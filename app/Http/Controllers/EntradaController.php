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
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }



        $articulos = Articulo::orderBy('nombre', 'asc')->get();
        $users = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            $entradas = $entradasQuery->orderBy('created_at', 'desc')->get();
            return view('admin.entradas._tabla', compact('entradas', 'articulos', 'users'))->render();
        }
        $entradas = $entradasQuery->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.entradas.index', compact('entradas', 'articulos', 'users'));
    }

    /**
     * Muestra el formulario para crear una nueva entrada.
     */
    public function create(): View
    {
        // Priorizar artículos financieros de abono/saldo en la selección
        $articulos = Articulo::whereIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
            ->orWhere('nombre', 'LIKE', '%saldo%')
            ->orderBy('nombre')
            ->get();

        if ($articulos->isEmpty()) {
            $articulos = Articulo::select('id', 'nombre', 'precio')
                ->orderBy('nombre')
                ->get();
        }

    // Solo traemos ID y nombre de los usuarios.
    $users = User::select('id', 'name')
        ->orderBy('name')
        ->get();

    return view('admin.entradas.create', compact('articulos', 'users'));
    }

    /**
     * Guarda una nueva entrada en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos
        $validated = $request->validate([
            'cliente_id'   => 'nullable|exists:users,id',
            'articulo_id'  => 'required|exists:articulos,id',
            'precio_venta' => 'required|numeric',
            'descripcion'  => 'nullable|string|max:1000',
        ]);

        // 2. Determinar el user_id de forma segura
        $userId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
            ? $validated['cliente_id']
            : Auth::id();

        // 3. Crear el registro
        Entrada::create([
            'articulo_id'    => $validated['articulo_id'],
            'user_id'        => $userId,
            'cliente_id'     => $validated['cliente_id'] ?? $userId,
            'precio_venta'   => $validated['precio_venta'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'fecha_generado' => now(),
        ]);

        // 4. Redireccionar
        return redirect()->route('admin.entradas.index')
            ->with('success', 'Entrada de capital registrada con éxito.');
    }


    public function edit(Entrada $entrada)
    {
        $users = User::orderBy('name', 'asc')->get();
        $articulos = Articulo::whereIn('nombre', ['Pago saldado', 'saldo', 'Saldo', 'Abono', 'abono'])
            ->orWhere('nombre', 'LIKE', '%saldo%')
            ->orderBy('nombre')
            ->get();

        if ($articulos->isEmpty()) {
            $articulos = Articulo::all();
        }
        $entrada->load(['user',  'articulo']);

        return view('admin.entradas.edit', compact('users', 'articulos', 'entrada'));
    }

    public function update(Request $request, Entrada $entrada)
    {

        $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'cliente_id' => 'nullable|exists:users,id',
            'precio_venta' => 'required|numeric',
            'descripcion' => 'nullable|string',
        ]);

        $fecha = Carbon::now();
        $userId = (Auth::user()->hasRole('admin') && $request->filled('cliente_id'))
            ? $request['cliente_id']
            : Auth::id();

        $request->merge([
            'fecha_generado' => $fecha,
            'user_id'        => $userId,
            'cliente_id'     => $request->input('cliente_id') ?: $userId,
        ]);
        $entrada->update($request->all());

        return redirect()->route('admin.entradas.index')->with('success', 'Entrada de capital actualizada correctamente.');
    }

    public function destroy(Entrada $entrada)
    {
        $entrada->delete();

        return redirect()->route('admin.entradas.index')
            ->with('success', 'Entrada de capital eliminada correctamente.');
    }
}
