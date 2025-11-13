<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'setup:roles';

    /**
     * The description of the console command.
     */
    protected $description = 'Setup roles and permissions for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up roles and permissions...');

        try {
            // Create basic roles
            $roles = ['admin', 'owner', 'user'];

            foreach ($roles as $roleName) {
                $role = Role::firstOrCreate(['name' => $roleName]);
                $this->line("✅ Role created/exists: $roleName");
            }

            // Create basic permissions
            $permissions = [
                'view-dashboard',
                'create-venues',
                'view-venues',
                'update-venues',
                'delete-venues',
                'create-courts',
                'view-courts',
                'update-courts',
                'delete-courts',
                'create-bookings',
                'view-bookings',
                'update-bookings',
                'delete-bookings',
                'manage-users',
                'send-notifications',
            ];

            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $this->line("✅ Permission created/exists: $permissionName");
            }

            // Assign permissions to roles
            $adminRole = Role::findByName('admin');
            $ownerRole = Role::findByName('owner');
            $userRole = Role::findByName('user');

            // Admin gets all permissions
            $adminRole->syncPermissions($permissions);
            $this->line("✅ Admin role assigned all permissions");

            // Owner gets venue/court/booking permissions
            $ownerPermissions = [
                'view-dashboard',
                'create-venues',
                'view-venues',
                'update-venues',
                'create-courts',
                'view-courts',
                'update-courts',
                'view-bookings',
                'update-bookings',
            ];
            $ownerRole->syncPermissions($ownerPermissions);
            $this->line("✅ Owner role assigned permissions");

            // User gets basic permissions
            $userPermissions = [
                'view-venues',
                'view-courts',
                'create-bookings',
                'view-bookings',
                'update-bookings',
            ];
            $userRole->syncPermissions($userPermissions);
            $this->line("✅ User role assigned permissions");

            $this->newLine();
            $this->info('🎉 Roles and permissions setup completed successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to setup roles: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
