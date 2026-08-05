<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultUser = User::where('email', 'user@example.com')->first();

        $statuses = [
            ['name' => 'Draft', 'slug' => 'draft', 'description' => 'Post is in draft mode', 'type' => 'draft'],
            ['name' => 'In Review', 'slug' => 'in-review', 'description' => 'Post is under review', 'type' => 'review'],
            ['name' => 'Published', 'slug' => 'published', 'description' => 'Post is published', 'type' => 'published'],
            ['name' => 'Archived', 'slug' => 'archived', 'description' => 'Post has been archived', 'type' => 'archived'],
        ];

        foreach ($statuses as $status) {
            Status::create(array_merge($status, [
                'uuid' => (string) Str::uuid(),
                'user_id' => $defaultUser?->id,
            ]));
        }
    }
}
