<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $articulos = Articulo::comerciales()
            ->select([
                'id',
                'nombre',
                'descripcion',
                'precio',
                'stock',
                'disponible',
                'requiere_cocina',
                'categoria_cocina',
                'img_base64',
                'imagen_tipo',
                'created_at',
                'updated_at',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower((string) $request->q) . '%']);
            })
            ->orderBy('nombre', 'asc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.articulos._tabla', compact('articulos'))->render();
        }

        $clientes = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'cliente');
        })->whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['admin', 'vendedor', 'cocina']);
        })->orderBy('name')->get(['id', 'name']);

        return view('admin.articulos.index', compact('articulos', 'clientes'));
    }

    public function create(): View
    {
        return view('admin.articulos.create');
    }

    public function store(Request $request): RedirectResponse
    {
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

        $validated['disponible'] = ((int) $validated['stock'] > 0);
        $validated = $this->normalizeKitchenMetadata($validated, $request);

        Articulo::create($validated);

        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo creado con éxito.');
    }

    public function edit(Articulo $articulo): View
    {
        return view('admin.articulos.edit', compact('articulo'));
    }

    public function update(Request $request, Articulo $articulo): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255',
            'descripcion'      => 'nullable|string',
            'precio'           => 'required|numeric|min:0',
            'stock'            => 'required|integer',
            'requiere_cocina'  => 'nullable|boolean',
            'categoria_cocina' => 'nullable|in:cemita,milanesa_pollo,milanesa_puerco,bebida,otro',
            'img_base64'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (isset($validated['stock'])) {
            $validated['stock'] = max(0, (int) $articulo->stock + (int) $validated['stock']);
        }

        if ($request->hasFile('img_base64')) {
            if (!empty($articulo->img_base64) && Storage::disk('public')->exists($articulo->img_base64)) {
                Storage::disk('public')->delete($articulo->img_base64);
            }

            $path = $request->file('img_base64')->store('articulos', 'public');
            $validated['img_base64'] = $path;
            $validated['imagen_tipo'] = $request->file('img_base64')->getMimeType();
        }

        if (isset($validated['stock'])) {
            $validated['disponible'] = ((int) $validated['stock'] > 0);
        }

        $validated = $this->normalizeKitchenMetadata($validated, $request, $articulo);

        $articulo->update($validated);

        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo actualizado con éxito.');
    }

    /**
     * Normaliza de forma consistente requiere_cocina y categoria_cocina en creación y actualización.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function normalizeKitchenMetadata(array $validated, Request $request, ?Articulo $existing = null): array
    {
        $hasRequiere = $request->has('requiere_cocina');
        $hasCategoria = $request->filled('categoria_cocina');

        if (!$hasRequiere && !$hasCategoria) {
            if ($existing) {
                $validated['requiere_cocina'] = (bool) $existing->requiere_cocina;
                $validated['categoria_cocina'] = $existing->categoria_cocina ?? Articulo::CATEGORIA_OTRO;
                return $validated;
            }

            $inferred = Articulo::inferirMetadatosCocina(
                (string) ($validated['nombre'] ?? ''),
                (string) ($validated['descripcion'] ?? '')
            );
            $validated['requiere_cocina'] = $inferred['requiere_cocina'];
            $validated['categoria_cocina'] = $inferred['categoria_cocina'];

            return $validated;
        }

        $categoria = $hasCategoria
            ? (string) $validated['categoria_cocina']
            : null;

        if ($hasRequiere) {
            $requiereCocina = $request->boolean('requiere_cocina');
        } elseif ($categoria === Articulo::CATEGORIA_BEBIDA) {
            $requiereCocina = false;
        } else {
            $requiereCocina = $existing ? (bool) $existing->requiere_cocina : true;
        }

        if ($requiereCocina === false) {
            // Si no requiere cocina y no se envió categoría (o se envió una categoría de cemita/milanesa), normalizar a bebida u otro
            if ($categoria === null || in_array($categoria, [
                Articulo::CATEGORIA_CEMITA,
                Articulo::CATEGORIA_MILANESA_POLLO,
                Articulo::CATEGORIA_MILANESA_PUERCO,
            ], true)) {
                $categoria = Articulo::CATEGORIA_BEBIDA;
            }
        } else {
            if ($categoria === null) {
                $inferred = Articulo::inferirMetadatosCocina(
                    (string) ($validated['nombre'] ?? ''),
                    (string) ($validated['descripcion'] ?? '')
                );
                $categoria = $inferred['categoria_cocina'] === Articulo::CATEGORIA_BEBIDA
                    ? Articulo::CATEGORIA_OTRO
                    : $inferred['categoria_cocina'];
            }
        }

        $validated['requiere_cocina'] = $requiereCocina;
        $validated['categoria_cocina'] = $categoria;

        return $validated;
    }

    public function destroy(Articulo $articulo): RedirectResponse
    {
        if (!empty($articulo->img_base64) && Storage::disk('public')->exists($articulo->img_base64)) {
            Storage::disk('public')->delete($articulo->img_base64);
        }

        $articulo->delete();
        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo eliminado con éxito.');
    }

    /**
     * Sirve la imagen del artículo desde Storage con caché HTTP o redirige a placeholder.
     */
    public function imagen(Articulo $articulo)
    {
        if (!empty($articulo->img_base64)) {
            if (Storage::disk('public')->exists($articulo->img_base64)) {
                return Storage::disk('public')->response($articulo->img_base64, null, [
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            }

            if (strlen($articulo->img_base64) > 100) {
                $imageData = base64_decode($articulo->img_base64);
                $mimeType  = $articulo->imagen_tipo ?? 'image/jpeg';
                return response($imageData, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Cache-Control', 'public, max-age=3600');
            }
        }

        $defaultPath = public_path('img/default_food.svg');
        if (file_exists($defaultPath)) {
            return response()->file($defaultPath, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        abort(404);
    }

    public function toggleDisponible(Articulo $articulo)
    {
        $articulo->disponible = !$articulo->disponible;
        $articulo->save();

        return response()->json([
            'success' => true,
            'disponible' => $articulo->disponible
        ]);
    }

    public function bulkDisponible(Request $request)
    {
        $status = $request->input('status') == 1;

        if ($status) {
            Articulo::where('stock', '>', 0)->update(['disponible' => true]);
            $mensaje = 'Se habilitaron todos los artículos con stock disponible.';
        } else {
            Articulo::query()->update(['disponible' => false]);
            $mensaje = 'Se deshabilitaron todos los artículos.';
        }

        return redirect()->route('admin.articulos.index')->with('success', $mensaje);
    }
}
