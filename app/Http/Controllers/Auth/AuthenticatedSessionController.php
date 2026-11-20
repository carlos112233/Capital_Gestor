<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request using LoginRequest (compound account + IP rate limiting).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Evitar que Laravel redirija a endpoints de API / JSON / wa-status guardados en sesión
        $intended = redirect()->getIntendedUrl();
        if ($intended && (str_contains($intended, 'wa-status') || str_contains($intended, 'api') || str_contains($intended, 'json'))) {
            $request->session()->forget('url.intended');
            $intended = null;
        }

        if ($intended) {
            return redirect()->intended($user->hasRole('admin') ? route('dashboardAdmin', [], false) : route('dashboard', [], false));
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('dashboardAdmin');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
