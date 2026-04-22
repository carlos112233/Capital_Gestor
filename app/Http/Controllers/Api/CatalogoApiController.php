<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articulo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogoApiController extends Controller
{
    use ApiResponse;

    /**
     * Listar artículos disponibles en catálogo
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Usamos query() para mayor claridad
        $query = Articulo::query()
            ->where('nombre', '!=', 'Saldar pago')
            ->where('stock', '>=', 1);

        // 2. Ajuste para PostgreSQL: ILIKE
        // En Postgres, 'like' es sensible a mayúsculas. 'ilike' es la versión rápida que ignora mayúsculas.
        if ($request->filled('q')) {
            $query->where('nombre', 'ilike', '%' . $request->q . '%');
        }

        // 3. Ejecución ultra ligera y ordenada
        $articulos = $query->select('id', 'nombre', 'precio', 'stock', 'img_base64')
            ->orderBy('nombre', 'asc') // Siempre ordena para que tu App se vea profesional
            ->toBase() // Sigue usando esto, es genial para ahorrar los 512MB de RAM de Render
            ->get();

        return $this->success($articulos);
    }

    /**
     * Procesar venta de artículo
     */
    public function vender(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'articulo_id'  => 'required|exists:articulos,id',
            'cantidad'     => 'required|integer|min:1',
            'cliente_id'   => 'required|exists:clientes,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion'  => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $articulo = Articulo::lockForUpdate()->find($validated['articulo_id']);
                $cantidadVenta = $validated['cantidad'];

                if ($articulo->stock < $cantidadVenta) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'No hay suficiente stock. Disponible: ' . $articulo->stock,
                    ]);
                }

                // Descontar stock
                $articulo->decrement('stock', $cantidadVenta);

                // Registrar venta
                $articulo->ventas()->create([
                    'user_id'      => Auth::id(),
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $validated['precio_venta'],
                    'total_venta'  => $validated['precio_venta'] * $cantidadVenta,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);
            });
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

        return $this->success(
            null,
            'Venta registrada con éxito'
        );
    }
}
