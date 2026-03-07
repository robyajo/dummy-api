<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [];

        // Using faker to generate contacts
        $faker = \Faker\Factory::create('id_ID');

        for ($i = 1; $i <= 50; $i++) {
            $contacts[] = [
                'uuid' => Str::uuid(),
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'avatar' => 'assets/template/avatar-' . rand(1, 4) . '.png',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('contacts')->insert($contacts);
    }
}
