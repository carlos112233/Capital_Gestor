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
                'cliente:id,name',
                'articulo:id,nombre,precio'
            ]);

        // 2. Filtro de seguridad
        if (!$user->hasRole('admin')) {
            $entradasQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('cliente_id', $user->id);
            });
        }

        // 3. Búsqueda optimizada para POSTGRESQL
        if ($request->filled('q')) {
            $search = $request->input('q');

            $entradasQuery->where(function ($q) use ($search) {
                $q->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'ILIKE', "%$search%");
                })->orWhereHas('cliente', function ($query) use ($search) {
                    $query->where('name', 'ILIKE', "%$search%");
                })->orWhereHas('articulo', function ($query) use ($search) {
                    $query->where('nombre', 'ILIKE', "%$search%");
                })->orWhere('descripcion', 'ILIKE', "%$search%");
            });
        }

        // 4. Ordenar y limitar
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
                'precio_venta' => 'required|numeric',
                'descripcion'  => 'nullable|string|max:1000',
            ]);

            // 2. Transacción y Creación
            $entrada = DB::transaction(function () use ($validated, $request) {
                $clienteId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
                    ? $validated['cliente_id']
                    : Auth::id();

                $data = [
                    'articulo_id'    => $validated['articulo_id'],
                    'user_id'        => Auth::id(),
                    'precio_venta'   => $validated['precio_venta'],
                    'descripcion'    => $validated['descripcion'] ?? null,
                    'fecha_generado' => Carbon::now(),
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('entradas', 'cliente_id')) {
                    $data['cliente_id'] = $clienteId;
                }

                return Entrada::create($data);
            });

            // 3. Respuesta Exitosa
            return $this->success(
                $entrada->load(['user', 'cliente', 'articulo']),
                'Entrada de capital registrada con éxito.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            \Log::error("Error en EntradaApiController@store: " . $e->getMessage());

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
        if (!$user->hasRole('admin') && $entrada->user_id !== $user->id && $entrada->cliente_id !== $user->id) {
            return $this->error('No autorizado para ver esta entrada', 403);
        }

        return $this->success($entrada->load(['user', 'cliente', 'articulo']));
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
                'precio_venta' => 'required|numeric',
                'descripcion'  => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $entrada) {
                $clienteId = Auth::user()->hasRole('admin')
                    ? ($validated['cliente_id'] ?? $entrada->cliente_id ?? Auth::id())
                    : ($entrada->cliente_id ?? Auth::id());

                $data = [
                    'articulo_id'    => $validated['articulo_id'],
                    'precio_venta'   => $validated['precio_venta'],
                    'descripcion'    => $validated['descripcion'],
                    'fecha_generado' => Carbon::now(),
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('entradas', 'cliente_id')) {
                    $data['cliente_id'] = $clienteId;
                }

                $entrada->update($data);
            });

            return $this->success($entrada->fresh(['user', 'cliente', 'articulo']), 'Entrada actualizada correctamente.');

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
