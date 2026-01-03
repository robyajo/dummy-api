<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Super Admin User
        $superAdmin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Saya',
            'email' => 's@s.com',
            'active' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('123456')
        ]);
        $superAdmin->assignRole('Super Admin');

        // Creating Admin User
        $admin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Ini Admin',
            'email' => 'a@a.com',
            'active' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('123456')
        ]);
        $admin->assignRole('Admin');

        // Creating User
        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Ini User',
            'email' => 'u@u.com',
            'active' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('123456')
        ]);
        $user->assignRole('User');
    }
}
