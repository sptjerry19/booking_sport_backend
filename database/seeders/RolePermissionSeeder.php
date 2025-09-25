<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Venue permissions
            'create-venues',
            'view-venues',
            'update-venues',
            'delete-venues',
            'approve-venues',

            // Court permissions
            'create-courts',
            'view-courts',
            'update-courts',
            'delete-courts',

            // Booking permissions
            'create-bookings',
            'view-bookings',
            'update-bookings',
            'cancel-bookings',

            // Sport permissions
            'create-sports',
            'update-sports',
            'delete-sports',

            // User management
            'manage-users',
            'view-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $playerRole = Role::firstOrCreate(['name' => 'player']);

        // Assign permissions to roles
        $adminRole->givePermissionTo($permissions);

        $ownerRole->givePermissionTo([
            'create-venues',
            'view-venues',
            'update-venues',
            'delete-venues',
            'create-courts',
            'view-courts',
            'update-courts',
            'delete-courts',
            'view-bookings',
            'update-bookings',
            'cancel-bookings',
        ]);

        $playerRole->givePermissionTo([
            'view-venues',
            'view-courts',
            'create-bookings',
            'view-bookings',
            'cancel-bookings',
        ]);

        // Create demo users
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'System Admin',
            'password' => 'password',
            'level' => 'professional',
        ]);
        $admin->assignRole('admin');

        $owner = User::firstOrCreate([
            'email' => 'owner@example.com'
        ], [
            'name' => 'Venue Owner',
            'password' => 'password',
            'level' => 'intermediate',
            'phone' => '+84901234567',
        ]);
        $owner->assignRole('owner');

        $player = User::firstOrCreate([
            'email' => 'player@example.com'
        ], [
            'name' => 'John Player',
            'password' => 'password',
            'level' => 'beginner',
            'phone' => '+84907654321',
            'preferred_sports' => ['football', 'basketball'],
            'preferred_position' => ['midfielder', 'guard'],
        ]);
        $player->assignRole('player');
    }
}
