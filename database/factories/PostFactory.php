<?php

namespace Database\Factories;

use App\Models\CategoriPost;
use App\Models\Post;
use App\Models\Status;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);
        $paragraphs = fake()->paragraphs(3);

        $content = '<h2>'.htmlspecialchars($title).'</h2>';
        foreach ($paragraphs as $paragraph) {
            $content .= '<p>'.htmlspecialchars($paragraph).'</p>';
        }

        $content .= '<h3>Key Points</h3><ul>';
        for ($i = 0; $i < 3; $i++) {
            $content .= '<li><strong>'.htmlspecialchars(fake()->word).'</strong> — '.htmlspecialchars(fake()->sentence).'</li>';
        }
        $content .= '</ul>';

        $content .= '<blockquote><p>"'.htmlspecialchars(fake()->sentence(10)).'"</p></blockquote>';

        return [
            'title' => $title,
            'slug' => str($title)->slug(),
            'excerpt' => strip_tags(substr($content, 0, 150)).'...',
            'content' => $content,
            'cover_image' => 'https://api.dicebear.com/7.x/shapes/svg?seed='.fake()->uuid(),
            'author_id' => User::factory(),
            'category_id' => CategoriPost::factory(),
            'status_id' => Status::factory(),
            'tag_id' => Tag::factory(),
            'published_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'views' => (string) fake()->numberBetween(0, 10000),
            'meta_description' => strip_tags(substr($content, 0, 160)),
        ];
    }
}
