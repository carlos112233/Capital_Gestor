<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Pedido;
use App\Models\Articulo;
use App\Models\Entrada;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppNotification;

beforeEach(function () {
    // Asegurar roles en base de datos
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
});

function assignTestRole(User $user, string $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('un usuario autenticado puede guardar su suscripcion push', function () {
    $user = User::factory()->create();
    assignTestRole($user, 'cliente');

    $response = $this->actingAs($user)->postJson(route('push.subscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-token',
        'keys' => [
            'p256dh' => 'test-p256dh-key',
            'auth' => 'test-auth-key',
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-token',
    ]);
});

test('un usuario puede eliminar su suscripcion push', function () {
    $user = User::factory()->create();
    $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/test-endpoint-token', 'p256dh', 'auth');

    $response = $this->actingAs($user)->postJson(route('push.unsubscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-token',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseMissing('push_subscriptions', [
        'subscribable_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-token',
    ]);
});

test('al crear un pedido un usuario cliente notifica al administrador via push', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create();
    assignTestRole($cliente, 'cliente');

    $articulo = Articulo::create([
        'nombre' => 'Cemita de Pollo Test',
        'precio' => 85.00,
        'disponible' => true,
    ]);

    $response = $this->actingAs($cliente)->post(route('pedidos.store'), [
        'pedidos' => [
            [
                'articulo_id' => $articulo->id,
                'cantidad' => 1,
                'costo' => 85.00,
                'descripcion' => 'Sin cebolla',
            ]
        ]
    ]);

    $response->assertRedirect(route('pedidos.index'));

    Notification::assertSentTo(
        [$admin],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Nuevo Pedido Recibido');
        }
    );
});

test('el administrador puede actualizar el estado del pedido en 1 clic y notifica al cliente', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create();
    assignTestRole($cliente, 'cliente');

    $articulo = Articulo::create([
        'nombre' => 'Chilaquiles Test',
        'precio' => 70.00,
        'disponible' => true,
    ]);

    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'costo' => 70.00,
        'cantidad' => 1,
        'status' => 'realizado',
    ]);

    // Cambiar a 'en_preparacion'
    $response = $this->actingAs($admin)->postJson(route('admin.pedidos.status', $pedido->id), [
        'status' => 'En preparación',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true, 'status' => 'en_preparacion']);
    $this->assertEquals('en_preparacion', $pedido->fresh()->status);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Estado de Pedido');
        }
    );

    // Cambiar a 'entregado'
    $response2 = $this->actingAs($admin)->postJson(route('admin.pedidos.status', $pedido->id), [
        'status' => 'Entregado',
    ]);

    $response2->assertStatus(200);
    $response2->assertJson(['success' => true, 'status' => 'entregado']);
    $this->assertEquals('entregado', $pedido->fresh()->status);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Pedido Entregado');
        }
    );
});

test('las alertas matutinas de entregas no envian notificaciones si no hay pedidos en preparacion', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $count = PushNotificationService::sendScheduledDeliveryReminders();

    $this->assertEquals(0, $count);
    Notification::assertNothingSent();
});

test('las alertas matutinas de entregas envian 1 notificacion independiente por cada pedido en preparacion', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create();
    assignTestRole($cliente, 'cliente');

    $articulo = Articulo::create([
        'nombre' => 'Cemita Hawaiana Test',
        'precio' => 90.00,
        'disponible' => true,
    ]);

    // Crear 2 pedidos en preparación
    $pedido1 = Pedido::create([
        'user_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'costo' => 90.00,
        'cantidad' => 1,
        'status' => 'en_preparacion',
    ]);

    $pedido2 = Pedido::create([
        'user_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'costo' => 90.00,
        'cantidad' => 1,
        'status' => 'en_preparacion',
    ]);

    $count = PushNotificationService::sendScheduledDeliveryReminders();

    $this->assertEquals(2, $count);
    Notification::assertSentToTimes($admin, AppNotification::class, 2);
});

test('la funcion de eliminar entrada (reversion de pago) envia notificacion push al cliente y respeta el checkbox de whatsapp', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create([
        'telefono' => '2221234567',
    ]);
    assignTestRole($cliente, 'cliente');

    $articulo = Articulo::create([
        'nombre' => 'Pago saldado Test',
        'precio' => 500.00,
        'disponible' => true,
    ]);

    $entrada = Entrada::create([
        'user_id' => $cliente->id,
        'cliente_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'precio_venta' => 500.00,
        'descripcion' => 'Abono semanal',
        'fecha_generado' => now(),
    ]);

    // Eliminar con enviar_wa desactivado (0)
    $response = $this->actingAs($admin)->delete(route('admin.entradas.destroy', $entrada->id), [
        'enviar_wa' => '0',
    ]);

    $response->assertRedirect(route('admin.entradas.index'));
    $this->assertDatabaseMissing('entradas', ['id' => $entrada->id]);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Actualización de Pago');
        }
    );

    // Verificar que NO se encoló mensaje de WhatsApp porque estaba desmarcado
    $this->assertDatabaseMissing('whatsapp_pending_messages', [
        'numero' => '5212221234567',
    ]);
});
