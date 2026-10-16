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
            ->select(['id', 'nombre', 'descripcion', 'precio', 'stock', 'disponible', 'img_base64', 'imagen_tipo', 'created_at', 'updated_at'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($request->q) . '%']);
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
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'img_base64'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('img_base64')) {
            $path = $request->file('img_base64')->store('articulos', 'public');
            $validated['img_base64'] = $path;
            $validated['imagen_tipo'] = $request->file('img_base64')->getMimeType();
        }

        $validated['disponible'] = ($validated['stock'] > 0);

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
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'stock'       => 'required',
            'img_base64'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if (isset($validated['stock'])) {
            $validated['stock'] = $articulo->stock + $validated['stock'];
        }

        if ($request->hasFile('img_base64')) {
            // Eliminar imagen física anterior si existe
            if (!empty($articulo->img_base64) && Storage::disk('public')->exists($articulo->img_base64)) {
                Storage::disk('public')->delete($articulo->img_base64);
            }

            $path = $request->file('img_base64')->store('articulos', 'public');
            $validated['img_base64'] = $path;
            $validated['imagen_tipo'] = $request->file('img_base64')->getMimeType();
        }

        if (isset($validated['stock'])) {
            $validated['disponible'] = ($validated['stock'] > 0);
        }

        $articulo->update($validated);

        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo actualizado con éxito.');
    }

    public function destroy(Articulo $articulo): RedirectResponse
    {
        // Limpiar archivo físico asociado al eliminar el registro
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
