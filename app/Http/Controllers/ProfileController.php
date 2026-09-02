<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
        $request->user()->fill($request->validated());

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $request->user()->image = base64_encode(file_get_contents($file->getRealPath()));
            $request->user()->image_tipo = $file->getMimeType();
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Show the user profile image or default avatar
     */
    public function showImage($id)
    {
        $user = \App\Models\User::select('image', 'image_tipo')->find($id);

        if ($user && !empty($user->image) && strlen($user->image) > 100) {
            $image = base64_decode($user->image);
            return response($image, 200)
                ->header('Content-Type', $user->image_tipo ?? 'image/jpeg')
                ->header('Cache-Control', 'max-age=86400, public');
        }

        $defaultPath = public_path('img/default_user.svg');
        if (file_exists($defaultPath)) {
            return response()->file($defaultPath, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'max-age=86400, public'
            ]);
        }

        abort(404);
    }
}
