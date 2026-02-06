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
use Carbon\Carbon;


class EntradaController extends Controller
{

    /**
     * Muestra una lista de las entradas.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $entradasQuery = Entrada::with('user')->latest();

        if ($user->hasRole('admin')) {
            // El admin ve todas las entradas
            $entradas = $entradasQuery->paginate(15);
        } else {
            // El usuario normal solo ve sus propias entradas
            $entradas = $entradasQuery->where('user_id', $user->id)->paginate(10);
        }

        // Filtro por nombre de usuario
        if ($request->filled('q')) {
            $search = $request->input('q');
            $entradasQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }



        if ($request->ajax()) {
            $entradas = $entradasQuery->orderBy('created_at', 'desc')->get();
            return view('admin.entradas._tabla', compact('entradas'))->render();
        }
        $entradas = $entradasQuery->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.entradas.index', compact('entradas'));
    }

    /**
     * Muestra el formulario para crear una nueva entrada.
     */
    public function create(): View
    {
        $articulos = Articulo::all();
        $clientes = User::orderBy('name', 'asc')->get();
        return view('admin.entradas.create', compact( 'articulos', 'clientes'));
    }

    /**
     * Guarda una nueva entrada en la base de datos.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'cliente_id' => 'nullable|exists:users,id',
            'articulo_id' => 'required|exists:articulos,id',
            'precio_venta' => 'required|integer',
            'descripcion' => 'nullable|string|max:1000',
        ]);


        $fecha = Carbon::now();
        $user = Auth::user();
        $request->merge([
            'fecha_generado' => $fecha,
            'user_id' => Auth::user()->hasRole('admin') ? $validated['cliente_id'] : Auth::id(),
            'cliente_id' => null
        ]);

        Entrada::create($request->all());

        return redirect()->route('admin.entradas.index')
            ->with('success', 'Entrada de capital registrada con éxito.');
    }


    public function edit(Entrada $entrada)
    {
        $users = User::all();
        $articulos = Articulo::all();
        $clientes = User::all();
        $entrada->load(['user', 'cliente', 'articulo']);

        return view('admin.entradas.edit', compact('users', 'articulos', 'clientes', 'entrada'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        $user = Auth::user();

        $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cliente_id' => 'nullable|exists:users,id',
            'precio_venta' => 'required|integer',
            'descripcion' => 'nullable|string',
        ]);

        $fecha = Carbon::now();
        $user = Auth::user();
        $request->merge([
            'fecha_generado' => $fecha,
            'user_id' => Auth::user()->hasRole('admin') ? $request['cliente_id'] : Auth::id(),
        ]);
        $entrada->update($request->all());

        return redirect()->route('admin.entradas.index')->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Entrada $entrada)
    {
        $entrada->delete();

        return redirect()->route('admin.entradas.index')
            ->with('success', 'Venta eliminada correctamente.');
    }
}
