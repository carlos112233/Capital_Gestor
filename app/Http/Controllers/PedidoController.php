<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Events\PedidoCreado;

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
            'pedidos.*.articulo_id' => 'required|exists:articulos,id',
            'pedidos.*.cantidad'    => 'required|integer|min:1',
            'pedidos.*.costo'       => 'required|numeric|min:1',
            'pedidos.*.descripcion' => 'nullable|string',
            'user_id'                => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];

            // Crear la venta
            $venta = Venta::create([
                'user_id'      => $userId,
                'articulo_id'  => $p['articulo_id'],
                'cantidad'     => $p['cantidad'],
                'precio_venta' => $p['costo'],
                'total_venta'  => $total,
                'descripcion'  => $p['descripcion'] ?? '',
            ]);

            // Crear el pedido
           $pedido =  Pedido::create([
                'user_id'     => $userId,
                'articulo_id' => $p['articulo_id'],
                'descripcion' => $p['descripcion'] ?? '',
                'costo'       => $total,
                'venta_id'    => $venta->id,
            ]);

            event(new PedidoCreado($pedido));
        }

        return redirect()->route('pedidos.index')->with('success', 'Todos los pedidos fueron creados correctamente.');
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
        $validated = $request->validate([
            'pedidos.*.id'           => 'nullable|exists:pedidos,id', // Si se quiere actualizar existentes
            'pedidos.*.articulo_id'  => 'required|exists:articulos,id',
            'pedidos.*.cantidad'     => 'required|integer|min:1',
            'pedidos.*.costo'        => 'required|numeric|min:1',
            'pedidos.*.descripcion'  => 'nullable|string',
            'user_id'                 => 'nullable|exists:users,id',
        ]);

        $userId = Auth::user()->hasRole('admin') ? $request->user_id : Auth::id();

        foreach ($request->pedidos as $p) {
            $total = $p['costo'] * $p['cantidad'];

            if (!empty($p['id'])) {
                // Actualizar pedido y su venta existente
                $pedidoExistente = Pedido::find($p['id']);
                $pedidoExistente->update([
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                ]);

                $pedidoExistente->venta()->update([
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $p['costo'],
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);
            } else {
                // Crear nueva venta y pedido
                $venta = Venta::create([
                    'user_id'      => $userId,
                    'articulo_id'  => $p['articulo_id'],
                    'cantidad'     => $p['cantidad'],
                    'precio_venta' => $p['costo'],
                    'total_venta'  => $total,
                    'descripcion'  => $p['descripcion'] ?? '',
                ]);

                Pedido::create([
                    'user_id'     => $userId,
                    'articulo_id' => $p['articulo_id'],
                    'descripcion' => $p['descripcion'] ?? '',
                    'costo'       => $total,
                    'venta_id'    => $venta->id,
                ]);
            }
        }

        return redirect()->route('pedidos.index')->with('success', 'Pedidos actualizados correctamente.');
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
