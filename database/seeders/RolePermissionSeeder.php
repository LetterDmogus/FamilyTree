<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions if they don't exist
        $permissions = ['manage_roles', 'manage_users', 'manage_master_data', 'view_dashboard'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $superAdminRole->syncPermissions(['manage_roles', 'manage_users', 'manage_master_data', 'view_dashboard']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(['manage_users', 'manage_master_data', 'view_dashboard']);

        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->syncPermissions(['view_dashboard']);

        // Create a test user and assign superadmin role
        $user = User::where('email', 'superadmin@example.com')->first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        $user->assignRole($superAdminRole);
    }
}
