<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'Super Admin']);
        $admin = Role::create(['name' => 'Admin']);
        $user = Role::create(['name' => 'User']);
        Role::create(['name' => 'User 2']);
        Role::create(['name' => 'User 3']);

        $admin->givePermissionTo([
            // Manage Roles
            'get-role',
            'create-role',
            'show-role',
            'edit-role',
            'delete-role',
            // Manage Permissions
            'get-permission',
            'create-permission',
            'show-permission',
            'edit-permission',
            'delete-permission',
            // Manage Users
            'get-user',
            'create-user',
            'show-user',
            'edit-user',
            'delete-user',

        ]);


        $user->givePermissionTo([
            'get-user',
            'show-user',
            'edit-user'
        ]);
    }
}
