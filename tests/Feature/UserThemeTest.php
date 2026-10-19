<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Role::firstOrCreate(['name' => 'vendedor']);
    Role::firstOrCreate(['name' => 'cocina']);
    (new \Database\Seeders\AppSettingSeeder())->run();
    AppSetting::clearSettingsCache();
});

function createTestUserWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $role = Role::where('name', $roleName)->first();
    $user->roles()->syncWithoutDetaching([$role->id]);
    return $user;
}

test('invitados no autenticados son redirigidos al login al intentar acceder a /mi-tema', function () {
    $response = $this->get('/mi-tema');
    $response->assertRedirect('/login');
});

test('administrador que ingresa a /mi-tema es redirigido a /admin/personalizacion', function () {
    $admin = createTestUserWithRole('admin');
    $response = $this->actingAs($admin)->get('/mi-tema');
    $response->assertRedirect(route('admin.branding.index'));
});

test('usuarios no administradores pueden ver la vista /mi-tema sin controles de marca corporativa', function () {
    $cliente = createTestUserWithRole('cliente');
    $response = $this->actingAs($cliente)->get('/mi-tema');
    $response->assertStatus(200);
    $response->assertSee('Mi Apariencia y Tema');
    $response->assertSee('Modo de Visualización');
    $response->assertSee('Color de Acento');
    $response->assertSee('Restablecer Valores Institucionales');
    $response->assertDontSee('Nombre de la Empresa');
    $response->assertDontSee('Logotipo Oficial');
    $response->assertDontSee('Favicon del Navegador');
});

test('usuario cliente puede actualizar su modo y color de tema personal', function () {
    $cliente = createTestUserWithRole('cliente');

    $response = $this->actingAs($cliente)->post('/mi-tema', [
        'theme_mode' => 'dark',
        'theme_color' => '#10b981',
    ]);

    $response->assertRedirect(route('theme.index'));
    $response->assertSessionHas('success');

    $cliente->refresh();
    expect($cliente->theme_mode)->toBe('dark');
    expect($cliente->theme_color)->toBe('#10b981');
});

test('validacion rechaza modos invalidos o colores con formato erroneo', function () {
    $cliente = createTestUserWithRole('cliente');

    $response = $this->actingAs($cliente)->post('/mi-tema', [
        'theme_mode' => 'neon_future',
        'theme_color' => 'not-a-hex-color',
    ]);

    $response->assertSessionHasErrors(['theme_mode', 'theme_color']);
});

test('usuario puede restablecer su tema a los valores institucionales', function () {
    $vendedor = createTestUserWithRole('vendedor');
    $vendedor->update([
        'theme_mode' => 'dark',
        'theme_color' => '#ec4899',
    ]);

    $response = $this->actingAs($vendedor)->post('/mi-tema/reset');
    $response->assertRedirect(route('theme.index'));
    $response->assertSessionHas('success');

    $vendedor->refresh();
    expect($vendedor->theme_mode)->toBe('system');
    expect($vendedor->theme_color)->toBeNull();
});

test('el layout inyecta las variables CSS del usuario autenticado sobre las globales', function () {
    $cliente = createTestUserWithRole('cliente');
    $cliente->update([
        'theme_mode' => 'dark',
        'theme_color' => '#f59e0b',
    ]);

    $response = $this->actingAs($cliente)->get('/mi-tema');
    $response->assertStatus(200);

    // Debe inyectar el color del usuario en lugar del predeterminado
    $response->assertSee('--color-primary: #f59e0b;', false);
    $response->assertSee('--color-primary-rgb: 245, 158, 11;', false);
});

test('el sidebar muestra Mi Apariencia para clientes y no para administradores', function () {
    $cliente = createTestUserWithRole('cliente');
    $admin = createTestUserWithRole('admin');

    $resCliente = $this->actingAs($cliente)->get('/mi-tema');
    $resCliente->assertSee('Mi Apariencia');
    $resCliente->assertDontSee('Personalización y Marca');

    $resAdmin = $this->actingAs($admin)->get('/admin/personalizacion');
    $resAdmin->assertDontSee('Mi Apariencia');
    $resAdmin->assertSee('Personalización y Marca');
});

