<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Models\Entrada;
use App\Models\Articulo;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'vendedor']);
});

test('administrador ve el panel analitico ejecutivo y scoring en la vista de cobros y pagos', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $admin->roles()->syncWithoutDetaching([$adminRole->id]);

    $response = $this->actingAs($admin)->get(route('cobros.index'));

    $response->assertStatus(200);
    $response->assertSee('Panel Analítico Ejecutivo y Scoring');
    $response->assertSee('Clientes VIP / Platino');
    $response->assertSee('Score ≥ 80 pts');
    $response->assertSee('Clientes en Riesgo');
    $response->assertSee('Tendencia de Cobros vs Ventas');
    $response->assertSee('chart-ingresos-deuda');
    $response->assertViewHas('countVip');
    $response->assertViewHas('countRiesgo');
    $response->assertViewHas('chartCategories');
    $response->assertViewHas('chartVentas');
    $response->assertViewHas('chartCobros');
});

test('usuario cliente no ve el panel analitico ejecutivo exclusivo de administrador en cobros', function () {
    $cliente = User::factory()->create();
    $clienteRole = Role::where('name', 'cliente')->first();
    $cliente->roles()->syncWithoutDetaching([$clienteRole->id]);

    $response = $this->actingAs($cliente)->get(route('cobros.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Panel Analítico Ejecutivo y Scoring');
    $response->assertDontSee('Exclusivo Administrador');
});

test('el dashboard de administrador ya no contiene el panel analitico ejecutivo reubicado', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $admin->roles()->syncWithoutDetaching([$adminRole->id]);

    $response = $this->actingAs($admin)->get(route('dashboardAdmin'));

    $response->assertStatus(200);
    $response->assertDontSee('Panel Analítico Ejecutivo y Scoring');
    $response->assertDontSee('chart-ingresos-deuda');
});
