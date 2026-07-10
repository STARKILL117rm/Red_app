<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Hash;

class UserWithImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Creamos al usuario "Roman" (el que ya tienes o uno de prueba limpio)
        $user1 = User::create([
            'name' => 'Roman',
            'email' => 'roman@test.com',
            'phone_number' => '5481818181',
            'password' => Hash::make('password'), // Por si te pide loguearte
        ]);

        // 2. Le asignamos 3 imágenes simulando el carrusel mediante la relación polimórfica
        $user1->images()->createMany([
            ['url' => 'https://picsum.photos/id/10/600/400'],
            ['url' => 'https://picsum.photos/id/20/600/400'],
            ['url' => 'https://picsum.photos/id/30/600/400'],
        ]);

        // 3. Creamos un segundo usuario de prueba para que luzca tu método "Index"
        $user2 = User::create([
            'name' => 'Profesor de Redes',
            'email' => 'profe@test.com',
            'phone_number' => '9998887766',
            'password' => Hash::make('password'),
        ]);

        $user2->images()->createMany([
            ['url' => 'https://picsum.photos/id/40/600/400'],
            ['url' => 'https://picsum.photos/id/50/600/400'],
        ]);
    }
}