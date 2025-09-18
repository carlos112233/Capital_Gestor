<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $ventasQuery = Venta::with(['user', 'articulo', 'cliente'])->latest();

        // Si no es admin, solo ver sus ventas
        if (! $user->hasRole('admin')) {
            $ventasQuery->where('user_id', $user->id);
        }

        // Filtro por nombre de usuario
        if ($request->filled('q')) {
            $search = $request->input('q');
            $ventasQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $ventas = $ventasQuery->paginate(10);

        if ($request->ajax()) {
            return view('ventas._tabla', compact('ventas'))->render();
        }
        return view('ventas.index', compact('ventas'));
    }

    public function create(Request $request)
    {
        $users = User::all();
        $articulos = Articulo::all();
        $clientes = User::all();
        $articuloId = $request->get('articulo_id');
        return view('ventas.create', compact('users', 'articulos', 'clientes', 'articuloId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad' => 'required|integer|min:1',
            'cliente_id' => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
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
                    'user_id'      =>  Auth::user()->hasRole('admin') ? $validated['cliente_id'] : Auth::id(),
                    //'cliente_id'   => $validated['cliente_id'],
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
            return redirect()->route('catalogo.index')->with('error', 'Ocurrió un error inesperado al procesar la venta.' . $e);
        }

        return redirect()->route('catalogo.index')->with('success', '¡Venta registrada con éxito!');
    }

    public function show(Venta $venta)
    {
        return view('ventas.show', compact('venta'));
    }

    public function edit(Venta $venta)
    {
        $users = User::all();
        $articulos = Articulo::all();
        $clientes = Cliente::all();
        return view('ventas.edit', compact('venta', 'users', 'articulos', 'clientes'));
    }

    public function update(Request $request, Venta $venta)
    {
        $user = Auth::user();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad' => 'required|integer|min:1',
            'cliente_id' => 'required|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        $request->merge([
            'user_id' => $user->id,
            'total_venta' => $request->cantidad * $request->precio_venta
        ]);

        $venta->update($request->all());

        return redirect()->route('ventas.index')->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Venta $venta)
    {
        try {
            DB::transaction(function () use ($venta) {
                // 1. Bloqueamos el artículo relacionado
                $articulo = $venta->articulo()->lockForUpdate()->first();

                // 2. Devolvemos al stock la cantidad vendida
                $articulo->increment('stock', $venta->cantidad);

                // 3. Eliminamos la venta
                $venta->delete();
            });

            return redirect()->route('ventas.index')->with('success', 'Venta eliminada y stock actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('ventas.index')->with('error', 'Ocurrió un error al eliminar la venta: ' . $e->getMessage());
        }
    }
}
