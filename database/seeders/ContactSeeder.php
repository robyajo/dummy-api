<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create('id_ID');

        // Get the default User for assigning as contact creator
        $defaultUser = User::where('email', 'user@example.com')->first();

        for ($i = 1; $i <= 50; $i++) {
            Contact::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $defaultUser?->id,
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'avatar' => 'https://api.dicebear.com/7.x/adventurer/svg?seed='.urlencode($faker->name()),
            ]);
        }
    }
}
