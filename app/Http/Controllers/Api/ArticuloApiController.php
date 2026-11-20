<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articulo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArticuloApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Articulo::latest();

        if ($request->filled('q')) {
            $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower((string) $request->q) . '%'])
                ->orderBy('nombre', 'asc');
        }

        $articulos = $query->select(
            'id',
            'nombre',
            'precio',
            'stock',
            'disponible',
            'requiere_cocina',
            'categoria_cocina',
            'img_base64',
            'imagen_tipo'
        )
            ->orderBy('nombre', 'asc')
            ->get();

        return $this->success($articulos);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre'           => 'required|string|max:255',
                'descripcion'      => 'nullable|string',
                'precio'           => 'required|numeric|min:0',
                'stock'            => 'required|integer|min:0',
                'requiere_cocina'  => 'nullable|boolean',
                'categoria_cocina' => 'nullable|in:cemita,milanesa_pollo,milanesa_puerco,bebida,otro',
                'img_base64'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($request->hasFile('img_base64')) {
                $path = $request->file('img_base64')->store('articulos', 'public');
                $validated['img_base64'] = $path;
                $validated['imagen_tipo'] = $request->file('img_base64')->getMimeType();
            }

            if ($request->has('requiere_cocina') && !$request->boolean('requiere_cocina') && empty($validated['categoria_cocina'])) {
                $validated['requiere_cocina'] = false;
                $validated['categoria_cocina'] = Articulo::CATEGORIA_BEBIDA;
            }

            $articulo = Articulo::create($validated);

            return $this->success(
                $articulo,
                'Artículo creado con éxito',
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
                'Error al crear el artículo',
                500
            );
        }
    }

    public function show(Articulo $articulo): JsonResponse
    {
        return $this->success($articulo);
    }

    public function update(Request $request, Articulo $articulo): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre'           => 'required|string|max:255',
                'descripcion'      => 'nullable|string',
                'precio'           => 'required|numeric|min:0',
                'stock'            => 'required',
                'requiere_cocina'  => 'nullable|boolean',
                'categoria_cocina' => 'nullable|in:cemita,milanesa_pollo,milanesa_puerco,bebida,otro',
                'img_base64'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if (isset($validated['stock'])) {
                $validated['stock'] = (int) $articulo->stock + (int) $validated['stock'];
            }

            if ($request->hasFile('img_base64')) {
                if (!empty($articulo->img_base64) && Storage::disk('public')->exists($articulo->img_base64)) {
                    Storage::disk('public')->delete($articulo->img_base64);
                }

                $path = $request->file('img_base64')->store('articulos', 'public');
                $validated['img_base64'] = $path;
                $validated['imagen_tipo'] = $request->file('img_base64')->getMimeType();
            }

            if ($request->has('requiere_cocina') && !$request->boolean('requiere_cocina') && empty($validated['categoria_cocina'])) {
                $validated['requiere_cocina'] = false;
                $validated['categoria_cocina'] = Articulo::CATEGORIA_BEBIDA;
            }

            $articulo->update($validated);

            return $this->success(
                $articulo,
                'Artículo actualizado con éxito'
            );
        } catch (ValidationException $e) {
            return $this->error(
                'Error de validación',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al actualizar el artículo',
                500
            );
        }
    }

    public function destroy(Articulo $articulo): JsonResponse
    {
        try {
            if (!empty($articulo->img_base64) && Storage::disk('public')->exists($articulo->img_base64)) {
                Storage::disk('public')->delete($articulo->img_base64);
            }

            $articulo->delete();

            return $this->success(
                null,
                'Artículo eliminado con éxito'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al eliminar el artículo',
                500
            );
        }
    }
}
