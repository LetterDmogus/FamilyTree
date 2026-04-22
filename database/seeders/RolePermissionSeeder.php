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

        // 1. Define All Granular Permissions
        $permissions = [
            // User Management
            'view_users', 'create_users', 'update_users', 'delete_users', 'access_trash_users',
            // Role Management
            'view_roles', 'create_roles', 'update_roles', 'delete_roles',
            // Master Data
            'view_master', 'create_master', 'update_master', 'delete_master', 'access_trash_master',
            // Family Tree
            'manage_tree_all', 'delete_node_all', 'manage_tree_self',
            // UI Basics
            'view_dashboard'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Setup Super Admin
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin']);
        // Super admin gets everything via Gate::before in AppServiceProvider, 
        // but we sync them anyway for clarity.
        $superAdminRole->syncPermissions($permissions);

        // 3. Setup Admin (Limited Recycle Bin access by default)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'view_users', 'create_users', 'update_users', 'delete_users',
            'view_roles',
            'view_master', 'create_master', 'update_master', 'delete_master',
            'manage_tree_all', 'delete_node_all',
            'view_dashboard'
        ]);

        // 4. Setup Member (Basic access)
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->syncPermissions([
            'view_dashboard',
            'manage_tree_self'
        ]);

        // 5. Ensure Super Admin User exists
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
