<?php

declare(strict_types=1);

use App\Models\Articulo;
use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'cocina']);
});

function assignArticuloAuditRole(User $user, string $roleName): void
{
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('administrador puede crear y actualizar articulos con requiere_cocina y categoria_cocina y se exponen en API catalogo', function () {
    $admin = User::factory()->create();
    assignArticuloAuditRole($admin, 'admin');

    // 1. Crear artículo con metadatos explícitos de cocina
    $responseCreate = $this->actingAs($admin)->post(route('admin.articulos.store'), [
        'nombre'           => 'Platillo Especial del Chef',
        'descripcion'      => 'Con guarnición doble',
        'precio'           => 145.00,
        'stock'            => 12,
        'requiere_cocina'  => '1',
        'categoria_cocina' => Articulo::CATEGORIA_MILANESA_PUERCO,
    ]);

    $responseCreate->assertRedirect(route('admin.articulos.index'));

    $articulo = Articulo::where('nombre', 'Platillo Especial del Chef')->first();
    expect($articulo)->not->toBeNull()
        ->and($articulo->requiere_cocina)->toBeTrue()
        ->and($articulo->categoria_cocina)->toBe(Articulo::CATEGORIA_MILANESA_PUERCO)
        ->and($articulo->disponible)->toBeTrue();

    // 2. Actualizar artículo desactivando requiere_cocina sin enviar categoría -> debe normalizar a bebida
    $responseUpdate = $this->actingAs($admin)->put(route('admin.articulos.update', $articulo), [
        'nombre'          => 'Limonada Mineral 1L',
        'descripcion'     => 'Bebida embotellada',
        'precio'          => 35.00,
        'stock'           => 3, // Suma 3 al stock actual de 12 => 15
        'requiere_cocina' => '0',
    ]);

    $responseUpdate->assertRedirect(route('admin.articulos.index'));

    $articulo->refresh();
    expect($articulo->nombre)->toBe('Limonada Mineral 1L')
        ->and($articulo->stock)->toBe(15)
        ->and($articulo->requiere_cocina)->toBeFalse()
        ->and($articulo->categoria_cocina)->toBe(Articulo::CATEGORIA_BEBIDA);

    // 3. Verificar exposición en endpoint JSON del catálogo API
    Sanctum::actingAs($admin);
    $apiResponse = $this->getJson('/api/catalogo');
    $apiResponse->assertStatus(200);

    $items = collect($apiResponse->json('data'));
    $found = $items->firstWhere('id', $articulo->id);

    expect($found)->not->toBeNull()
        ->and($found['requiere_cocina'])->toBeFalse()
        ->and($found['categoria_cocina'])->toBe(Articulo::CATEGORIA_BEBIDA);
});

test('blindaje mass assignment en User y control de acceso 403 en endpoints API para rol cliente', function () {
    $clienteA = User::factory()->create([
        'name'            => 'Cliente A',
        'score_calculado' => 70,
        'override_score'  => false,
    ]);
    assignArticuloAuditRole($clienteA, 'cliente');

    $clienteB = User::factory()->create([
        'name'  => 'Cliente B',
        'email' => 'clienteb@elbajon.store',
    ]);
    assignArticuloAuditRole($clienteB, 'cliente');

    // 1. Intentar inyectar campos de scoring mediante mass assignment (fill)
    $clienteA->fill([
        'name'            => 'Cliente A Editado',
        'score_calculado' => 100,
        'score_manual'    => 100,
        'override_score'  => true,
    ]);

    expect($clienteA->name)->toBe('Cliente A Editado')
        ->and((int) $clienteA->score_calculado)->toBe(70)
        ->and((bool) $clienteA->override_score)->toBeFalse()
        ->and($clienteA->score_manual)->toBeNull();

    // 2. Un cliente autenticado via Sanctum NO puede modificar otro cliente ni crear entradas de capital
    Sanctum::actingAs($clienteA);

    $this->putJson("/api/clientes/{$clienteB->id}", [
        'name'  => 'Hackeado',
        'email' => 'hackeado@elbajon.store',
    ])->assertStatus(403);

    $art = Articulo::create([
        'nombre'     => 'Pago Test',
        'precio'     => 100,
        'stock'      => 10,
        'disponible' => true,
    ]);

    $this->postJson('/api/entradas', [
        'articulo_id'  => $art->id,
        'precio_venta' => 5000,
        'descripcion'  => 'Abono falso inyectado por cliente',
    ])->assertStatus(403);
});
