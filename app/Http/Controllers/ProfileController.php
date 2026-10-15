<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($request->hasFile('image')) {
            // Eliminar avatar anterior en disco físico si existe
            if (!empty($user->image) && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $path = $request->file('image')->store('avatars', 'public');
            $user->image = $path;
            $user->image_tipo = $request->file('image')->getMimeType();
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Eliminar avatar físico al borrar la cuenta
        if (!empty($user->image) && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Sirve la imagen de perfil del usuario desde Storage con caché HTTP o fallback a avatar predeterminado.
     */
    public function showImage(int|string $id)
    {
        $user = User::select('id', 'image', 'image_tipo')->find($id);

        if ($user && !empty($user->image)) {
            if (Storage::disk('public')->exists($user->image)) {
                return Storage::disk('public')->response($user->image, null, [
                    'Cache-Control' => 'max-age=86400, public',
                ]);
            }

            if (strlen($user->image) > 100) {
                $image = base64_decode($user->image);
                return response($image, 200)
                    ->header('Content-Type', $user->image_tipo ?? 'image/jpeg')
                    ->header('Cache-Control', 'max-age=86400, public');
            }
        }

        $defaultPath = public_path('img/default_user.svg');
        if (file_exists($defaultPath)) {
            return response()->file($defaultPath, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'max-age=86400, public',
            ]);
        }

        abort(404);
    }
}
