<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventoryPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create inventory permissions
        $permissions = [
            'view_inventory',
            'edit_inventory',
            'create_inventory',
            'delete_inventory',
            'manage_stock_levels',
            'view_stock_movements',
            'create_stock_movements',
            'adjust_stock',
            'view_inventory_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign inventory permissions to admin roles
        $adminRoles = ['super_admin', 'admin', 'school_admin'];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        $this->command->info('Inventory permissions created and assigned to admin roles.');
    }
}