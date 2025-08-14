<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;


class ArticuloController extends Controller
{
    public function index(): View
    {
        $articulos = Articulo::latest()->paginate(10);
        return view('admin.articulos.index', compact('articulos'));
    }

    public function create(): View
    {
        return view('admin.articulos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
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
        ]);

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
