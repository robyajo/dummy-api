<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();
        $types = ['draft', 'review', 'published', 'archived'];

        return [
            'user_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement($types),
        ];
    }
}
