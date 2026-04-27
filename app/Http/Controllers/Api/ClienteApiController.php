<?php

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

class ClienteApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('q')) {
            $query->where('name', 'ilike', '%' . $request->q . '%')
                  ->orWhere('telefono', 'ilike', '%' . $request->q . '%'); // Ahora busca también por teléfono
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

        // Lógica de Imagen Base64
        if ($request->filled('image')) {
            $imageData = $request->image;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $validated['image'] = $imageData;
                $validated['image_tipo'] = "image/" . strtolower($type[1]);
            } else {
                $validated['image'] = $imageData;
                $validated['image_tipo'] = $request->image_tipo ?? 'image/jpeg';
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
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($cliente->id),
            ],
            'password' => 'nullable|string|min:8',
        ]);

        // Lógica de Imagen Base64 (simplificada para legibilidad)
        if ($request->filled('image')) {
            $imageData = $request->image;
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $validated['image'] = $imageData;
                $validated['image_tipo'] = "image/" . strtolower($type[1]);
            } else {
                $validated['image'] = $imageData;
                $validated['image_tipo'] = $request->image_tipo ?? $cliente->image_tipo;
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
        $articulos = Articulo::select('id', 'nombre', 'stock', 'precio', 'descripcion')
            ->orderBy('nombre')
            ->toBase()
            ->get();

        // Agregamos 'telefono' también aquí para que Flutter lo tenga disponible
        $clientes = User::select('id', 'name', 'email', 'telefono')
            ->orderBy('name')
            ->toBase()
            ->get();

        return $this->success([
            "clientes" => $clientes,
            "articulos" => $articulos
        ]);
    }
}
