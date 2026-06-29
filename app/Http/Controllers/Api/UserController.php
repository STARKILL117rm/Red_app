<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\Image;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Carga la relación image para evitar N+1 queries.
     */
    public function index()
    {
        return UserResource::collection(User::with('image')->get());
    }

    /**
     * Store a newly created resource in storage.
     * Acepta opcionalmente una URL de imagen para crear el registro polimórfico.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:8',
            'phone_number' => 'nullable|string|max:255',
            'image_url'    => 'nullable|string|max:2048',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => $validated['password'],
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        // Si se envía una URL de imagen, crear el registro polimórfico
        if (!empty($validated['image_url'])) {
            $user->image()->create([
                'url' => $validated['image_url']
            ]);
        }

        return UserResource::make($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return UserResource::make(User::with('image')->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     * Soporta PUT y PATCH. Actualiza también la imagen si se provee.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password'     => 'sometimes|string|min:8',
            'phone_number' => 'nullable|string|max:255',
            'image_url'    => 'nullable|string|max:2048',
        ]);

        // Separar image_url del resto para no meterla en User
        $imageUrl = $validated['image_url'] ?? null;
        unset($validated['image_url']);

        $user->update($validated);

        // Actualizar o crear imagen polimórfica si se envió
        if ($imageUrl !== null) {
            if ($user->image) {
                $user->image()->update([
                    'url' => $imageUrl
                ]);
            } else {
                $user->image()->create([
                    'url' => $imageUrl
                ]);
            }
        }

        return UserResource::make($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        if ($user->image) {
            $user->image()->delete();
        }

        $user->delete();

        return response()->noContent();
    }
}