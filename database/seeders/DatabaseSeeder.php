<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $adminRole = \App\Models\Role::firstOrCreate([
            'name' => 'admin',
        ], [
            'label' => 'Administrator',
            'description' => 'Full admin access',
        ]);

        $permissions = [
            ['name' => 'manage_users', 'label' => 'Manage users'],
            ['name' => 'manage_roles', 'label' => 'Manage roles'],
            ['name' => 'manage_permissions', 'label' => 'Manage permissions'],
        ];

        foreach ($permissions as $p) {
            $permission = \App\Models\Permission::firstOrCreate(['name' => $p['name']], [
                'label' => $p['label'],
                'description' => $p['label'],
            ]);
            $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
