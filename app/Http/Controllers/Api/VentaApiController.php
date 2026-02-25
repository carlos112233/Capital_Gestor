<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Venta;
use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = Auth::user();
        $ventasQuery = Venta::with(['user', 'articulo'])->latest();

        if (!$user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $ventasQuery->whereHas('user', function ($query) use ($search) {
                // CAMBIO POSTGRES: 'ILIKE' para búsqueda insensible a mayúsculas y acentos
                $query->where('name', 'SIMILAR TO', '%TREASURE%');
            });
        }

        return $this->success($ventasQuery->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad'    => 'required|integer|min:1',
            'cliente_id'  => 'nullable|exists:users,id',
            'descripcion' => 'nullable|string',
        ]);

        try {
            // CAMBIO POSTGRES: Envolviendo el retorno en la transacción para consistencia
            $venta = DB::transaction(function () use ($validated) {
                $articulo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                $cantidad = (int) $validated['cantidad'];

                if ($articulo->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente. Disponible: {$articulo->stock}",
                    ]);
                }

                $articulo->decrement('stock', $cantidad);

                // Cálculo explícito de totales para evitar errores de precisión decimal
                $total = (float) ($articulo->precio * $cantidad);

                return Venta::create([
                    'user_id'      => Auth::user()->hasRole('admin') ? ($validated['cliente_id'] ?? Auth::id()) : Auth::id(),
                    'articulo_id'  => $articulo->id,
                    'cantidad'     => $cantidad,
                    'precio_venta' => $articulo->precio,
                    'total_venta'  => $total,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);
            });

            return $this->success($venta, 'Venta registrada con éxito', 201);
        } catch (ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            \Log::error("Postgres Store Error: " . $e->getMessage());
            return $this->error('Error al procesar la venta.', 500);
        }
    }

    public function show(Venta $venta)
    {
        if (!Auth::user()->hasRole('admin') && $venta->user_id !== Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        return $this->success($venta->load(['user', 'articulo']));
    }

    public function update(Request $request, Venta $venta)
    {
        if (!Auth::user()->hasRole('admin') && $venta->user_id !== Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad'    => 'required|integer|min:1',
            'cliente_id'  => 'nullable|exists:users,id',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $updatedVenta = DB::transaction(function () use ($validated, $venta) {
                // 1. Bloquear y devolver stock al artículo original
                $articuloAnterior = Articulo::lockForUpdate()->findOrFail($venta->articulo_id);
                $articuloAnterior->increment('stock', $venta->cantidad);

                // 2. Bloquear artículo nuevo y validar
                $articuloNuevo = Articulo::lockForUpdate()->findOrFail($validated['articulo_id']);
                $nuevaCantidad = (int) $validated['cantidad'];
                
                if ($articuloNuevo->stock < $nuevaCantidad) {
                    throw ValidationException::withMessages([
                        'cantidad' => "Stock insuficiente en el producto seleccionado. Disponible: {$articuloNuevo->stock}",
                    ]);
                }

                // 3. Restar stock
                $articuloNuevo->decrement('stock', $nuevaCantidad);

                // 4. Actualizar
                $total = (float) ($articuloNuevo->precio * $nuevaCantidad);
                
                $venta->update([
                    'user_id'      => Auth::user()->hasRole('admin') ? ($validated['cliente_id'] ?? $venta->user_id) : $venta->user_id,
                    'articulo_id'  => $articuloNuevo->id,
                    'cantidad'     => $nuevaCantidad,
                    'precio_venta' => $articuloNuevo->precio,
                    'total_venta'  => $total,
                    'descripcion'  => $validated['descripcion'],
                ]);

                return $venta;
            });

            return $this->success($updatedVenta->fresh(), 'Venta actualizada con éxito');
        } catch (ValidationException $e) {
            return $this->error('Error de validación', 422, $e->errors());
        } catch (\Exception $e) {
            \Log::error("Postgres Update Error: " . $e->getMessage());
            return $this->error('Error al actualizar la venta.', 500);
        }
    }

    public function destroy(Venta $venta)
    {
        if (!Auth::user()->hasRole('admin') && $venta->user_id !== Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        try {
            DB::transaction(function () use ($venta) {
                // CAMBIO POSTGRES: Usar findOrFail para asegurar el bloqueo en la transacción
                $articulo = Articulo::lockForUpdate()->find($venta->articulo_id);
                if ($articulo) {
                    $articulo->increment('stock', (int) $venta->cantidad);
                }
                $venta->delete();
            });

            return $this->success(null, 'Venta eliminada y stock restaurado');
        } catch (\Exception $e) {
            \Log::error("Postgres Delete Error: " . $e->getMessage());
            return $this->error('Error al eliminar', 500);
        }
    }
}