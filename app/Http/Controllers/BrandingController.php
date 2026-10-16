<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
    /**
     * Muestra la vista del panel de personalización y marca blanca.
     */
    public function index(): View
    {
        $settings = AppSetting::getSettings();

        $fontOptions = [
            'Inter' => 'Inter (Estilo Moderno & Limpio)',
            'Poppins' => 'Poppins (Geométrico & Amigable)',
            'Roboto' => 'Roboto (Clásico & Legible)',
            'Plus Jakarta Sans' => 'Plus Jakarta Sans (Corporativo & Elegante)',
            'Montserrat' => 'Montserrat (Audaz & Moderno)',
        ];

        $presets = [
            'clasico' => [
                'name' => 'El Bajón Clásico',
                'primary' => AppSetting::DEFAULT_PRIMARY_COLOR,
                'secondary' => AppSetting::DEFAULT_SECONDARY_COLOR,
                'sidebar' => AppSetting::DEFAULT_SIDEBAR_COLOR,
                'font' => AppSetting::DEFAULT_FONT_FAMILY,
                'description' => 'Paleta institucional original con tonos índigo, esmeralda y pizarra oscura.',
            ],
            'dark_elegante' => [
                'name' => 'Dark Elegante',
                'primary' => '#f59e0b',
                'secondary' => '#10b981',
                'sidebar' => '#18181b',
                'font' => 'Plus Jakarta Sans',
                'description' => 'Elegante y de alto contraste con acentos ámbar y fondo zinc ultra oscuro.',
            ],
            'azul_corporativo' => [
                'name' => 'Azul Corporativo',
                'primary' => '#2563eb',
                'secondary' => '#06b6d4',
                'sidebar' => '#1e293b',
                'font' => 'Poppins',
                'description' => 'Tono corporativo y confiable con azules vibrantes y cian.',
            ],
            'esmeralda_fresco' => [
                'name' => 'Esmeralda Fresco',
                'primary' => '#059669',
                'secondary' => '#3b82f6',
                'sidebar' => '#064e3b',
                'font' => 'Montserrat',
                'description' => 'Tonos verdes esmeralda frescos y fondo bosque profundo.',
            ],
        ];

        return view('admin.settings.branding', compact('settings', 'fontOptions', 'presets'));
    }

    /**
     * Guarda la personalización de la aplicación.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['required', 'string', 'in:Inter,Poppins,Roboto,Plus Jakarta Sans,Montserrat'],
            'theme_mode' => ['nullable', 'string', 'in:light,dark,system'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,x-icon', 'max:1024'],
        ], [
            'app_name.required' => 'El nombre del sistema es obligatorio.',
            'primary_color.regex' => 'El color primario debe ser un código hexadecimal válido (ej. #6366f1).',
            'secondary_color.regex' => 'El color secundario debe ser un código hexadecimal válido (ej. #10b981).',
            'sidebar_color.regex' => 'El color del menú lateral debe ser un código hexadecimal válido (ej. #0f172a).',
            'font_family.in' => 'La tipografía seleccionada no es compatible.',
            'logo.max' => 'El logotipo no debe superar los 2MB.',
            'favicon.max' => 'El favicon no debe superar 1MB.',
        ]);

        $setting = AppSetting::first() ?? new AppSetting();

        // Manejo de carga de Logotipo
        if ($request->hasFile('logo')) {
            if (!empty($setting->logo_path) && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $logoPath = $request->file('logo')->store('branding', 'public');
            $setting->logo_path = $logoPath;
        }

        // Manejo de carga de Favicon
        if ($request->hasFile('favicon')) {
            if (!empty($setting->favicon_path) && Storage::disk('public')->exists($setting->favicon_path)) {
                Storage::disk('public')->delete($setting->favicon_path);
            }
            $faviconPath = $request->file('favicon')->store('branding', 'public');
            $setting->favicon_path = $faviconPath;
        }

        $setting->app_name = $validated['app_name'];
        $setting->primary_color = $validated['primary_color'];
        $setting->secondary_color = $validated['secondary_color'];
        $setting->sidebar_color = $validated['sidebar_color'];
        $setting->font_family = $validated['font_family'];
        $setting->theme_mode = $validated['theme_mode'] ?? 'light';
        $setting->save();

        Cache::forget('app_settings');
        AppSetting::clearSettingsCache();

        return redirect()->route('admin.branding.index')
            ->with('success', '¡Personalización y marca del sistema guardadas exitosamente!');
    }

    /**
     * Restablece la configuración de marca a los valores de fábrica exactos de El Bajón.
     */
    public function reset(): RedirectResponse
    {
        $setting = AppSetting::first() ?? new AppSetting();

        // Eliminar archivos personalizados de almacenamiento
        if (!empty($setting->logo_path) && Storage::disk('public')->exists($setting->logo_path)) {
            Storage::disk('public')->delete($setting->logo_path);
        }
        if (!empty($setting->favicon_path) && Storage::disk('public')->exists($setting->favicon_path)) {
            Storage::disk('public')->delete($setting->favicon_path);
        }

        // Limpiar directorio de storage branding si quedó algún remanente
        try {
            $files = Storage::disk('public')->files('branding');
            foreach ($files as $file) {
                Storage::disk('public')->delete($file);
            }
        } catch (\Throwable $e) {
            // Ignorar si no existe
        }

        $setting->app_name = AppSetting::DEFAULT_APP_NAME;
        $setting->logo_path = null;
        $setting->favicon_path = null;
        $setting->primary_color = AppSetting::DEFAULT_PRIMARY_COLOR;
        $setting->secondary_color = AppSetting::DEFAULT_SECONDARY_COLOR;
        $setting->sidebar_color = AppSetting::DEFAULT_SIDEBAR_COLOR;
        $setting->font_family = AppSetting::DEFAULT_FONT_FAMILY;
        $setting->theme_mode = AppSetting::DEFAULT_THEME_MODE;
        $setting->save();

        Cache::forget('app_settings');
        AppSetting::clearSettingsCache();

        return redirect()->route('admin.branding.index')
            ->with('success', 'Configuración de marca restablecida a los valores de fábrica con éxito.');
    }
}
