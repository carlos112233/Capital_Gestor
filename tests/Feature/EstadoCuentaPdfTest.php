<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Venta;
use App\Models\Articulo;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
});

function assignRoleToUserForPdf(User $user, string $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

test('cliente autenticado puede descargar su estado de cuenta pdf con qr', function () {
    $cliente = User::factory()->create([
        'name' => 'Cliente Pruebas EL BAJON',
        'email' => 'cliente_pdf@test.com',
    ]);
    assignRoleToUserForPdf($cliente, 'cliente');

    // Registrar ventas de prueba
    $articulo = Articulo::create([
        'nombre' => 'Hamburguesa Especial',
        'precio' => 150.00,
        'precio_venta' => 150.00,
        'stock' => 50,
        'disponible' => true
    ]);

    Venta::create([
        'user_id' => $cliente->id,
        'articulo_id' => $articulo->id,
        'cantidad' => 1,
        'precio_venta' => 150.00,
        'total_venta' => 150.00,
        'created_at' => now()
    ]);

    $response = $this->actingAs($cliente)->get('/estado-cuenta/pdf');

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

test('administrador puede generar el pdf de estado de cuenta de cualquier cliente', function () {
    $admin = User::factory()->create([
        'name' => 'Admin EL BAJON',
        'email' => 'admin_pdf@test.com'
    ]);
    assignRoleToUserForPdf($admin, 'admin');

    $cliente = User::factory()->create([
        'name' => 'Cliente Destino',
        'email' => 'cliente_destino@test.com',
    ]);
    assignRoleToUserForPdf($cliente, 'cliente');

    $response = $this->actingAs($admin)->get("/admin/clientes/{$cliente->id}/estado-cuenta/pdf");

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

test('comando artisan storage:migrate-base64 convierte binarios base64 a archivos fisicos', function () {
    $exitCode = Artisan::call('storage:migrate-base64');
    expect($exitCode)->toBe(0);
});
