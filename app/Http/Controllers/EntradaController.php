<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Cliente;
use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- Importante
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EntradaController extends Controller
{
        /**
     * Muestra una lista de las entradas.
     */
    public function index(): View
    {
        $user = Auth::user();
        $query = Entrada::with('user')->latest();

        if ($user->hasRole('admin')) {
            // El admin ve todas las entradas
            $entradas = $query->paginate(10);
        } else {
            // El usuario normal solo ve sus propias entradas
            $entradas = $query->where('user_id', $user->id)->paginate(10);
        }

        return view('entradas.index', compact('entradas'));
    }

    /**
     * Muestra el formulario para crear una nueva entrada.
     */
    public function create(): View
    {
        $users = User::all();
        $articulos = Articulo::all();
        $clientes = Cliente::all();
        return view('entradas.create', compact('users', 'articulos', 'clientes'));
    }

    /**
     * Guarda una nueva entrada en la base de datos.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id'=> 'required|exists:clientes,id',
            'articulo_id' => 'required|exists:articulos,id',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:1000',
            'fecha_generado' => 'required|date',
        ]);

        $user = Auth::user();
        $request->merge([
            'user_id' => $user->id,
        ]);

        Entrada::create($validated);

        return redirect()->route('entradas.index')
                         ->with('success', 'Entrada de capital registrada con éxito.');
    }


      public function edit(Entrada $venta)
    {
        $clientes = Cliente::all();
        return view('entradas.edit', compact('clientes'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        $user = Auth::user();

        $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'monto' => 'required|integer|min:1',
            'cliente_id' => 'required|exists:clientes,id',
            'descripcion' => 'nullable|string',
            'fecha_generado' => 'required|date',
        ]);

         $user = Auth::user();
        $request->merge([
            'user_id' => $user->id,
        ]);
        $entrada->update($request->all());

        return redirect()->route('entradas.index')->with('success', 'Venta actualizada correctamente.');
    }

}
