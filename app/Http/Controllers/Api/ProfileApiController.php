<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfileApiController extends Controller
{
    use ApiResponse;

    /**
     * Obtener perfil del usuario autenticado
     */
    public function show(Request $request)
    {
        return $this->success(
            $request->user()
        );
    }

    /**
     * Actualizar perfil del usuario
     */
    public function update(ProfileUpdateRequest $request)
    {
        try {
            $user = $request->user();

            $user->fill($request->validated());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $this->success(
                $user,
                'Perfil actualizado correctamente'
            );

        } catch (ValidationException $e) {
            return $this->error(
                'Error de validación',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al actualizar el perfil',
                500
            );
        }
    }

    /**
     * Eliminar cuenta del usuario
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            // Revocar tokens
            $user->tokens()->delete();

            Auth::logout();

            $user->delete();

            return $this->success(
                null,
                'Cuenta eliminada correctamente'
            );

        } catch (ValidationException $e) {
            return $this->error(
                'Credenciales incorrectas',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al eliminar la cuenta',
                500
            );
        }
    }
}
