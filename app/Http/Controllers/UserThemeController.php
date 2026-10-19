<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserThemeController extends Controller
{
    /**
     * Muestra la vista de personalización individual de apariencia.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        // Si es un administrador, redirigir a la personalización institucional de marca
        if ($user && $user->hasRole('admin')) {
            return redirect()->route('admin.branding.index');
        }

        return view('profile.theme', [
            'user' => $user,
        ]);
    }

    /**
     * Actualiza las preferencias visuales del usuario autenticado.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->hasRole('admin')) {
            return redirect()->route('admin.branding.index');
        }

        $validated = $request->validate([
            'theme_mode' => ['required', 'string', 'in:light,dark,system'],
            'theme_color' => ['nullable', 'string', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
        ], [
            'theme_mode.required' => 'El modo de visualización es obligatorio.',
            'theme_mode.in' => 'El modo seleccionado no es válido.',
            'theme_color.regex' => 'El color de acento debe ser un código hexadecimal válido (ej. #6366f1).',
        ]);

        $user->update([
            'theme_mode' => $validated['theme_mode'],
            'theme_color' => !empty($validated['theme_color']) ? strtolower($validated['theme_color']) : null,
        ]);

        return redirect()->route('theme.index')
            ->with('status', 'theme-updated')
            ->with('success', '¡Tus preferencias de apariencia se han guardado correctamente!');
    }

    /**
     * Restablece el tema a los valores institucionales del negocio.
     */
    public function reset(): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->hasRole('admin')) {
            return redirect()->route('admin.branding.index');
        }

        $user->update([
            'theme_color' => null,
            'theme_mode' => 'system',
        ]);

        return redirect()->route('theme.index')
            ->with('status', 'theme-reset')
            ->with('success', 'Has restablecido tu apariencia al tema institucional.');
    }
}
