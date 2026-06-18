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
        User::factory(15)->create()->each(function ($user) {
            Image::factory()->create([
                'imageable_id' => $user->id,
                'imageable_type' => User::class,
            ]);
        });

    
        
    }
}
