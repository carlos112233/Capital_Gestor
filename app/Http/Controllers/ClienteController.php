<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        // 1. Optimización: Paginamos directamente en la base de datos (PostgreSQL)
        $clientes = User::withTrashed()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . strtolower($request->q) . '%';
                // Buscamos por nombre, email o el nuevo campo telefono
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', $search)
                      ->orWhere('email', 'like', $search)
                      ->orWhere('telefono', 'like', $search);
                });
            }, function ($query) {
                // Si no hay búsqueda, no mostramos ningún registro
                $query->whereRaw('1 = 0');
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

        $rawPassword = $request->password;

        // Manejo de Imagen
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $validated['image'] = base64_encode(file_get_contents($file->getRealPath()));
            $validated['image_tipo'] = $file->getMimeType();
        }

        $validated['password'] = Hash::make($validated['password']);

        $cliente = User::create($validated);

        $waEnviado = false;
        if ($request->boolean('send_whatsapp_credentials') && !empty($cliente->telefono)) {
            $waEnviado = $this->queueWhatsAppCredentials($cliente, $rawPassword);
        }

        $mensajeExito = 'Usuario creado correctamente' . ($waEnviado ? ' y accesos encolados por WhatsApp.' : '.');

        return redirect()->route('admin.clientes.index')
            ->with('success', $mensajeExito);
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

        $rawPassword = null;
        // Manejo de Password
        if ($request->filled('password')) {
            $rawPassword = $request->password;
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $cliente->update($validated);

        if ($request->boolean('send_whatsapp_credentials') && !empty($cliente->telefono)) {
            $this->queueWhatsAppCredentials($cliente, $rawPassword ?? 'Su contraseña actual');
        }

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function enviarWhatsAppAccess(Request $request, $id)
    {
        $cliente = User::withTrashed()->findOrFail($id);

        if (empty($cliente->telefono)) {
            return back()->with('error', 'El cliente no tiene un número de teléfono registrado.');
        }

        $passwordText = $request->input('password');

        if (!empty($passwordText)) {
            $cliente->update([
                'password' => Hash::make($passwordText),
            ]);
        } else {
            $passwordText = 'Su contraseña asignada';
        }

        $this->queueWhatsAppCredentials($cliente, $passwordText);

        return back()->with('success', 'Mensaje de accesos por WhatsApp encolado correctamente para ' . $cliente->name . '.');
    }

    private function queueWhatsAppCredentials(User $cliente, string $password): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $cliente->telefono);
        if (empty($phone)) return false;

        // Si son 10 dígitos (México), anteponer 521
        if (strlen($phone) === 10) {
            $phone = '521' . $phone;
        }

        $mensaje = "🔐 *ACCESOS DE CUENTA - Capital Gestor*\n\n" .
                   "Hola *{$cliente->name}*,\n" .
                   "Tus credenciales de acceso a la plataforma son:\n\n" .
                   "👤 *Usuario/Email:* {$cliente->email}\n" .
                   "🔑 *Contraseña:* {$password}\n\n" .
                   "🌐 *Iniciar Sesión:* " . url('/login') . "\n\n" .
                   "¡Gracias por formar parte de Capital Gestor!";

        DB::table('whatsapp_pending_messages')->insert([
            'numero'     => $phone,
            'mensaje'    => $mensaje,
            'status'     => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function destroy($id)
    {
        $cliente = User::withTrashed()->findOrFail($id);
        $cliente->delete();
        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario dado de baja (eliminado lógicamente) correctamente.');
    }

    public function activar($id)
    {
        $cliente = User::withTrashed()->findOrFail($id);
        $cliente->restore();
        return redirect()->route('admin.clientes.index')
            ->with('success', 'Usuario reactivado correctamente.');
    }
}