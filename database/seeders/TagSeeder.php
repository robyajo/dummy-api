<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::where('email', 'user@example.com')->first();

        $tags = [
            ['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'Laravel framework related posts'],
            ['name' => 'PHP', 'slug' => 'php', 'description' => 'PHP programming posts'],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'description' => 'JavaScript related posts'],
            ['name' => 'Tutorial', 'slug' => 'tutorial', 'description' => 'Tutorial and how-to posts'],
            ['name' => 'News', 'slug' => 'news', 'description' => 'Latest news and updates'],
            ['name' => 'Tips', 'slug' => 'tips', 'description' => 'Tips and tricks posts'],
            ['name' => 'Review', 'slug' => 'review', 'description' => 'Product and tool reviews'],
            ['name' => 'Opinion', 'slug' => 'opinion', 'description' => 'Opinion and editorial posts'],
            ['name' => 'Beginner', 'slug' => 'beginner', 'description' => 'Beginner-friendly content'],
            ['name' => 'Advanced', 'slug' => 'advanced', 'description' => 'Advanced level content'],
            ['name' => 'Open Source', 'slug' => 'open-source', 'description' => 'Open source projects and tools'],
            ['name' => 'DevOps', 'slug' => 'devops', 'description' => 'DevOps and infrastructure posts'],
            ['name' => 'AI', 'slug' => 'ai', 'description' => 'Artificial intelligence posts'],
            ['name' => 'Machine Learning', 'slug' => 'machine-learning', 'description' => 'Machine learning related posts'],
            ['name' => 'Database', 'slug' => 'database', 'description' => 'Database and data posts'],
            ['name' => 'API', 'slug' => 'api', 'description' => 'API development posts'],
            ['name' => 'Security', 'slug' => 'security', 'description' => 'Security related posts'],
            ['name' => 'Performance', 'slug' => 'performance', 'description' => 'Performance optimization posts'],
            ['name' => 'Testing', 'slug' => 'testing', 'description' => 'Testing and quality assurance posts'],
            ['name' => 'Career', 'slug' => 'career', 'description' => 'Career and professional development posts'],
            ['name' => 'Freelancing', 'slug' => 'freelancing', 'description' => 'Freelancing tips and experience'],
            ['name' => 'Startup', 'slug' => 'startup', 'description' => 'Startup and entrepreneurship posts'],
            ['name' => 'Design', 'slug' => 'design', 'description' => 'Design and UI/UX posts'],
            ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Mobile development posts'],
            ['name' => 'Backend', 'slug' => 'backend', 'description' => 'Backend development posts'],
            ['name' => 'Frontend', 'slug' => 'frontend', 'description' => 'Frontend development posts'],
            ['name' => 'Cloud', 'slug' => 'cloud', 'description' => 'Cloud computing posts'],
            ['name' => 'Blockchain', 'slug' => 'blockchain', 'description' => 'Blockchain and web3 posts'],
            ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'description' => 'Cybersecurity posts'],
            ['name' => 'IoT', 'slug' => 'iot', 'description' => 'Internet of Things posts'],
        ];

        foreach ($tags as $tag) {
            Tag::create(array_merge($tag, [
                'uuid' => (string) Str::uuid(),
                'user_id' => $defaultUser?->id,
            ]));
        }
    }
}
