<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

// Ruta por defecto de Sanctum
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 🚨 NUEVA RUTA DE SINCRONIZACIÓN ASÍNCRONA
Route::post('users/sync', [UserController::class, 'sync'])->name('api.users.sync');

// 📸 ENDPOINTS PARA EL CHECKLIST (Control individual de imágenes polimórficas)
// 1. Agregar una imagen específica a un usuario existente
Route::post('users/{id}/images', [UserController::class, 'addImage'])->name('api.users.images.store');
// 2. Eliminar una imagen específica de la galería por su propio ID
Route::delete('images/{id}', [UserController::class, 'destroyImage'])->name('api.images.destroy');

// Tu recurso original intacto
Route::resource('users', UserController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->names('api.user');