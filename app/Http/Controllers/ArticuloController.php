<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $articulosQuery = Articulo::latest();



        // Filtro por nombre de usuario
        if ($request->filled('q')) {
            $search = $request->input('q');
            $articulosQuery->where('nombre', 'like', '%' . $search . '%');
        }

        $articulos = $articulosQuery->paginate(10);

        if ($request->ajax()) {
            return view('admin.articulos._tabla', compact('articulos'))->render();
        }
        return view('admin.articulos.index', compact('articulos'));
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
            'stock' => 'required|integer|min:0',
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
}
