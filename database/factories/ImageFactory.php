<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'url'=>'https://picsum.photos/id/' .$this->faker->unique()->numberBetween(1, 1000). '/1090/800',
            'imageable_id' => $this->faker->randomDigitNotNull(),
            'imageable_type' => $this->faker->randomElement(['App\Models\User']),
        ];
    }
}
