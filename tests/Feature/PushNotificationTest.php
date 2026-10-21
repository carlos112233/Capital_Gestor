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
        function ($notification) use ($cliente) {
            return str_contains($notification->title, 'Nuevo Pedido Recibido')
                && str_contains($notification->message, "El cliente {$cliente->name} realizó un pedido de 1 producto(s)")
                && str_contains($notification->message, "1x Cemita de Pollo Test");
        }
    );
});

test('notificarAdminWhatsApp envia push al admin indicando cuantos y cuales productos compro', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create(['name' => 'Carlos Cliente']);
    assignTestRole($cliente, 'cliente');

    $articulo = Articulo::create([
        'nombre' => 'Cemita Especial',
        'precio' => 120.00,
        'disponible' => true,
    ]);

    $venta = \App\Models\Venta::create([
        'user_id'      => $cliente->id,
        'comprador_id' => $cliente->id,
        'articulo_id'  => $articulo->id,
        'cantidad'     => 2,
        'precio_venta' => 120.00,
        'total_venta'  => 240.00,
        'descripcion'  => 'Para llevar',
    ]);

    \App\Models\Venta::notificarAdminWhatsApp($venta);

    Notification::assertSentTo(
        [$admin],
        AppNotification::class,
        function ($notification) {
            return $notification->title === 'Nueva Compra Registrada 🛒'
                && str_contains($notification->message, "El cliente Carlos Cliente compró 2 producto(s)")
                && str_contains($notification->message, "2x Cemita Especial")
                && str_contains($notification->message, "240.00");
        }
    );
});

test('notificarCarritoAdminWhatsApp envia push al admin indicando cuantos y cuales productos compro en carrito', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignTestRole($admin, 'admin');

    $cliente = User::factory()->create(['name' => 'Maria Lopez']);
    assignTestRole($cliente, 'cliente');

    $art1 = Articulo::create([
        'nombre' => 'Hamburguesa Doble',
        'precio' => 95.00,
        'disponible' => true,
    ]);

    $art2 = Articulo::create([
        'nombre' => 'Refresco 600ml',
        'precio' => 25.00,
        'disponible' => true,
    ]);

    $venta1 = \App\Models\Venta::create([
        'user_id'      => $cliente->id,
        'comprador_id' => $cliente->id,
        'articulo_id'  => $art1->id,
        'cantidad'     => 2,
        'precio_venta' => 95.00,
        'total_venta'  => 190.00,
    ]);

    $venta2 = \App\Models\Venta::create([
        'user_id'      => $cliente->id,
        'comprador_id' => $cliente->id,
        'articulo_id'  => $art2->id,
        'cantidad'     => 3,
        'precio_venta' => 25.00,
        'total_venta'  => 75.00,
    ]);

    \App\Models\Venta::notificarCarritoAdminWhatsApp([$venta1, $venta2], 265.00);

    Notification::assertSentTo(
        [$admin],
        AppNotification::class,
        function ($notification) {
            return $notification->title === 'Nueva Compra Múltiple (Carrito) 🛒'
                && str_contains($notification->message, "El cliente Maria Lopez compró 5 producto(s)")
                && str_contains($notification->message, "2x Hamburguesa Doble")
                && str_contains($notification->message, "3x Refresco 600ml")
                && str_contains($notification->message, "265.00");
        }
    );

    $msg = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')
        ->where('numero', '5212222153410')
        ->latest('id')
        ->first();
    $this->assertNotNull($msg);
    $this->assertStringContainsString('• *Total de productos:* 5 unidad(es)', $msg->mensaje);
    $this->assertStringContainsString('Hamburguesa Doble (x2)', $msg->mensaje);
    $this->assertStringContainsString('Refresco 600ml (x3)', $msg->mensaje);
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
    $pedido1->created_at = now()->subDay();
    $pedido1->save();

    $pedido2 = Pedido::create([
        'user_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'costo' => 90.00,
        'cantidad' => 1,
        'status' => 'en_preparacion',
    ]);
    $pedido2->created_at = now()->subDay();
    $pedido2->save();

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
