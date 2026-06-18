<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Resources\User\UserResource;

Route::get('/user', function (Request $request) {
        return $request->user();
})->middleware('auth:sanctum');


Route::resource('users', UserController::class)
    ->only (['index', 'store', 'show', 'update', 'destroy'])
    ->names('api.user');