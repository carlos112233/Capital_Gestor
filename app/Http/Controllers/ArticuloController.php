<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $articulos = Articulo::comerciales()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($request->q) . '%']);
            })
            ->orderBy('nombre', 'asc')
            ->paginate(10)
            ->withQueryString();

        $clientes = \App\Models\User::orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return view('admin.articulos._tabla', compact('articulos', 'clientes'))->render();
        }
        return view('admin.articulos.index', compact('articulos', 'clientes'));
    }

    public function create(): View
    {
        return view('admin.articulos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->hasFile('img_base64')) {
            // Obtiene el contenido binario del archivo
            $imagenBinaria = file_get_contents($request->file('img_base64')->getRealPath());
            $imagen_tipo = $request->file('img_base64')->getMimeType();

            // Lo convierte a Base64
            $base64 = base64_encode($imagenBinaria);

            $request->merge([
                'img_base64' => $base64,
                'img_tipo' => $imagen_tipo,
            ]);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'img_base64' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if (isset($validated['stock']) && $validated['stock'] > 0) {
            $validated['disponible'] = true;
        } else {
            $validated['disponible'] = false;
        }

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
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required',
            'img_base64' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

        ]);

        if (isset($validated['stock'])) {
            $validated['stock'] = $articulo->stock + $validated['stock'];
        }

        if ($request->hasFile('img_base64')) {
            $file = $request->file('img_base64');
            $validated['img_base64'] = base64_encode(file_get_contents($file->getRealPath()));
            $articulo->imagen_tipo = $request->file('img_base64')->getMimeType();
        }

        if (isset($validated['stock'])) {
            if ($validated['stock'] > 0) {
                $validated['disponible'] = true;
            } else {
                $validated['disponible'] = false;
            }
        }

        $articulo->update($validated);

        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo actualizado con éxito.');
    }

    public function destroy(Articulo $articulo): RedirectResponse
    {
        $articulo->delete();
        return redirect()->route('admin.articulos.index')
            ->with('success', 'Artículo eliminado con éxito.');
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

        return back()->with('success', $mensaje);
    }

    public function resetStock(): RedirectResponse
    {
        $affected = Articulo::comerciales()->update(['stock' => 0, 'disponible' => false]);
        return back()->with('status', "Cierre 7:00 PM: Se actualizó el stock a 0 en {$affected} artículos.");
    }
}
