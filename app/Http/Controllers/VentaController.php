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

        $ventas = $ventasQuery->paginate(20);

        if ($request->ajax()) {
            return view('ventas._tabla', compact('ventas'))->render();
        }
        return view('ventas.index', compact('ventas'));
    }

    public function create(Request $request)
    {
        $articulos = Articulo::all();
        $clientes = User::orderBy('name', 'asc')->get();
        $articuloId = $request->get('articulo_id');
        return view('ventas.create', compact('articulos', 'clientes', 'articuloId'));
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
        $clientes = User::all();
        return view('ventas.edit', compact('venta', 'users', 'articulos', 'clientes'));
    }

    public function update(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad' => 'required|integer|min:1',
            'cliente_id' => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $venta) {
                // 1. Obtener el artículo (el actual o el nuevo si cambió)
                $articulo = Articulo::lockForUpdate()->find($validated['articulo_id']);

                // 2. Calcular la diferencia de stock
                // Si antes vendí 5 y ahora quiero vender 8, la diferencia es 3 (debo quitar 3 más)
                // Si antes vendí 5 y ahora quiero vender 3, la diferencia es -2 (debo devolver 2)
                $diferenciaStock = $validated['cantidad'] - $venta->cantidad;

                // 3. Validar si hay stock suficiente para la diferencia
                if ($articulo->stock < $diferenciaStock) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cantidad' => 'No hay suficiente stock adicional. Disponible: ' . $articulo->stock,
                    ]);
                }

                // 4. Actualizar el stock del artículo
                $articulo->decrement('stock', $diferenciaStock);

                // 5. Actualizar los datos de la venta
                $venta->update([
                    // Si es admin, puede cambiar el cliente. Si no, se queda el que estaba.
                    'user_id'      => Auth::user()->hasRole('admin') ? $validated['cliente_id'] : $venta->user_id,
                    'articulo_id'  => $validated['articulo_id'],
                    'cantidad'     => $validated['cantidad'],
                    'precio_venta' => $articulo->precio, // O $validated['precio_venta'] si permites editar precio
                    'total_venta'  => $articulo->precio * $validated['cantidad'],
                    'descripcion'  => $validated['descripcion'],
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->route('ventas.index')->with('error', 'Error al actualizar la venta: ' . $e->getMessage());
        }

        return redirect()->route('ventas.index')->with('success', '¡Venta actualizada con éxito!');
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
