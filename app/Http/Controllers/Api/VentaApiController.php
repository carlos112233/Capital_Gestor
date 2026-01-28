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

        $ventasQuery = Venta::with(['user', 'articulo', 'cliente'])->latest();

        // Si no es admin, solo ver sus ventas
        if (! $user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        // Filtro por nombre de usuario
        if ($request->filled('q')) {
            $ventasQuery->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            });
        }

        $ventas = $ventasQuery->paginate(20);

        return $this->success($ventas);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'articulo_id' => 'required|exists:articulos,id',
                'cantidad'    => 'required|integer|min:1',
                'cliente_id'  => 'nullable|exists:users,id',
                'descripcion' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated) {
                $articulo = Articulo::lockForUpdate()->find($validated['articulo_id']);

                if ($articulo->stock < $validated['cantidad']) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'No hay suficiente stock. Disponible: ' . $articulo->stock,
                    ]);
                }

                // Disminuir stock
                $articulo->decrement('stock', $validated['cantidad']);

                // Crear venta
                Venta::create([
                    'user_id'      => Auth::user()->hasRole('admin')
                        ? $validated['cliente_id']
                        : Auth::id(),
                    'articulo_id'  => $articulo->id,
                    'cantidad'     => $validated['cantidad'],
                    'precio_venta' => $articulo->precio,
                    'total_venta'  => $articulo->precio * $validated['cantidad'],
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);
            });

            return $this->success(
                null,
                'Venta registrada con éxito',
                201
            );

        } catch (ValidationException $e) {
            return $this->error(
                'Error de validación',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al procesar la venta',
                500
            );
        }
    }

    public function show(Venta $venta)
    {
        return $this->success(
            $venta->load(['user', 'articulo', 'cliente'])
        );
    }

    public function update(Request $request, Venta $venta)
    {
        try {
            $validated = $request->validate([
                'articulo_id' => 'required|exists:articulos,id',
                'cantidad'    => 'required|integer|min:1',
                'cliente_id'  => 'nullable|exists:users,id',
                'descripcion' => 'nullable|string',
            ]);

            DB::transaction(function () use ($validated, $venta) {
                $articulo = Articulo::lockForUpdate()->find($validated['articulo_id']);

                $diferenciaStock = $validated['cantidad'] - $venta->cantidad;

                if ($diferenciaStock > 0 && $articulo->stock < $diferenciaStock) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'No hay suficiente stock adicional. Disponible: ' . $articulo->stock,
                    ]);
                }

                // Ajustar stock
                $articulo->decrement('stock', $diferenciaStock);

                $venta->update([
                    'user_id'      => Auth::user()->hasRole('admin')
                        ? $validated['cliente_id']
                        : $venta->user_id,
                    'articulo_id'  => $validated['articulo_id'],
                    'cantidad'     => $validated['cantidad'],
                    'precio_venta' => $articulo->precio,
                    'total_venta'  => $articulo->precio * $validated['cantidad'],
                    'descripcion'  => $validated['descripcion'],
                ]);
            });

            return $this->success(
                $venta->fresh(),
                'Venta actualizada con éxito'
            );

        } catch (ValidationException $e) {
            return $this->error(
                'Error de validación',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al actualizar la venta',
                500
            );
        }
    }

    public function destroy(Venta $venta)
    {
        try {
            DB::transaction(function () use ($venta) {
                $articulo = $venta->articulo()->lockForUpdate()->first();

                // Regresar stock
                $articulo->increment('stock', $venta->cantidad);

                $venta->delete();
            });

            return $this->success(
                null,
                'Venta eliminada y stock actualizado correctamente'
            );

        } catch (\Exception $e) {
            return $this->error(
                'Error al eliminar la venta',
                500
            );
        }
    }
}
