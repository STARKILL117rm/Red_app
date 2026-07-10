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
     * Soporta filtros, paginación y ordenamiento.
     */
    public function index(Request $request)
    {
        $query = User::with('images');

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('phone_number', 'LIKE', "%{$search}%");
            });
        }

        $allowedSortFields = ['id', 'name', 'email', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $allowedSortFields) ? $request->input('sort_by') : 'id';
        $sortOrder = strtolower($request->input('sort_order')) === 'desc' ? 'desc' : 'asc';
        
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 10);
        return new UserCollection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     * 🛠️ CORREGIDO: Si Android vuelve a enviar los datos aquí, NO CREA un usuario nuevo, lo actualiza.
     */
    public function store(Request $request)
    {
        $phone = $request->input('phone_number') ?? $request->input('phone') ?? null;
        $email = $request->input('email') ?? null;
        $name = $request->input('name') ?? 'Sin Nombre';
        $password = $request->input('password') ?? 'password123';

        // 🚨 BLOQUEO DE DUPLICADOS: Buscamos si ya existe por teléfono o por email real
        $user = null;
        if (!empty($phone)) {
            $user = User::where('phone_number', $phone)->first();
        }
        if (!$user && !empty($email) && !str_contains($email, 'sin_correo_')) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            // Si ya existe, lo actualizamos para que DBeaver no cree otra fila
            $user->update([
                'name' => $name,
                'phone_number' => $phone ?? $user->phone_number
            ]);
        } else {
            // Si es realmente nuevo, se crea
            $user = User::create([
                'name'         => $name,
                'email'        => $email ?? 'sin_correo_' . uniqid() . '@test.com',
                'password'     => bcrypt($password),
                'phone_number' => $phone,
            ]);
        }

        // 📸 REFLEJAR IMÁGENES SIN REPETIRLAS EN LA TABLA
        if ($request->has('image_urls') && is_array($request->input('image_urls'))) {
            foreach ($request->input('image_urls') as $url) {
                if (!empty($url) && !$user->images()->where('url', $url)->exists()) {
                    $user->images()->create(['url' => $url]);
                }
            }
        } elseif (!empty($request->input('image_url'))) {
            $url = $request->input('image_url');
            if (!$user->images()->where('url', $url)->exists()) {
                $user->images()->create(['url' => $url]);
            }
        }

        return UserResource::make($user->load('images'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return UserResource::make(User::with('images')->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
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
            'image_urls'   => 'nullable|array',
        ]);

        $imageUrl = $validated['image_url'] ?? null;
        $imageUrls = $validated['image_urls'] ?? null;
        unset($validated['image_url'], $validated['image_urls']);

        $user->update($validated);

        // Reflejar imágenes en el update controlando duplicados
        if (is_array($imageUrls)) {
            foreach ($imageUrls as $url) {
                if (!empty($url) && !$user->images()->where('url', $url)->exists()) {
                    $user->images()->create(['url' => $url]);
                }
            }
        }

        if ($imageUrl !== null && !$user->images()->where('url', $imageUrl)->exists()) {
            $user->images()->create(['url' => $imageUrl]);
        }

        return UserResource::make($user->load('images'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        $user->images()->delete();
        $user->delete();

        return response()->noContent();
    }

    /**
     * Procesa la sincronización masiva enviada desde el SyncWorker de Android.
     */
    public function sync(Request $request): JsonResponse
    {
        $contacts = $request->all();

        if (empty($contacts)) {
            return response()->json(['message' => 'No hay contactos para sincronizar'], 200);
        }

        $synchronizedCount = 0;

        foreach ($contacts as $contactData) {
            $phone = $contactData['phone_number'] ?? null;
            $email = $contactData['email'] ?? null;

            $user = null;
            if (!empty($phone)) {
                $user = User::where('phone_number', $phone)->first();
            }
            if (!$user && !empty($email) && !str_contains($email, 'sin_correo_')) {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                $user->update([
                    'name' => $contactData['name'] ?? $user->name,
                    'phone_number' => $phone ?? $user->phone_number,
                ]);
            } else {
                $user = User::create([
                    'name'         => $contactData['name'] ?? 'Sin Nombre',
                    'email'        => $email ?? 'sin_correo_' . uniqid() . '@test.com',
                    'password'     => $contactData['password'] ?? 'password123', 
                    'phone_number' => $phone,
                ]);
            }

            // Reflejar imágenes en sincronización sin repetirse
            if (isset($contactData['image_urls']) && is_array($contactData['image_urls'])) {
                foreach ($contactData['image_urls'] as $url) {
                    if (!empty($url) && !$user->images()->where('url', $url)->exists()) {
                        $user->images()->create(['url' => $url]);
                    }
                }
            } elseif (!empty($contactData['image_url'])) {
                $url = $contactData['image_url'];
                if (!$user->images()->where('url', $url)->exists()) {
                    $user->images()->create(['url' => $url]);
                }
            }

            $synchronizedCount++;
        }

        return response()->json([
            'message' => 'Sincronización completada con éxito',
            'processed' => $synchronizedCount
        ], 201);
    }

    /**
     * 📸 Agrega una foto de forma aislada a la tabla imágenes.
     */
    public function addImage(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'url' => 'required|url|max:2048'
        ]);

        // Evitamos meter la misma URL dos veces a la tabla imágenes
        if ($user->images()->where('url', $request->input('url'))->exists()) {
            return response()->json([
                'message' => 'Esta imagen ya está registrada para este usuario',
                'image' => $user->images()->where('url', $request->input('url'))->first()
            ], 200);
        }

        $image = $user->images()->create([
            'url' => $request->input('url')
        ]);

        return response()->json([
            'message' => 'Imagen inyectada de forma polimórfica al contacto',
            'image' => $image
        ], 201);
    }

    /**
     * 🗑️ Elimina una imagen de la tabla.
     */
    public function destroyImage(string $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        $image->delete();

        return response()->json([
            'message' => 'Imagen removida de la tabla polimórfica exitosamente'
        ], 200);
    }
}