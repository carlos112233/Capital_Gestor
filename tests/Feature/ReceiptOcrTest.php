<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Comprobante;
use App\Models\Entrada;
use App\Models\Articulo;
use App\Services\ReceiptOcrService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppNotification;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Storage::fake('public');
});

function assignRoleToUser(User $user, string $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('ReceiptOcrService reconoce correctamente datos clave de un comprobante bancario SPEI BBVA', function () {
    $rawText = "BBVA BANCOMER
    COMPROBANTE DE PAGO SPEI
    CLAVE DE RASTREO: 202609024001234567890
    FECHA: 02/09/2026
    IMPORTE: $ 1,250.50 MXN
    CONCEPTO: Pago de pedido";

    $parsed = ReceiptOcrService::parseReceiptText($rawText);

    $this->assertTrue($parsed['is_valid_receipt']);
    $this->assertEquals('BBVA', $parsed['banco']);
    $this->assertEquals('202609024001234567890', $parsed['clave_rastreo']);
    $this->assertEquals(1250.50, $parsed['monto_extraido']);
    $this->assertEquals('02/09/2026', $parsed['fecha_transferencia']);
});

test('un cliente sube un comprobante y se asigna el estado procesando_pago y datos OCR', function () {
    Notification::fake();

    $admin = User::factory()->create(['telefono' => '2221112233']);
    assignRoleToUser($admin, 'admin');

    $cliente = User::factory()->create(['name' => 'Carlos Cliente']);
    assignRoleToUser($cliente, 'cliente');

    $file = UploadedFile::fake()->image('comprobante_transferencia.jpg', 600, 800);

    $response = $this->actingAs($cliente)->post(route('comprobantes.store'), [
        'monto' => 350.00,
        'imagen' => $file,
        'notas' => 'Pago por transferencia BBVA'
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('comprobantes', [
        'user_id' => $cliente->id,
        'status' => 'procesando_pago',
        'monto' => 350.00,
    ]);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Comprobante en Revisión');
        }
    );
});

test('el administrador aprueba el comprobante en procesando_pago y genera la entrada de capital', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignRoleToUser($admin, 'admin');

    $cliente = User::factory()->create();
    assignRoleToUser($cliente, 'cliente');

    Articulo::create([
        'nombre' => 'Pago saldado',
        'precio' => 0.00,
        'disponible' => true,
    ]);

    $comprobante = Comprobante::create([
        'user_id' => $cliente->id,
        'monto' => 800.00,
        'imagen' => 'comprobantes/test.jpg',
        'status' => 'procesando_pago',
        'banco' => 'BBVA',
        'clave_rastreo' => '123456789',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.comprobantes.aprobar', $comprobante->id));

    $response->assertRedirect();
    $this->assertEquals('aprobado', $comprobante->fresh()->status);

    $this->assertDatabaseHas('entradas', [
        'user_id' => $cliente->id,
        'precio_venta' => 800.00,
    ]);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Pago Aprobado');
        }
    );
});

test('el administrador rechaza el comprobante y respeta el checkbox de whatsapp desmarcado', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignRoleToUser($admin, 'admin');

    $cliente = User::factory()->create(['telefono' => '2229876543']);
    assignRoleToUser($cliente, 'cliente');

    $comprobante = Comprobante::create([
        'user_id' => $cliente->id,
        'monto' => 450.00,
        'imagen' => 'comprobantes/test2.jpg',
        'status' => 'procesando_pago',
    ]);

    // Rechazar con enviar_wa = 0 (checkbox desmarcado por defecto)
    $response = $this->actingAs($admin)->post(route('admin.comprobantes.rechazar', $comprobante->id), [
        'enviar_wa' => '0',
    ]);

    $response->assertRedirect();
    $this->assertEquals('rechazado', $comprobante->fresh()->status);

    Notification::assertSentTo(
        [$cliente],
        AppNotification::class,
        function ($notification) {
            return str_contains($notification->title, 'Actualización de Pago');
        }
    );

    // Verificar que NO se encoló mensaje en WhatsApp por estar desmarcado
    $this->assertDatabaseMissing('whatsapp_pending_messages', [
        'numero' => '5212229876543',
    ]);
});
