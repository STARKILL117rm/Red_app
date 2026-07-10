<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Image;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
   public function run(): void
{
    // Creamos 15 usuarios
    User::factory(15)->create()->each(function ($user) {
        // 🔥 Aquí el truco: usamos count(3) para que meta 3 fotos en el carrusel de cada usuario
        \App\Models\Image::factory()->count(3)->create([
            'imageable_id'   => $user->id,
            'imageable_type' => User::class,
        ]);
    });
}
}
