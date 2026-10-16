<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'cliente']);
    Storage::fake('public');
    (new \Database\Seeders\AppSettingSeeder())->run();
    AppSetting::clearSettingsCache();
});

function createAdminUser(): User
{
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'admin')->first();
    $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    return $admin;
}

function createClientUser(): User
{
    $client = User::factory()->create();
    $clientRole = Role::where('name', 'cliente')->first();
    $client->roles()->syncWithoutDetaching([$clientRole->id]);
    return $client;
}

test('usuarios no autenticados son redirigidos al login al intentar acceder a personalizacion', function () {
    $response = $this->get('/admin/personalizacion');
    $response->assertRedirect('/login');
});

test('usuarios con rol cliente no pueden acceder al panel de personalizacion', function () {
    $client = createClientUser();
    $response = $this->actingAs($client)->get('/admin/personalizacion');
    $response->assertStatus(403);
});

test('administrador puede ver el panel de personalizacion y marca blanca', function () {
    $admin = createAdminUser();
    $response = $this->actingAs($admin)->get('/admin/personalizacion');
    $response->assertStatus(200);
    $response->assertSee('Personalización y Marca Blanca');
    $response->assertSee('Temas Preconfigurados en 1 Clic');
    $response->assertSee('Restablecer a Valores de Fábrica');
});

test('administrador puede actualizar los colores, nombre y tipografia del sistema', function () {
    $admin = createAdminUser();

    $payload = [
        'app_name' => 'El Bajón Burgers & Fries',
        'primary_color' => '#2563eb',
        'secondary_color' => '#06b6d4',
        'sidebar_color' => '#1e293b',
        'font_family' => 'Poppins',
        'theme_mode' => 'light',
    ];

    $response = $this->actingAs($admin)->post('/admin/personalizacion', $payload);
    $response->assertRedirect('/admin/personalizacion');
    $response->assertSessionHas('success');

    $setting = AppSetting::first();
    expect($setting->app_name)->toBe('El Bajón Burgers & Fries')
        ->and($setting->primary_color)->toBe('#2563eb')
        ->and($setting->secondary_color)->toBe('#06b6d4')
        ->and($setting->sidebar_color)->toBe('#1e293b')
        ->and($setting->font_family)->toBe('Poppins');

    // Comprobar que getSettings() devuelve la versión actualizada
    $cached = AppSetting::getSettings();
    expect($cached->app_name)->toBe('El Bajón Burgers & Fries')
        ->and($cached->primary_color)->toBe('#2563eb');
});

test('administrador puede subir un nuevo logotipo y favicon y se guardan en storage', function () {
    $admin = createAdminUser();

    $logo = UploadedFile::fake()->image('mi_logo.png', 300, 300);
    $favicon = UploadedFile::fake()->image('mi_favicon.png', 32, 32);

    $payload = [
        'app_name' => 'El Bajón Gourmet',
        'primary_color' => '#6366f1',
        'secondary_color' => '#10b981',
        'sidebar_color' => '#0f172a',
        'font_family' => 'Inter',
        'theme_mode' => 'light',
        'logo' => $logo,
        'favicon' => $favicon,
    ];

    $response = $this->actingAs($admin)->post('/admin/personalizacion', $payload);
    $response->assertRedirect('/admin/personalizacion');

    $setting = AppSetting::first();
    expect($setting->logo_path)->not->toBeNull()
        ->and($setting->favicon_path)->not->toBeNull();

    Storage::disk('public')->assertExists($setting->logo_path);
    Storage::disk('public')->assertExists($setting->favicon_path);
});

test('boton de reset restaura los valores de fabrica y elimina archivos personalizados de storage', function () {
    $admin = createAdminUser();

    // 1. Modificar valores y subir archivos
    $logo = UploadedFile::fake()->image('custom_logo.png', 200, 200);
    $favicon = UploadedFile::fake()->image('custom_favicon.png', 32, 32);

    $this->actingAs($admin)->post('/admin/personalizacion', [
        'app_name' => 'Custom Branding Temp',
        'primary_color' => '#ff0000',
        'secondary_color' => '#00ff00',
        'sidebar_color' => '#000000',
        'font_family' => 'Montserrat',
        'theme_mode' => 'dark',
        'logo' => $logo,
        'favicon' => $favicon,
    ]);

    $modifiedSetting = AppSetting::first();
    $uploadedLogoPath = $modifiedSetting->logo_path;
    $uploadedFaviconPath = $modifiedSetting->favicon_path;

    Storage::disk('public')->assertExists($uploadedLogoPath);
    Storage::disk('public')->assertExists($uploadedFaviconPath);

    // 2. Ejecutar Reset Quirúrgico
    $response = $this->actingAs($admin)->post('/admin/personalizacion/reset');
    $response->assertRedirect('/admin/personalizacion');
    $response->assertSessionHas('success');

    // 3. Verificar que los archivos fueron eliminados de storage
    Storage::disk('public')->assertMissing($uploadedLogoPath);
    Storage::disk('public')->assertMissing($uploadedFaviconPath);

    // 4. Verificar que los valores en BD volvieron a ser los de fábrica exactos de El Bajón
    $resetSetting = AppSetting::first();
    expect($resetSetting->app_name)->toBe(AppSetting::DEFAULT_APP_NAME)
        ->and($resetSetting->primary_color)->toBe(AppSetting::DEFAULT_PRIMARY_COLOR)
        ->and($resetSetting->secondary_color)->toBe(AppSetting::DEFAULT_SECONDARY_COLOR)
        ->and($resetSetting->sidebar_color)->toBe(AppSetting::DEFAULT_SIDEBAR_COLOR)
        ->and($resetSetting->font_family)->toBe(AppSetting::DEFAULT_FONT_FAMILY)
        ->and($resetSetting->theme_mode)->toBe(AppSetting::DEFAULT_THEME_MODE)
        ->and($resetSetting->logo_path)->toBeNull()
        ->and($resetSetting->favicon_path)->toBeNull();

    // 5. Verificar que la caché también se refrescó
    $cached = AppSetting::getSettings();
    expect($cached->app_name)->toBe(AppSetting::DEFAULT_APP_NAME)
        ->and($cached->primary_color)->toBe(AppSetting::DEFAULT_PRIMARY_COLOR);
});

test('el sidebar refleja el color configurado en app_settings con css variable y contenedores transparentes', function () {
    $admin = createAdminUser();

    $setting = AppSetting::first() ?? new AppSetting();
    $setting->sidebar_color = '#1e293b';
    $setting->save();
    AppSetting::clearSettingsCache();

    $response = $this->actingAs($admin)->get('/admin/personalizacion');

    $response->assertOk();
    $response->assertSee('--color-sidebar: #1e293b;', false);
    $response->assertSee('id="sidebar"', false);
    $response->assertSee('style="background-color: var(--color-sidebar, #0f172a) !important;"', false);
    $response->assertSee('#sidebar, #sidebar-mobile', false);
});

test('el modo oscuro inyecta la clase dark en html y contiene script dinamico para modo system', function () {
    $admin = createAdminUser();

    // 1. Caso Oscuro (dark)
    $setting = AppSetting::first() ?? new AppSetting();
    $setting->theme_mode = 'dark';
    $setting->sidebar_color = '#1e293b';
    $setting->save();
    AppSetting::clearSettingsCache();

    $responseDark = $this->actingAs($admin)->get('/admin/personalizacion');
    $responseDark->assertOk();
    $responseDark->assertSee('<html lang="es" class="dark">', false);
    $responseDark->assertSee('Oscuro (dark)', false);
    $responseDark->assertDontSee('Oscuro (Próximamente)', false);

    // 2. Caso Automático (system)
    $setting->theme_mode = 'system';
    $setting->save();
    AppSetting::clearSettingsCache();

    $responseSystem = $this->actingAs($admin)->get('/admin/personalizacion');
    $responseSystem->assertOk();
    $responseSystem->assertSee("window.matchMedia('(prefers-color-scheme: dark)')", false);
    $responseSystem->assertSee('Automático (system)', false);
});

test('administrador puede ver la seccion restaurada de personalizacion de logotipo en configuracion', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get('/admin/configuracion');
    $response->assertOk();
    $response->assertSee('Personalización de Logotipo');
    $response->assertSee('Logotipo Actual');
    $response->assertSee('Suite Completa de Marca');
    $response->assertSee(route('admin.configuracion.logo'), false);
    $response->assertSee('name="logo"', false);
});

test('administrador puede subir un logotipo desde configuracion y se sincroniza con AppSetting', function () {
    $admin = createAdminUser();

    $logo = UploadedFile::fake()->image('nuevo_logo_config.png', 400, 400);

    $response = $this->actingAs($admin)->post('/admin/configuracion/logo', [
        'logo' => $logo,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $setting = AppSetting::first();
    expect($setting->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($setting->logo_path);

    // Comprobar que AppSetting::getSettings()->logo_url refleja la ruta guardada
    $cached = AppSetting::getSettings();
    expect($cached->logo_path)->toBe($setting->logo_path);
});



