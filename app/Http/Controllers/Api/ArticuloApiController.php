<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Articulo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ArticuloApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $articulosCollection = Articulo::latest()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('nombre', 'like', '%' . $request->q . '%');
            })
            ->get()
            ->sortBy('nombre')
            ->values();

        // paginación manual (se conserva tu lógica)
        $page = (int) $request->get('page', 1);
        $perPage = 10;

        $items = $articulosCollection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $articulos = new LengthAwarePaginator(
            $items,
            $articulosCollection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        return $this->success($articulos);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if ($request->hasFile('img_base64')) {
                $imagenBinaria = file_get_contents(
                    $request->file('img_base64')->getRealPath()
                );

                $request->merge([
                    'img_base64' => base64_encode($imagenBinaria),
                    'img_tipo'   => $request->file('img_base64')->getMimeType(),
                ]);
            }

            $validated = $request->validate([
                'nombre'      => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio'      => 'required|numeric|min:0',
                'stock'       => 'required|integer|min:0',
                'img_base64'  => 'nullable|string',
                'img_tipo'    => 'nullable|string',
            ]);

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
                'nombre'      => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio'      => 'required|numeric|min:0',
                'stock'       => 'required|integer|min:0',
                'img_base64'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // suma de stock (misma lógica que web)
            if (isset($validated['stock'])) {
                $validated['stock'] = $articulo->stock + $validated['stock'];
            }

            if ($request->hasFile('img_base64')) {
                $file = $request->file('img_base64');
                $validated['img_base64'] = base64_encode(
                    file_get_contents($file->getRealPath())
                );
                $validated['img_tipo'] = $file->getMimeType();
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
