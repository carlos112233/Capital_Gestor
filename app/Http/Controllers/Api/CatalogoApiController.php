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
            ->where('nombre', '!=', 'Pago saldado')
            ->where('stock', '>=', 1);

        // 2. Búsqueda compatible con MySQL y PostgreSQL
        if ($request->filled('q')) {
            $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($request->q) . '%']);
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
            'precio_venta' => 'nullable|numeric|min:0',
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

                // Blindaje de precio maestro: por defecto precio de catálogo
                $precioUnitario = (float) $articulo->precio;
                if (Auth::user()->hasRole('admin') && isset($validated['precio_venta']) && is_numeric($validated['precio_venta'])) {
                    $precioUnitario = (float) $validated['precio_venta'];
                }
                $totalVenta = $precioUnitario * $cantidadVenta;

                // Registrar venta
                $venta = $articulo->ventas()->create([
                    'user_id'      => Auth::id(),
                    'comprador_id' => Auth::id(),
                    'cantidad'     => $cantidadVenta,
                    'precio_venta' => $precioUnitario,
                    'total_venta'  => $totalVenta,
                    'descripcion'  => $validated['descripcion'] ?? null,
                ]);

                DB::afterCommit(function () use ($venta): void {
                    \App\Models\Venta::notificarAdminWhatsApp($venta);
                });
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
