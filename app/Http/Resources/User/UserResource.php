<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Image;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Muestra la lista optimizada (Solo la imagen más reciente por el Resource).
     */
    public function index()
    {
        $users = User::with('images')->get();
        return UserResource::collection($users);
    }

    /**
     * POST /api/users
     * REQUISITO DEL PDF: "Se puede crear contacto con varias imágenes"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20',
            'password' => 'nullable|string|min:6',
            'images' => 'nullable|array', // Recibe un arreglo de URLs enviadas desde Android
            'images.*.url' => 'required|url'
        ]);

        // Aseguramos una contraseña por defecto si no viene de Android
        $validated['password'] = bcrypt($validated['password'] ?? 'password123');

        // 1. Creamos el usuario/contacto
        $user = User::create($validated);

        // 2. Si vienen imágenes en la cola de sincronización, las creamos de forma polimorfa
        if (!empty($request->images)) {
            $user->images()->createMany($request->images);
        }

        return response()->json([
            'message' => 'Contacto creado exitosamente con sus imágenes',
            'data' => new UserResource($user->load('images'))
        ], 201);
    }

    /**
     * GET /api/users/{id}
     * REQUISITO DEL PDF: "GET /api/users/{id} regresa todas las imágenes del contacto"
     */
    public function show(string $id)
    {
        $user = User::with('images')->findOrFail($id);
        return new UserResource($user);
    }

    /**
     * POST /api/users/{id}/images
     * REQUISITO DEL PDF: "Se puede agregar una imagen específica"
     */
    public function addImage(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'url' => 'required|url'
        ]);

        // Agrega una nueva imagen de forma dinámica a la galería polimórfica
        $image = $user->images()->create([
            'url' => $request->url
        ]);

        return response()->json([
            'message' => 'Imagen agregada con éxito al contacto',
            'image' => $image
        ], 201);
    }

    /**
     * DELETE /api/images/{id}
     * REQUISITO DEL PDF: "Se puede eliminar una imagen específica"
     */
    public function destroyImage(string $id)
    {
        $image = Image::findOrFail($id);
        $image->delete();

        return response()->json([
            'message' => 'Imagen eliminada exitosamente de la galería polimórfica'
        ], 200);
    }
}