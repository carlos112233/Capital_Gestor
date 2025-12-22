<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
         $clientesCollection = User::latest()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->get()
            ->sortBy('name')
            ->values();

        // paginación manual
        $page = request()->get('page', 1);
        $perPage = 10;
        $items = $clientesCollection->slice(($page - 1) * $perPage, $perPage)->all();

        $clientes = new LengthAwarePaginator(
            $items,
            $clientesCollection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($request->ajax()) {
            return view('admin.clientes._tabla', compact('clientes'))->render();
        }

        return view('admin.clientes.index', compact('clientes'));
    }


    public function create()
    {
        return view('admin.clientes.create');
    }
    /**
     * Crear un nuevo usuario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Laravel encripta automáticamente la contraseña por el cast
        User::create($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, User $cliente)
    {
        // Normalizar email
        $request->merge([
            'email' => trim(strtolower($request->email)),
        ]);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'string',
                'email',
                Rule::unique('users')->ignore($cliente->id),
            ],
            'password' => 'nullable|string|min:8',
        ]);

        if (!$request->filled('password')) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($validated['password']);
        }

        $cliente->update($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }


    public function edit($id)
    {
        $cliente = User::findOrFail($id);
        return view('admin.clientes.edit', compact('cliente'));
    }

    /**
     * Eliminar un usuario.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
