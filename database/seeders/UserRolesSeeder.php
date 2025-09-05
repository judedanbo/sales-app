<?php

namespace Database\Seeders;

use App\Enums\UserType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Sales permissions
            'create_sales',
            'view_sales',
            'edit_sales',
            'void_sales',
            'view_all_sales',

            // Product permissions
            'create_products',
            'view_products',
            'edit_products',
            'delete_products',

            // Inventory permissions
            'view_inventory',
            'edit_inventory',
            'manage_stock',

            // User management permissions
            'create_users',
            'view_users',
            'edit_users',
            'delete_users',

            // School management permissions
            'create_schools',
            'view_schools',
            'edit_schools',
            'delete_schools',
            'manage_school_officials',
            'manage_school_classes',
            'manage_academic_years',

            // Reports and audit permissions
            'view_reports',
            'export_reports',
            'view_audit_logs',
            'view_activity_logs',

            // System permissions
            'manage_settings',
            'manage_roles',
            'manage_permissions',
            'system_administration',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles based on UserType enum
        $roles = [
            'staff' => [
                'create_sales',
                'view_sales',
                'view_products',
                'view_inventory',
            ],
            'admin' => [
                'create_sales',
                'view_sales',
                'edit_sales',
                'void_sales',
                'view_all_sales',
                'create_products',
                'view_products',
                'edit_products',
                'delete_products',
                'view_inventory',
                'edit_inventory',
                'manage_stock',
                'create_users',
                'view_users',
                'edit_users',
                'delete_users',
                'view_reports',
                'export_reports',
                'manage_settings',
            ],
            'audit' => [
                'view_sales',
                'view_all_sales',
                'view_products',
                'view_inventory',
                'view_users',
                'view_schools',
                'view_reports',
                'export_reports',
                'view_audit_logs',
                'view_activity_logs',
            ],
            'school_admin' => [
                'view_schools',
                'edit_schools',
                'manage_school_officials',
                'manage_school_classes',
                'manage_academic_years',
                'view_users',
                'create_users',
                'edit_users',
                'view_reports',
                'export_reports',
            ],
            'principal' => [
                'view_schools',
                'manage_school_classes',
                'manage_academic_years',
                'view_users',
                'view_reports',
            ],
            'teacher' => [
                'view_schools',
                'view_users',
                'manage_school_classes',
            ],
            'system_admin' => [
                'create_sales',
                'view_sales',
                'edit_sales',
                'void_sales',
                'view_all_sales',
                'create_products',
                'view_products',
                'edit_products',
                'delete_products',
                'view_inventory',
                'edit_inventory',
                'manage_stock',
                'create_users',
                'view_users',
                'edit_users',
                'delete_users',
                'create_schools',
                'view_schools',
                'edit_schools',
                'delete_schools',
                'manage_school_officials',
                'manage_school_classes',
                'manage_academic_years',
                'view_reports',
                'export_reports',
                'view_audit_logs',
                'view_activity_logs',
                'manage_settings',
                'manage_roles',
                'manage_permissions',
                'system_administration',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('User roles and permissions created successfully.');
    }
}
