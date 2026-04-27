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
        // 1. Optimización: Paginamos directamente en la base de datos (PostgreSQL)
        $clientes = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . strtolower($request->q) . '%';
                // Buscamos por nombre, email o el nuevo campo telefono
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', $search)
                      ->orWhere('email', 'ilike', $search)
                      ->orWhere('telefono', 'ilike', $search);
                });
            })
            ->orderBy('name', 'asc')
            ->paginate(10) // Laravel hace el trabajo sucio por ti
            ->withQueryString(); // Mantiene los filtros al cambiar de página

        if ($request->ajax()) {
            return view('admin.clientes._tabla', compact('clientes'))->render();
        }

        return view('admin.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'telefono' => 'nullable|string|max:20', // Nuevo campo
            'image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Manejo de Imagen
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $validated['image'] = base64_encode(file_get_contents($file->getRealPath()));
            $validated['image_tipo'] = $file->getMimeType();
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $cliente = User::findOrFail($id);
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, User $cliente)
    {
        $request->merge([
            'email' => trim(strtolower($request->email)),
        ]);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($cliente->id),
            ],
            'telefono' => 'nullable|string|max:20', // Nuevo campo
            'password' => 'nullable|string|min:8',
            'image'    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Manejo de Imagen
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $validated['image'] = base64_encode(file_get_contents($file->getRealPath()));
            $validated['image_tipo'] = $file->getMimeType();
        }

        // Manejo de Password
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $cliente->update($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $cliente)
    {
        $cliente->delete();
        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}