<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->sentence(),
        ];
    }
}
