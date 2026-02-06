<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $clientesCollection = User::latest()
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->get()
            ->sortBy('name')
            ->values();

        $page = (int) $request->get('page', 1);
        $perPage = 10;

        $items = $clientesCollection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $clientes = new LengthAwarePaginator(
            $items,
            $clientesCollection->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query()
            ]
        );

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
        $request->merge([
            'email' => trim(strtolower($request->email)),
        ]);

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'image_tipo' => 'nullable|string', // Validamos que sea un string (el base64)
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($cliente->id),
            ],
            'password' => 'nullable|string|min:8',
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

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $cliente->update($validated);

        return $this->success(
            $cliente,
            'Cliente actualizado correctamente'
        );
    }

    public function destroy(User $cliente): JsonResponse
    {
        $cliente->delete();

        return $this->success(
            null,
            'Cliente eliminado correctamente'
        );
    }
}
