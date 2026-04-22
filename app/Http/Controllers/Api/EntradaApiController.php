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

        // 1. Usamos 'query()' y limitamos las columnas para ahorrar RAM en Render
        $entradasQuery = Entrada::query()
            ->with([
                'user:id,name',
                'articulo:id,nombre,precio' // Solo trae lo que vas a mostrar
            ]);

        // 2. Filtro de seguridad
        if (!$user->hasRole('admin')) {
            $entradasQuery->where('user_id', $user->id);
        }

        // 3. Búsqueda optimizada para POSTGRESQL
        if ($request->filled('q')) {
            $search = $request->input('q');

            // En Postgres, existe 'ILIKE', que es mucho más rápido que LOWER()
            // porque permite a la DB usar índices si están bien configurados.
            $entradasQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%$search%");
            });
        }

        // 4. Ordenar y limitar
        // latest() en Postgres es rápido si tienes índice en created_at
        $entradas = $entradasQuery->latest()->limit(40)->get();

        return $this->success($entradas);
    }

    /**
     * Guarda una nueva entrada.
     */
    public function store(Request $request)
    {
        try {
            // 1. Validación
            $validated = $request->validate([
                'articulo_id'  => 'required|exists:articulos,id',
                'cliente_id'   => 'nullable|exists:users,id',
                'precio_venta' => 'required|integer',
                'descripcion'  => 'nullable|string|max:1000',
            ]);

            // 2. Transacción y Creación
            $entrada = DB::transaction(function () use ($validated, $request) {

                // Determinar el ID del usuario: 
                // Si es admin y envió cliente_id, usamos ese. Si no, el del usuario autenticado.
                $userId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
                    ? $validated['cliente_id']
                    : Auth::id();

                return Entrada::create([
                    'articulo_id'    => $validated['articulo_id'],
                    'user_id'        => $userId,
                    'precio_venta'   => $validated['precio_venta'],
                    'descripcion'    => $validated['descripcion'] ?? null,
                    'fecha_generado' => Carbon::now(),
                ]);
            });

            // 3. Respuesta Exitosa
            return $this->success(
                $entrada->load(['user', 'articulo']),
                'Entrada de capital registrada con éxito.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            // Loguear el error es buena práctica para debuguear
            \Log::error("Error en EntradaController@store: " . $e->getMessage());

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
