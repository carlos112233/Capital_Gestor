<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Articulo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ClienteApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();
        if ($request->filled('q')) {
            $query->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . strtolower($request->q) . '%';
                // Buscamos por nombre, email o el nuevo campo telefono
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('telefono', 'ilike', $search);
                });
            }); // Ahora busca también por teléfono
        }

        // Agregamos 'telefono' al select
        $clientes = $query->select('id', 'name', 'email', 'telefono', 'created_at')
            ->orderBy('name', 'asc')
            ->toBase()
            ->get();

        return $this->success($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6',
            'telefono'   => 'nullable|string|max:20', // Validamos el teléfono
            'image_tipo' => 'nullable|string',
        ]);

        // Almacenamiento físico de Avatar
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('avatars', 'public');
            $validated['image'] = $path;
            $validated['image_tipo'] = $request->file('image')->getMimeType();
        } elseif ($request->filled('image')) {
            $clean = preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', (string) $request->image);
            $clean = preg_replace('/\s+/', '', $clean);
            $bin = base64_decode($clean, true);
            if ($bin !== false && strlen($bin) > 0) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $bin);
                finfo_close($finfo);
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $filename = 'avatars/' . uniqid('avatar_') . '.' . $ext;
                Storage::disk('public')->put($filename, $bin);
                $validated['image'] = $filename;
                $validated['image_tipo'] = $mime;
            }
        }

        $validated['password'] = Hash::make($validated['password']);

        $cliente = User::create($validated);

        return $this->success($cliente, 'Cliente creado correctamente', 201);
    }

    public function show(User $cliente): JsonResponse
    {
        // El objeto $cliente ya incluye el teléfono automáticamente si está en el modelo
        return $this->success($cliente);
    }

    public function update(Request $request, User $cliente): JsonResponse
    {
        $request->merge([
            'email' => trim(strtolower($request->email)),
        ]);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20', // Validamos el teléfono en el update
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($cliente->id),
            ],
            'password' => 'nullable|string|min:8',
        ]);

        // Almacenamiento físico de Avatar
        if ($request->hasFile('image')) {
            if (!empty($cliente->image) && Storage::disk('public')->exists($cliente->image)) {
                Storage::disk('public')->delete($cliente->image);
            }
            $path = $request->file('image')->store('avatars', 'public');
            $validated['image'] = $path;
            $validated['image_tipo'] = $request->file('image')->getMimeType();
        } elseif ($request->filled('image')) {
            $clean = preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', (string) $request->image);
            $clean = preg_replace('/\s+/', '', $clean);
            $bin = base64_decode($clean, true);
            if ($bin !== false && strlen($bin) > 0) {
                if (!empty($cliente->image) && Storage::disk('public')->exists($cliente->image)) {
                    Storage::disk('public')->delete($cliente->image);
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $bin);
                finfo_close($finfo);
                $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
                $filename = 'avatars/avatar_' . $cliente->id . '.' . $ext;
                Storage::disk('public')->put($filename, $bin);
                $validated['image'] = $filename;
                $validated['image_tipo'] = $mime;
            }
        }

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $cliente->update($validated);

        return $this->success($cliente, 'Cliente actualizado correctamente');
    }

    public function destroy(User $cliente): JsonResponse
    {
        $cliente->delete();
        return $this->success(null, 'Cliente eliminado correctamente');
    }

    public function articuloCliente(Request $request): JsonResponse
    {        
        $queryA = Articulo::query();
         if ($request->filled('a')) {
                $search = '%' . strtolower($request->a) . '%';
                $queryA->where('nombre', 'ilike', $search )->orderBy('nombre', 'asc')
            ->toBase()
            ->get();
            
        }

        // Quitamos 'imagen' de aquí porque no existe en la tabla
        $articulos = $queryA->select('id', 'nombre', 'precio', 'stock')
            ->orderBy('nombre', 'asc')
            ->toBase()
            ->get();
            
        $query = User::query();
        if ($request->filled('q')) {
            $query->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . strtolower($request->q) . '%';
                // Buscamos por nombre, email o el nuevo campo telefono
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('telefono', 'ilike', $search);
                });
            }); // Ahora busca también por teléfono
        }

        // Agregamos 'telefono' también aquí para que Flutter lo tenga disponible
        $clientes = $query->select('id', 'name', 'email', 'telefono')
            ->orderBy('name')
            ->toBase()
            ->get();

        return $this->success([
            "clientes" => $clientes,
            "articulos" => $articulos
        ]);
    }
}
