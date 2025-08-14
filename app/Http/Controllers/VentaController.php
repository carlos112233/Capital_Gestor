<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\User;
use App\Models\Articulo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ventasQuery = Venta::with(['user', 'articulo', 'cliente'])->latest();
        if (! $user->hasRole('admin')) {
            $ventasQuery->where('user_id', Auth::id());
        }

        $ventas = $ventasQuery->paginate(10);
        //return $ventas;
        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $users = User::all();
        $articulos = Articulo::all();
        $clientes = Cliente::all();
        return view('ventas.create', compact('users', 'articulos', 'clientes'));
    }

    public function store(Request $request)
    {    
        $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cantidad' => 'required|integer|min:1',
            'cliente_id' => 'required|exists:clientes,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        $user = Auth::user();
        $request->merge([
            'user_id' => $user->id,
            'total_venta' => $request->cantidad * $request->precio_venta
        ]);

        Venta::create($request->all());

        return redirect()->route('ventas.index')->with('success', 'Venta creada correctamente.');
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
            'cliente_id' => 'required|exists:clientes,id',
            'precio_venta' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
        ]);

        $request->merge([
            'user_id' => $user->id,
            'total_venta' => $request->cantidad * $request->precio_venta
        ]);

        $venta->update($request->all());

        return redirect()->route('admin.ventas.index')->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Venta $venta)
    {
        $venta->delete();
        return redirect()->route('admin.ventas.index')->with('success', 'Venta eliminada correctamente.');
    }
}
