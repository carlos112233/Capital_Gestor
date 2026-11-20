<?php

declare(strict_types=1);

use App\Http\Controllers\PagoVendedorController;
use App\Models\Articulo;
use App\Models\Comprobante;
use App\Models\PagoVendedor;
use App\Models\Pedido;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'vendedor']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'cocina']);
    Storage::fake('local');
    Storage::fake('public');
});

function assignKaizenRole(User $user, string $roleName): void
{
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('PagoVendedorController persiste retencion en MySQL, liquida comisiones y bloquea doble liquidacion', function () {
    $admin = User::factory()->create();
    assignKaizenRole($admin, 'admin');

    $vendedor = User::factory()->create([
        'name'     => 'Roberto Vendedor',
        'telefono' => '2224445566',
    ]);
    assignKaizenRole($vendedor, 'vendedor');

    $articulo = Articulo::create([
        'nombre'     => 'Cemita Tradicional',
        'precio'     => 100.00,
        'stock'      => 20,
        'disponible' => true,
    ]);

    // Registrar ventas operadas por el vendedor por $500.00 totales
    Venta::create([
        'user_id'      => $vendedor->id,
        'comprador_id' => $vendedor->id,
        'articulo_id'  => $articulo->id,
        'cantidad'     => 5,
        'precio_venta' => 100.00,
        'total_venta'  => 500.00,
    ]);

    // 1. Actualizar retención personalizada del vendedor al 15% en MySQL
    $this->actingAs($admin)->post(route('admin.pagos-vendedores.retencion'), [
        'vendedor_id'          => $vendedor->id,
        'porcentaje_retencion' => 15.00,
        'aplicar_todos'        => false,
    ])->assertRedirect(route('admin.pagos-vendedores.index'));

    expect(PagoVendedorController::getPorcentajeRetencion($vendedor->id))->toBe(15.00)
        ->and((float) $vendedor->fresh()->porcentaje_retencion)->toBe(15.00);

    // 2. Liquidar los $500.00 vendidos (15% retención = $75.00, neto = $425.00)
    $this->actingAs($admin)->post(route('pagos-vendedores.store'), [
        'vendedor_id'          => $vendedor->id,
        'total_vendido'        => 500.00,
        'porcentaje_retencion' => 15.00,
        'metodo_pago'          => 'transferencia',
        'notas'                => 'Corte semanal',
    ])->assertSessionHas('success');

    $pago = PagoVendedor::where('vendedor_id', $vendedor->id)->first();
    expect($pago)->not->toBeNull()
        ->and((float) $pago->total_vendido)->toBe(500.00)
        ->and((float) $pago->monto_retenido)->toBe(75.00)
        ->and((float) $pago->monto_neto_pagado)->toBe(425.00);

    // 3. Intentar liquidar de nuevo sin nuevas ventas debe ser rechazado por protección contra doble liquidación
    $this->actingAs($admin)->from(route('admin.pagos-vendedores.index'))->post(route('pagos-vendedores.store'), [
        'vendedor_id'          => $vendedor->id,
        'total_vendido'        => 500.00,
        'porcentaje_retencion' => 15.00,
        'metodo_pago'          => 'efectivo',
    ])->assertSessionHasErrors(['total_vendido']);

    expect(PagoVendedor::where('vendedor_id', $vendedor->id)->count())->toBe(1);
});

test('acceso privado a comprobantes permite propietario y admin (200), bloquea otro cliente (403) y redirige invitado', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignKaizenRole($admin, 'admin');

    $propietario = User::factory()->create();
    assignKaizenRole($propietario, 'cliente');

    $otroCliente = User::factory()->create();
    assignKaizenRole($otroCliente, 'cliente');

    $file = UploadedFile::fake()->image('voucher_privado.jpg', 400, 400);

    $this->actingAs($propietario)->post(route('comprobantes.store'), [
        'monto'  => 250.00,
        'imagen' => $file,
    ])->assertRedirect();

    $comprobante = Comprobante::where('user_id', $propietario->id)->latest('id')->first();
    expect($comprobante)->not->toBeNull();

    // El archivo se guardó en disco privado 'local' y NO en 'public'
    Storage::disk('local')->assertExists($comprobante->imagen);
    Storage::disk('public')->assertMissing($comprobante->imagen);

    // Propietario: HTTP 200
    $this->actingAs($propietario)
        ->get(route('comprobantes.imagen', $comprobante->id))
        ->assertStatus(200);

    // Admin: HTTP 200
    $this->actingAs($admin)
        ->get(route('comprobantes.imagen', $comprobante->id))
        ->assertStatus(200);

    // Otro cliente: HTTP 403
    $this->actingAs($otroCliente)
        ->get(route('comprobantes.imagen', $comprobante->id))
        ->assertStatus(403);

    // Invitado: Redirige a login
    auth()->logout();
    $this->get(route('comprobantes.imagen', $comprobante->id))
        ->assertRedirect('/login');
});

test('aprobar comprobante sin articulo Pago saldado en BD lo autoclona sin fallar y HSTS se inyecta en HTTPS', function () {
    Notification::fake();

    $admin = User::factory()->create();
    assignKaizenRole($admin, 'admin');

    $cliente = User::factory()->create(['telefono' => '2221113344']);
    assignKaizenRole($cliente, 'cliente');

    // Eliminar cualquier artículo 'Pago saldado' existente
    Articulo::whereRaw('LOWER(nombre) LIKE ?', ['%pago saldado%'])->delete();

    $comprobante = Comprobante::create([
        'user_id' => $cliente->id,
        'monto'   => 420.00,
        'imagen'  => 'comprobantes/voucher.jpg',
        'status'  => 'procesando_pago',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.comprobantes.aprobar', $comprobante->id));
    $response->assertRedirect();
    $response->headers->has('Strict-Transport-Security');

    expect($comprobante->fresh()->status)->toBe('aprobado')
        ->and(Articulo::where('nombre', 'Pago saldado')->exists())->toBeTrue();
});

test('WhatsAppNotificationService retryFailed reencola con limite de intentos y comando reenviar-cemitas-hoy usa categoria_cocina', function () {
    $waService = app(WhatsAppNotificationService::class);

    $msgId = $waService->enqueue('5212229990000', 'Mensaje fallido test');
    $waService->markAsFailed($msgId, 'Timeout temporal de Puppeteer');

    expect(DB::table(WhatsAppNotificationService::TABLE)->where('id', $msgId)->value('status'))->toBe('fallido')
        ->and((int) DB::table(WhatsAppNotificationService::TABLE)->where('id', $msgId)->value('attempts'))->toBe(1);

    $requeued = $waService->retryFailed(3, 10);
    expect($requeued)->toBe(1)
        ->and(DB::table(WhatsAppNotificationService::TABLE)->where('id', $msgId)->value('status'))->toBe('pendiente');

    // Probar comando pedidos:reenviar-cemitas-hoy con artículo de nombre neutro pero categoria_cocina = 'cemita'
    $cliente = User::factory()->create(['name' => 'Cliente Comando']);
    $artCemita = Articulo::create([
        'nombre'           => 'Especialidad Nocturna Z',
        'precio'           => 110.00,
        'stock'            => 5,
        'disponible'       => true,
        'requiere_cocina'  => true,
        'categoria_cocina' => Articulo::CATEGORIA_CEMITA,
    ]);

    Pedido::create([
        'user_id'     => $cliente->id,
        'articulo_id' => $artCemita->id,
        'costo'       => 110.00,
        'cantidad'    => 1,
        'descripcion' => 'Sin picante',
    ]);

    $this->artisan('pedidos:reenviar-cemitas-hoy --phone=2228887766')
        ->assertExitCode(0);

    $reenviado = DB::table(WhatsAppNotificationService::TABLE)
        ->where('numero', '5212228887766')
        ->where('mensaje', 'like', '%Especialidad Nocturna Z%')
        ->exists();

    expect($reenviado)->toBeTrue();
});
