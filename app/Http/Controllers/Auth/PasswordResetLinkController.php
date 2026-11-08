<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request via WhatsApp.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
        ]);

        $rawInput = trim((string) $request->input('email'));
        $digitsOnly = preg_replace('/[^0-9]/', '', $rawInput);

        // Buscar al usuario por correo o por teléfono
        $user = User::where(function ($query) use ($rawInput, $digitsOnly) {
            $query->where('email', strtolower($rawInput));

            if (!empty($digitsOnly)) {
                $query->orWhere('telefono', $rawInput)
                      ->orWhere('telefono', $digitsOnly);

                // Si son 10 dígitos (México), probar también con 521 o sin él
                if (strlen($digitsOnly) === 10) {
                    $query->orWhere('telefono', '521' . $digitsOnly);
                } elseif (strlen($digitsOnly) === 12 && str_starts_with($digitsOnly, '52')) {
                    $query->orWhere('telefono', substr($digitsOnly, 2));
                } elseif (strlen($digitsOnly) === 13 && str_starts_with($digitsOnly, '521')) {
                    $query->orWhere('telefono', substr($digitsOnly, 3));
                }
            }
        })->first();

        if (!$user || empty($user->telefono)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'No encontramos un usuario con esos datos o la cuenta no tiene un número de teléfono/WhatsApp asociado. Contacta a soporte.',
                ]);
        }

        // Sanitizar y formatear el número para WhatsApp
        $phone = preg_replace('/[^0-9]/', '', (string) $user->telefono);
        if (strlen($phone) === 10) {
            $phone = '521' . $phone;
        }

        // Generar contraseña temporal segura
        $tempPassword = 'Bajon-' . rand(1000, 9999);

        // Actualizar la contraseña del usuario en la base de datos
        $user->update([
            'password' => Hash::make($tempPassword),
        ]);

        $appName = AppSetting::getSettings()->app_name ?? config('app.name', 'El Bajón');

        // Construir mensaje oficial
        $mensaje = "🔐 *RECUPERACIÓN DE ACCESO - {$appName}*\n\n" .
                   "Hola *{$user->name}*,\n" .
                   "Hemos generado una contraseña temporal para que puedas restablecer tu acceso:\n\n" .
                   "🔑 *Nueva Contraseña Temporal:* `{$tempPassword}`\n\n" .
                   "🌐 *Iniciar sesión aquí:* " . url('/login') . "\n\n" .
                   "⚠️ *Recomendación:* Por tu seguridad, cambia esta contraseña en tu perfil en cuanto ingreses a la plataforma.";

        // Encolar mensaje en WhatsAppNotificationService
        app(WhatsAppNotificationService::class)->enqueuePayload([
            'numero'     => $phone,
            'mensaje'    => $mensaje,
            'status'     => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('login')
            ->with('status', '¡Listo! Te enviamos una contraseña temporal por WhatsApp para que puedas iniciar sesión de inmediato.');
    }
}
