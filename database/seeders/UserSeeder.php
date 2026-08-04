<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
            'password' => Hash::make('string'),
        ]);
        $superAdmin->assignRole('Super Admin');

        // Creating Admin User
        $admin = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Ini Admin',
            'email' => 'a@a.com',
            'active' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('string'),
        ]);
        $admin->assignRole('Admin');

        // Creating User
        $user = User::create([
            'uuid' => Str::uuid(),
            'name' => 'Ini User',
            'email' => 'user@example.com',
            'active' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('string'),
        ]);
        $user->assignRole('User');

        // Output test credentials to console
        $this->command->info('');
        $this->command->info('=== Test Credentials (for API testing) ===');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 's@s.com', 'string'],
                ['Admin', 'a@a.com', 'string'],
                ['User', 'user@example.com', 'string'],
            ]
        );
        $this->command->info('Login endpoint: POST /api/auth/login');
        $this->command->info('');
    }
}
