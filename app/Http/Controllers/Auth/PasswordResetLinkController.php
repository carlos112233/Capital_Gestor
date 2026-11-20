<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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

        // Asegurar correo válido antes de emitir el token criptográfico
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $user->forceFill([
                'email' => "usuario_{$user->id}@elbajon.store",
            ])->save();
        }

        // Sanitizar y formatear el número para WhatsApp
        $phone = preg_replace('/[^0-9]/', '', (string) $user->telefono);
        if (strlen($phone) === 10) {
            $phone = '521' . $phone;
        } elseif (strlen($phone) === 12 && str_starts_with($phone, '52')) {
            $phone = '521' . substr($phone, 2);
        }

        // Generar token criptográfico estándar sin mutar la contraseña actual del usuario
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));
        $expireMinutes = (int) config('auth.passwords.users.expire', 15);
        $appName = AppSetting::getSettings()->app_name ?? config('app.name', 'El Bajón');

        // Construir mensaje oficial únicamente con el enlace seguro de recuperación
        $mensaje = "🔐 *RECUPERACIÓN DE ACCESO - {$appName}*\n\n" .
                   "Hola *{$user->name}*,\n" .
                   "Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace seguro para definir una nueva:\n\n" .
                   "🔗 *Enlace de restablecimiento:* {$resetUrl}\n\n" .
                   "⏳ *Vigencia:* Este enlace expirará en {$expireMinutes} minutos.\n" .
                   "⚠️ Si tú no solicitaste este cambio, ignora este mensaje; tu contraseña actual sigue siendo válida.";

        // Encolar mensaje en WhatsAppNotificationService
        app(WhatsAppNotificationService::class)->enqueuePayload([
            'numero'     => $phone,
            'mensaje'    => $mensaje,
            'status'     => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('login')
            ->with('status', '¡Listo! Te enviamos un enlace seguro de restablecimiento por WhatsApp con vigencia de ' . $expireMinutes . ' minutos.');
    }
}
