<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            // Mostrar todos los pedidos
            $pedidos = Pedido::with(['user', 'articulo', 'venta'])->latest()->paginate(10);;
        } else {
            // Mostrar solo los pedidos del user autenticado
            $pedidos = Pedido::with(['user', 'articulo', 'venta'])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(10);
        }

        return view('pedidos.index', compact('pedidos'));
    }


    public function create()
    {
        $articulos = Articulo::all();
        $users = User::all();
        $articuloId = null; // o algún valor por defecto, por ejemplo el primer artículo
        return view('pedidos.create', compact('articulos', 'articuloId', 'users'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'descripcion' => 'nullable|string',
            'costo'       => 'required|numeric|min:1',
            'user_id' => 'required|exists:users,id',
        ]);
        // Crear la venta
        $venta = Venta::create([
            'user_id'     => Auth::user()->hasRole('admin') ? $validated['user_id'] : Auth::id(),
            'articulo_id' => $validated['articulo_id'],
            'precio_venta' => $validated['costo'],
            'total_venta' => $validated['costo'],
            'cliente_id'  => null, // si quieres relacionarlo con cliente luego
            'descripcion' => $validated['descripcion'] ?? '',
        ]);

        // Crear el pedido y ligarlo a la venta
        Pedido::create([
            'user_id'     => Auth::user()->hasRole('admin') ? $validated['user_id'] : Auth::id(),
            'articulo_id' => $validated['articulo_id'],
            'descripcion' => $validated['descripcion'],
            'costo'       => $validated['costo'],
            'venta_id'    => $venta->id,
        ]);

        return redirect()->route('pedidos.index')->with('success', 'Pedido creado y venta generada.');
    }

    public function edit($id)
    {
        // Obtener el pedido con su venta
        $pedido = Pedido::with('venta')->findOrFail($id);

        // Verificar permisos: si no es admin, solo puede editar sus propios pedidos
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para editar este pedido.');
        }

        $articulos = Articulo::all();
        $users  = User::all(); // si admin, puede cambiar el usuario

        return view('pedidos.edit', compact('pedido', 'articulos', 'users'));
    }

    public function update(Request $request, $id)
    {
        $pedido = Pedido::with('venta')->findOrFail($id);

        // Verificar permisos
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para actualizar este pedido.');
        }

        $validated = $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'descripcion' => 'nullable|string',
            'costo'       => 'required|numeric|min:1',
            'user_id'     => 'required|exists:users,id',
        ]);

        // Actualizar el pedido
        $pedido->update([
            'user_id'     => Auth::user()->hasRole('admin') ? $validated['user_id'] : Auth::id(),
            'articulo_id' => $validated['articulo_id'],
            'descripcion' => $validated['descripcion'] ?? '',
            'costo'       => $validated['costo'],
        ]);

        // Actualizar la venta asociada
        if ($pedido->venta) {
            $pedido->venta->update([
                'user_id'      => Auth::user()->hasRole('admin') ? $validated['user_id'] : Auth::id(),
                'articulo_id'  => $validated['articulo_id'],
                'precio_venta' => $validated['costo'],
                'total_venta'  => $validated['costo'],
                'descripcion'  => $validated['descripcion'] ?? '',
            ]);
        }

        return redirect()->route('pedidos.index')->with('success', 'Pedido y venta actualizados correctamente.');
    }

    public function destroy($id)
    {
        // Obtener el pedido con su venta
        $pedido = Pedido::with('venta')->findOrFail($id);

        // Verificar permisos: si no es admin, solo puede eliminar sus propios pedidos
        if (!Auth::user()->hasRole('admin') && $pedido->user_id != Auth::id()) {
            abort(403, 'No tienes permiso para eliminar este pedido.');
        }

        // Eliminar la venta asociada primero (si existe)
        if ($pedido->venta) {
            $pedido->venta->delete();
        }

        // Eliminar el pedido
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido y venta eliminados correctamente.');
    }
}
