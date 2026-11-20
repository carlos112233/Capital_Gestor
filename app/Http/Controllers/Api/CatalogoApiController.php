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

        // 3. Ejecución ultra ligera y ordenada con metadatos estructurados de cocina
        $articulos = $query->select(
            'id',
            'nombre',
            'precio',
            'stock',
            'disponible',
            'requiere_cocina',
            'categoria_cocina',
            'img_base64'
        )
            ->orderBy('nombre', 'asc')
            ->toBase()
            ->get()
            ->map(function ($art) {
                $art->disponible = (bool) $art->disponible;
                $art->requiere_cocina = (bool) $art->requiere_cocina;
                return $art;
            });

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
            'descripcion'  => 'nullable|string',
        ]);

        try {
            app(\App\Actions\Pos\RegistrarVentaAction::class)->executeSingle([
                'articulo_id' => $validated['articulo_id'],
                'cantidad'    => $validated['cantidad'],
                'descripcion' => $validated['descripcion'] ?? null,
            ], Auth::user());
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
