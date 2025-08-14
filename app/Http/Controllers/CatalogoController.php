<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CatalogoController extends Controller
{
    /**
     * Muestra el catálogo de artículos con stock disponible.
     */
    public function index(): View
    {
        $articulos = Articulo::where('stock', '>', 0)->latest()->paginate(12);
        return view('catalogo.index', compact('articulos'));
    }

    /**
     * Procesa la venta de un artículo.
     */
    public function vender(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'articulo_id' => 'required|exists:articulos,id',
        'cantidad' => 'required|integer|min:1',
    ]);

    try {
        DB::transaction(function () use ($validated) {
            $articulo = Articulo::lockForUpdate()->find($validated['articulo_id']);
            $cantidadVenta = $validated['cantidad'];

            // Validar que hay suficiente stock
            if ($articulo->stock < $cantidadVenta) {
                // Usamos una ValidationException para que el error se muestre como un error de formulario
                throw \Illuminate\Validation\ValidationException::withMessages([
                   'cantidad' => 'No hay suficiente stock. Cantidad disponible: ' . $articulo->stock,
                ]);
            }

            // 1. Disminuir el stock del artículo
            $articulo->decrement('stock', $cantidadVenta);

            // 2. Registrar la venta en la tabla 'ventas'
            $articulo->ventas()->create([
                'user_id'      => Auth::id(),
                'cantidad'     => $cantidadVenta,
                'precio_venta' => $articulo->precio,
                'total_venta'  => $articulo->precio * $cantidadVenta,
            ]);
        });

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Redirigir hacia atrás con los errores de validación
        return redirect()->back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        // Para cualquier otro tipo de error inesperado
        return redirect()->route('catalogo.index')->with('error', 'Ocurrió un error inesperado al procesar la venta.');
    }

    return redirect()->route('catalogo.index')->with('success', '¡Venta registrada con éxito!');
}
}
