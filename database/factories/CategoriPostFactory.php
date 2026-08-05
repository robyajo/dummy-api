<?php

namespace Database\Factories;

use App\Models\CategoriPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriPost>
 */
class CategoriPostFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->sentence(),
        ];
    }
}
