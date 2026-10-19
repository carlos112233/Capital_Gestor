<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Role;
use App\Models\Articulo;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    (new \Database\Seeders\AppSettingSeeder())->run();
});

test('modal de subir comprobante en dashboard renderiza el componente file-dropzone moderno', function () {
    $cliente = User::factory()->create();
    $role = Role::where('name', 'cliente')->first();
    $cliente->roles()->syncWithoutDetaching([$role->id]);

    $response = $this->actingAs($cliente)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSee('border-dashed', false);
    $response->assertSee('Haga clic para seleccionar comprobante o arrastre aquí');
    $response->assertSee('Formatos permitidos: JPG, PNG, WEBP (Máx. 5MB)');
    $response->assertSee('name="imagen"', false);
});

test('formulario de edicion de perfil renderiza el componente file-dropzone moderno con foto', function () {
    $cliente = User::factory()->create();
    $role = Role::where('name', 'cliente')->first();
    $cliente->roles()->syncWithoutDetaching([$role->id]);

    $response = $this->actingAs($cliente)->get('/profile');
    $response->assertStatus(200);
    $response->assertSee('border-dashed', false);
    $response->assertSee('Haga clic para seleccionar foto de perfil o arrastre aquí');
    $response->assertSee('name="image"', false);
});

