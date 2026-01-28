<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entrada;
use App\Models\Articulo;
use App\Models\User;
use App\Traits\ApiResponse; // Asegúrate de que el trait esté en esta ruta
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntradaApiController extends Controller
{
    use ApiResponse;

    /**
     * Muestra una lista de las entradas con filtros.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Iniciamos la consulta con la relación del usuario
        $entradasQuery = Entrada::with(['user', 'articulo'])->latest();

        // Filtro de seguridad: Si no es admin, solo ve sus propias entradas
        if (!$user->hasRole('admin')) {
            $entradasQuery->where('user_id', $user->id);
        }

        // Filtro por búsqueda (nombre de usuario)
        if ($request->filled('q')) {
            $search = $request->input('q');
            $entradasQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $entradas = $entradasQuery->paginate(15);

        return $this->success($entradas);
    }

    /**
     * Guarda una nueva entrada.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'articulo_id'  => 'required|exists:articulos,id',
                'cliente_id'   => 'nullable|exists:users,id',
                'precio_venta' => 'required|integer',
                'descripcion'  => 'nullable|string|max:1000',
            ]);

            $entrada = DB::transaction(function () use ($validated) {
                // Determinar a quién pertenece la entrada
                $userId = Auth::user()->hasRole('admin') 
                    ? ($validated['cliente_id'] ?? Auth::id()) 
                    : Auth::id();

                return Entrada::create([
                    'articulo_id'    => $validated['articulo_id'],
                    'user_id'        => $userId,
                    'precio_venta'   => $validated['precio_venta'],
                    'descripcion'    => $validated['descripcion'] ?? null,
                    'fecha_generado' => Carbon::now(),
                ]);
            });

            return $this->success(
                $entrada->load(['user', 'articulo']),
                'Entrada de capital registrada con éxito.',
                201
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Error al registrar la entrada: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Muestra el detalle de una entrada específica.
     */
    public function show(Entrada $entrada)
    {
        $user = Auth::user();

        // Verificar que el usuario tenga permiso para ver esta entrada
        if (!$user->hasRole('admin') && $entrada->user_id !== $user->id) {
            return $this->error('No autorizado para ver esta entrada', 403);
        }

        return $this->success($entrada->load(['user', 'articulo']));
    }

    /**
     * Actualiza una entrada existente.
     */
    public function update(Request $request, Entrada $entrada)
    {
        try {
            $validated = $request->validate([
                'articulo_id'  => 'required|exists:articulos,id',
                'cliente_id'   => 'nullable|exists:users,id',
                'precio_venta' => 'required|integer',
                'descripcion'  => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $entrada) {
                $userId = Auth::user()->hasRole('admin') 
                    ? ($validated['cliente_id'] ?? $entrada->user_id) 
                    : Auth::id();

                $entrada->update([
                    'articulo_id'    => $validated['articulo_id'],
                    'user_id'        => $userId,
                    'precio_venta'   => $validated['precio_venta'],
                    'descripcion'    => $validated['descripcion'],
                    'fecha_generado' => Carbon::now(), // O mantener la original si prefieres
                ]);
            });

            return $this->success($entrada->fresh(['user', 'articulo']), 'Entrada actualizada correctamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Error al actualizar la entrada', 500);
        }
    }

    /**
     * Elimina una entrada.
     */
    public function destroy(Entrada $entrada)
    {
        try {
            $entrada->delete();
            return $this->success(null, 'Entrada eliminada correctamente.');
        } catch (\Exception $e) {
            return $this->error('Error al eliminar la entrada', 500);
        }
    }
}