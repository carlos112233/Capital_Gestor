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
        // 1. Iniciamos la consulta de Usuarios (Clientes)
        $query = User::query();

        // 2. Filtro de búsqueda optimizado para PostgreSQL (ILIKE)
        if ($request->filled('q')) {
            $query->where('name', 'ilike', '%' . $request->q . '%');
        }

        // 3. Ejecución eficiente:
        // - select: Traemos solo lo necesario (id, name, email) para ahorrar RAM.
        // - orderBy: Ordenamos directamente en la DB, mucho más rápido que sortBy() de PHP.
        // - toBase: Convierte a objetos planos, ideal para no saturar los 512MB de Render.
        $clientes = $query->select('id', 'name', 'email', 'created_at')
            ->orderBy('name', 'asc')
            ->toBase()
            ->get();

        return $this->success($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'image_tipo' => 'nullable|string', // Validamos que sea un string (el base64)
        ]);
        if ($request->filled('image')) {
            $imageData = $request->image;

            // 1. Si el base64 viene con el encabezado "data:image/png;base64,..."
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                // Extraer el contenido puro sin el encabezado
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $validated['image'] = $imageData;
                $validated['image_tipo'] = "image/" . strtolower($type[1]);
            } else {
                // 2. Si es un base64 puro, usamos el image_tipo enviado desde Flutter
                $validated['image'] = $imageData;

                if ($request->filled('image_tipo')) {
                    $validated['image_tipo'] = $request->image_tipo;
                } else {
                    // 3. Si no hay nada, intentamos detectar por los primeros bytes del base64
                    $decoded = base64_decode($imageData);
                    $f = finfo_open();
                    $validated['image_tipo'] = finfo_buffer($f, $decoded, FILEINFO_MIME_TYPE);
                    finfo_close($f);
                }
            }
        }
        $validated['password'] = Hash::make($validated['password']);

        $cliente = User::create($validated);

        return $this->success(
            $cliente,
            'Cliente creado correctamente',
            201
        );
    }

    public function show(User $cliente): JsonResponse
    {
        return $this->success($cliente);
    }

    public function update(Request $request, User $cliente): JsonResponse
    {
        // 1. Normalizar el email
        $request->merge([
            'email' => trim(strtolower($request->email)),
        ]);

        // 2. Validar (Usando el ID del objeto $cliente que Laravel ya cargó)
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'image_tipo' => 'nullable|string',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Esta es la clave: ignora el ID del cliente actual
                Rule::unique('users', 'email')->ignore($cliente->id),
            ],
            'password' => 'nullable|string|min:8',
        ]);

        // 3. Lógica de la imagen (Base64)
        if ($request->filled('image')) {
            $imageData = $request->image;

            // Si trae encabezado data:image/...
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $validated['image'] = $imageData;
                $validated['image_tipo'] = "image/" . strtolower($type[1]);
            } else {
                // Si es base64 puro
                $validated['image'] = $imageData;
                if ($request->filled('image_tipo')) {
                    $validated['image_tipo'] = $request->image_tipo;
                } else {
                    $decoded = base64_decode($imageData);
                    $f = finfo_open();
                    $validated['image_tipo'] = finfo_buffer($f, $decoded, FILEINFO_MIME_TYPE);
                    finfo_close($f);
                }
            }
        }

        // 4. Lógica del Password
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            // Importante: Eliminar password del array si no se va a cambiar
            unset($validated['password']);
        }

        // 5. Actualizar el modelo
        $cliente->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $cliente,
            'message' => 'Cliente actualizado correctamente'
        ], 200);
    }

    public function destroy(User $cliente): JsonResponse
    {
        $cliente->delete();

        return $this->success(
            null,
            'Cliente eliminado correctamente'
        );
    }


    public function articuloCliente(Request $request): JsonResponse
    {
        // 1. Traemos solo las columnas necesarias. 
        // IMPORTANTE: NO incluyas 'image' o 'img_base64' aquí.
        $articulos = Articulo::select('id', 'nombre', 'stock', 'precio', 'descripcion')
            ->orderBy('nombre')
            ->toBase() // toBase es mucho más rápido para listas largas
            ->get();

        $clientes = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->toBase()
            ->get();

        $data = [
            "clientes" => $clientes,
            "articulos" => $articulos
        ];

        return $this->success($data);
    }
}
