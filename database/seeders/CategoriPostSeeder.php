<?php

namespace Database\Seeders;

use App\Models\CategoriPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::where('email', 'user@example.com')->first();

        $categories = [
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Technology related articles'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'description' => 'Lifestyle articles'],
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Business and entrepreneurship'],
            ['name' => 'Health', 'slug' => 'health', 'description' => 'Health and wellness articles'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Education and learning'],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Entertainment and media'],
            ['name' => 'Science', 'slug' => 'science', 'description' => 'Science and research articles'],
            ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Travel and adventure articles'],
            ['name' => 'Food', 'slug' => 'food', 'description' => 'Food and culinary articles'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Sports and fitness articles'],
        ];

        foreach ($categories as $category) {
            CategoriPost::create(array_merge($category, [
                'uuid' => (string) Str::uuid(),
                'user_id' => $defaultUser?->id,
            ]));
        }
    }
}
