<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Role;
use App\Notifications\AppNotification;
use App\Services\NotificationCircuitBreakerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config(['broadcasting.default' => 'null']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'admin']);
});

test('marcar una notificacion individual como leida cortocircuita y cancela mensajes de whatsapp pendientes del usuario', function () {
    // 1. Crear usuario con teléfono
    $cliente = User::factory()->create([
        'name' => 'Roberto Prueba',
        'telefono' => '2225551234',
    ]);
    $otroCliente = User::factory()->create([
        'name' => 'Otro Cliente',
        'telefono' => '2229998888',
    ]);

    // 2. Encolar mensajes pendientes en whatsapp_pending_messages
    $msgId1 = DB::table('whatsapp_pending_messages')->insertGetId([
        'numero' => '5212225551234',
        'mensaje' => 'Hola Roberto, recordatorio de tu saldo pendiente de $450.00.',
        'status' => 'pendiente',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $msgId2 = DB::table('whatsapp_pending_messages')->insertGetId([
        'numero' => '5212225551234',
        'mensaje' => 'Hola Roberto, te compartimos tu Estado de Cuenta adjunto con saldo a cubrir.',
        'status' => 'pendiente',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $msgOtro = DB::table('whatsapp_pending_messages')->insertGetId([
        'numero' => '5212229998888',
        'mensaje' => 'Mensaje para otro cliente.',
        'status' => 'pendiente',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 3. Crear una notificación en base de datos para el cliente
    $cliente->notify(new AppNotification('Recordatorio de Saldo Pendiente', 'Tienes un saldo de $450.00'));
    $notif = $cliente->unreadNotifications()->first();

    expect($notif)->not->toBeNull();
    expect($notif->read_at)->toBeNull();

    // 4. Ejecutar la acción "Marcar como Leído" mediante la ruta
    $response = $this->actingAs($cliente)
        ->postJson(route('notifications.markRead', $notif->id));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    // 5. Verificar que la notificación ahora esté leída
    expect($notif->fresh()->read_at)->not->toBeNull();

    // 6. Verificar que los mensajes pendientes de este cliente cambiaron a 'cancelado'
    $statusMsg1 = DB::table('whatsapp_pending_messages')->where('id', $msgId1)->value('status');
    $statusMsg2 = DB::table('whatsapp_pending_messages')->where('id', $msgId2)->value('status');
    $statusMsgOtro = DB::table('whatsapp_pending_messages')->where('id', $msgOtro)->value('status');

    expect($statusMsg1)->toBe('cancelado')
        ->and($statusMsg2)->toBe('cancelado')
        ->and($statusMsgOtro)->toBe('pendiente'); // El de otro cliente debe seguir pendiente
});

test('marcar todas las notificaciones como leidas cancela todos los envios pendientes del usuario', function () {
    $cliente = User::factory()->create([
        'name' => 'Laura Notif',
        'telefono' => '2223334455',
    ]);

    // Encolar mensaje pendiente
    $msgId = DB::table('whatsapp_pending_messages')->insertGetId([
        'numero' => '5212223334455',
        'mensaje' => 'Aviso general programado para Laura.',
        'status' => 'pendiente',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Crear dos notificaciones no leídas
    $cliente->notify(new AppNotification('Aviso 1', 'Detalle 1'));
    $cliente->notify(new AppNotification('Aviso 2', 'Detalle 2'));

    expect($cliente->unreadNotifications()->count())->toBe(2);

    // Marcar todas como leídas
    $response = $this->actingAs($cliente)
        ->postJson(route('notifications.markAllRead'));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    expect($cliente->unreadNotifications()->count())->toBe(0);

    // El mensaje de WhatsApp debe estar cancelado
    $status = DB::table('whatsapp_pending_messages')->where('id', $msgId)->value('status');
    expect($status)->toBe('cancelado');
});
