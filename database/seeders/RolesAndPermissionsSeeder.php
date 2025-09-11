<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles($permissions);

        $this->command->info('Roles and permissions created successfully.');
    }

    private function createPermissions(): array
    {
        $permissionGroups = [
            // User Management
            'users' => [
                'view_users' => 'View user list and details',
                'create_users' => 'Create new users',
                'edit_users' => 'Edit user information',
                'delete_users' => 'Delete users',
                'restore_users' => 'Restore soft-deleted users',
                'force_delete_users' => 'Permanently delete users',
                'bulk_edit_users' => 'Perform bulk operations on users',
                'export_users' => 'Export user data',
                'impersonate_users' => 'Impersonate other users',
                'manage_user_status' => 'Activate/deactivate users',
                'reset_passwords' => 'Reset user passwords',
            ],

            // Profile & Account Management
            'profile' => [
                'edit_profile' => 'Edit own profile information',
                'delete_own_account' => 'Delete own account',
                'manage_security_settings' => 'Manage security settings',
                'view_own_activity' => 'View own activity logs',
                'manage_preferences' => 'Manage personal preferences',
            ],

            // Role & Permission Management
            'roles' => [
                'view_roles' => 'View roles and permissions',
                'create_roles' => 'Create new roles',
                'edit_roles' => 'Edit roles and permissions',
                'delete_roles' => 'Delete roles',
                'assign_roles' => 'Assign roles to users',
                'manage_permissions' => 'Manage role permissions',
                'view_permissions' => 'View available permissions',
                'assign_permissions_to_roles' => 'Assign permissions to roles',
                'bulk_manage_roles' => 'Perform bulk operations on roles',
            ],

            // School Management
            'schools' => [
                'view_schools' => 'View school information',
                'view_all_schools' => 'View all schools (not just assigned)',
                'create_schools' => 'Create new schools',
                'edit_schools' => 'Edit school information',
                'delete_schools' => 'Delete schools',
                'restore_schools' => 'Restore soft-deleted schools',
                'force_delete_schools' => 'Permanently delete schools',
                'bulk_edit_schools' => 'Perform bulk operations on schools',
                'export_schools' => 'Export school data',
                'assign_school_users' => 'Assign users to schools',
                'manage_school_staff' => 'Manage school staff assignments',
                'manage_school_officials' => 'Manage school officials',
                'manage_school_classes' => 'Manage school classes',
                'restore_school_classes' => 'Restore soft-deleted classes',
                'force_delete_school_classes' => 'Permanently delete classes',
                'manage_academic_years' => 'Manage academic years',
                'restore_academic_years' => 'Restore soft-deleted academic years',
                'force_delete_academic_years' => 'Permanently delete academic years',
                'manage_school_documents' => 'Manage school documents',
                'approve_school_changes' => 'Approve major school changes',
            ],

            // Sales Management
            'sales' => [
                'view_own_sales' => 'View own sales records',
                'view_all_sales' => 'View all sales records',
                'create_sales' => 'Create new sales',
                'edit_own_sales' => 'Edit own sales',
                'edit_all_sales' => 'Edit all sales',
                'delete_sales' => 'Delete sales records',
                'void_sales' => 'Void completed sales',
                'approve_discounts' => 'Approve discount requests',
                'manage_sales_targets' => 'Set and manage sales targets',
                'view_sales_analytics' => 'View sales analytics',
            ],

            // Category Management
            'categories' => [
                'view_categories' => 'View product categories',
                'create_categories' => 'Create new categories',
                'edit_categories' => 'Edit category information',
                'delete_categories' => 'Delete categories',
                'restore_categories' => 'Restore soft-deleted categories',
                'force_delete_categories' => 'Permanently delete categories',
                'manage_category_status' => 'Activate/deactivate categories',
                'manage_category_hierarchy' => 'Move and reorder categories',
                'bulk_edit_categories' => 'Perform bulk operations on categories',
                'view_category_reports' => 'View category statistics and reports',
                'edit_inactive_categories' => 'Edit inactive categories',
            ],

            // Product & Inventory Management
            'products' => [
                'view_products' => 'View product catalog',
                'create_products' => 'Create new products',
                'edit_products' => 'Edit product information',
                'delete_products' => 'Delete products',
                'manage_pricing' => 'Manage product pricing',
            ],

            'inventory' => [
                'view_inventory' => 'View inventory levels',
                'edit_inventory' => 'Edit inventory levels',
                'manage_stock' => 'Manage stock movements',
                'approve_stock_adjustments' => 'Approve stock adjustments',
            ],

            // Financial Management
            'finance' => [
                'view_financial_reports' => 'View financial reports',
                'manage_invoices' => 'Create and manage invoices',
                'manage_payments' => 'Process and manage payments',
                'view_budgets' => 'View budget information',
                'manage_budgets' => 'Create and edit budgets',
                'approve_expenses' => 'Approve expense requests',
                'export_financial_data' => 'Export financial data',
            ],

            // Reporting & Analytics
            'reports' => [
                'view_reports' => 'View standard reports',
                'view_advanced_reports' => 'View advanced analytics',
                'create_custom_reports' => 'Create custom reports',
                'export_reports' => 'Export report data',
                'schedule_reports' => 'Schedule automated reports',
                'view_dashboards' => 'View analytics dashboards',
            ],

            // Human Resources
            'hr' => [
                'view_staff_profiles' => 'View staff information',
                'manage_staff_profiles' => 'Edit staff information',
                'manage_leave_requests' => 'Handle leave requests',
                'view_hr_reports' => 'View HR reports',
                'manage_departments' => 'Manage departments',
            ],

            // Communication
            'communication' => [
                'send_notifications' => 'Send system notifications',
                'send_bulk_messages' => 'Send bulk messages',
                'manage_announcements' => 'Create and manage announcements',
                'view_communication_logs' => 'View communication history',
            ],

            // Audit Management
            'audit' => [
                'view_audit_trail' => 'View audit log entries',
                'view_audit_dashboard' => 'View audit analytics dashboard',
                'export_audit_logs' => 'Export audit data',
                'view_audit_logs' => 'View detailed audit logs',
                'manage_audit_settings' => 'Configure audit settings',
            ],

            // System Administration
            'system' => [
                'view_system_settings' => 'View system settings',
                'manage_system_settings' => 'Modify system settings',
                'view_activity_logs' => 'View user activity logs',
                'manage_integrations' => 'Manage third-party integrations',
                'manage_backups' => 'Manage system backups',
                'view_system_health' => 'View system health metrics',
                'access_developer_tools' => 'Access developer tools',
                'manage_api_keys' => 'Manage API keys',
                'system_administration' => 'Full system administration',
            ],

            // Support
            'support' => [
                'view_support_tickets' => 'View support tickets',
                'manage_support_tickets' => 'Handle support tickets',
                'access_help_center' => 'Access help documentation',
                'manage_faqs' => 'Manage FAQ content',
            ],
        ];

        $allPermissions = [];

        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $name => $description) {
                $permission = Permission::firstOrCreate(
                    ['name' => $name],
                    ['guard_name' => 'web']
                );
                $allPermissions[$name] = $permission;
            }
        }

        return $allPermissions;
    }

    private function createRoles(array $permissions): void
    {
        // Super Administrator - Complete system control
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );
        $superAdmin->syncPermissions(Permission::all());

        // System Administrator - Full access except critical system operations
        $systemAdmin = Role::firstOrCreate(
            ['name' => 'system_admin'],
            ['guard_name' => 'web']
        );
        $systemAdmin->syncPermissions(array_diff(array_keys($permissions), [
            'system_administration',
            'manage_backups',
            'access_developer_tools',
            'manage_api_keys',
        ]));

        // School Administrator - Full access to assigned schools
        $schoolAdmin = Role::firstOrCreate(
            ['name' => 'school_admin'],
            ['guard_name' => 'web']
        );
        $schoolAdmin->syncPermissions([
            'view_users',
            'create_users',
            'edit_users',
            'manage_user_status',
            'bulk_edit_users',
            'export_users',
            'view_roles',
            'assign_roles',
            'view_permissions',
            'assign_permissions_to_roles',
            'view_schools',
            'edit_schools',
            'manage_school_staff',
            'manage_school_officials',
            'manage_school_classes',
            'restore_school_classes',
            'force_delete_school_classes',
            'manage_academic_years',
            'restore_academic_years',
            'force_delete_academic_years',
            'manage_school_documents',
            'assign_school_users',
            'bulk_edit_schools',
            'export_schools',
            'view_products',
            'view_inventory',
            'view_reports',
            'export_reports',
            'view_dashboards',
            'view_staff_profiles',
            'manage_staff_profiles',
            'send_notifications',
            'manage_announcements',
            'view_audit_trail',
            'view_audit_dashboard',
            'view_audit_logs',
            'export_audit_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
            'manage_security_settings',
        ]);

        // Principal/Head Teacher - School oversight with limited edit capabilities
        $principal = Role::firstOrCreate(
            ['name' => 'principal'],
            ['guard_name' => 'web']
        );
        $principal->syncPermissions([
            'view_users',
            'view_roles',
            'view_permissions',
            'view_schools',
            'manage_school_classes',
            'restore_school_classes',
            'manage_academic_years',
            'restore_academic_years',
            'approve_school_changes',
            'manage_school_documents',
            'view_products',
            'view_inventory',
            'view_reports',
            'view_dashboards',
            'export_reports',
            'view_staff_profiles',
            'send_notifications',
            'manage_announcements',
            'view_audit_trail',
            'view_audit_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Academic Coordinator - Academic program management
        $academicCoordinator = Role::firstOrCreate(
            ['name' => 'academic_coordinator'],
            ['guard_name' => 'web']
        );
        $academicCoordinator->syncPermissions([
            'view_users',
            'view_schools',
            'manage_school_classes',
            'restore_school_classes',
            'manage_academic_years',
            'restore_academic_years',
            'manage_school_documents',
            'view_reports',
            'export_reports',
            'view_dashboards',
            'view_staff_profiles',
            'view_audit_trail',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
            'send_notifications',
        ]);

        // Department Head - Department-specific management
        $departmentHead = Role::firstOrCreate(
            ['name' => 'department_head'],
            ['guard_name' => 'web']
        );
        $departmentHead->syncPermissions([
            'view_users',
            'create_users',
            'edit_users',
            'bulk_edit_users',
            'view_schools',
            'manage_school_classes',
            'restore_school_classes',
            'manage_academic_years',
            'view_reports',
            'export_reports',
            'view_dashboards',
            'view_staff_profiles',
            'manage_departments',
            'send_notifications',
            'view_audit_trail',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Sales Manager - Full sales module access
        $salesManager = Role::firstOrCreate(
            ['name' => 'sales_manager'],
            ['guard_name' => 'web']
        );
        $salesManager->syncPermissions([
            'view_users',
            'create_users',
            'edit_users',
            'bulk_edit_users',
            'export_users',
            'view_all_sales',
            'create_sales',
            'edit_all_sales',
            'delete_sales',
            'void_sales',
            'approve_discounts',
            'manage_sales_targets',
            'view_sales_analytics',
            'view_products',
            'edit_products',
            'manage_pricing',
            'view_inventory',
            'view_financial_reports',
            'manage_invoices',
            'view_reports',
            'view_advanced_reports',
            'export_reports',
            'view_dashboards',
            'view_audit_trail',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Sales Representative - Own sales management
        $salesRep = Role::firstOrCreate(
            ['name' => 'sales_rep'],
            ['guard_name' => 'web']
        );
        $salesRep->syncPermissions([
            'view_own_sales',
            'create_sales',
            'edit_own_sales',
            'view_products',
            'view_inventory',
            'view_reports',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Finance Officer - Financial operations
        $financeOfficer = Role::firstOrCreate(
            ['name' => 'finance_officer'],
            ['guard_name' => 'web']
        );
        $financeOfficer->syncPermissions([
            'view_all_sales',
            'void_sales',
            'view_financial_reports',
            'manage_invoices',
            'manage_payments',
            'view_budgets',
            'manage_budgets',
            'approve_expenses',
            'export_financial_data',
            'view_reports',
            'view_advanced_reports',
            'export_reports',
            'view_audit_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // HR Manager - Human resources management
        $hrManager = Role::firstOrCreate(
            ['name' => 'hr_manager'],
            ['guard_name' => 'web']
        );
        $hrManager->syncPermissions([
            'view_users',
            'create_users',
            'edit_users',
            'manage_user_status',
            'restore_users',
            'bulk_edit_users',
            'export_users',
            'reset_passwords',
            'impersonate_users',
            'view_staff_profiles',
            'manage_staff_profiles',
            'manage_leave_requests',
            'view_hr_reports',
            'manage_departments',
            'view_reports',
            'export_reports',
            'view_dashboards',
            'send_notifications',
            'send_bulk_messages',
            'view_audit_trail',
            'view_audit_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // IT Support - Technical support
        $itSupport = Role::firstOrCreate(
            ['name' => 'it_support'],
            ['guard_name' => 'web']
        );
        $itSupport->syncPermissions([
            'view_users',
            'reset_passwords',
            'manage_user_status',
            'view_system_settings',
            'view_system_health',
            'view_activity_logs',
            'view_support_tickets',
            'manage_support_tickets',
            'access_help_center',
            'manage_faqs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Data Analyst - Analytics and reporting
        $dataAnalyst = Role::firstOrCreate(
            ['name' => 'data_analyst'],
            ['guard_name' => 'web']
        );
        $dataAnalyst->syncPermissions([
            'view_users',
            'export_users',
            'view_schools',
            'view_all_schools',
            'export_schools',
            'view_all_sales',
            'view_sales_analytics',
            'view_products',
            'view_inventory',
            'view_financial_reports',
            'export_financial_data',
            'view_reports',
            'view_advanced_reports',
            'create_custom_reports',
            'export_reports',
            'schedule_reports',
            'view_dashboards',
            'view_hr_reports',
            'view_audit_trail',
            'view_audit_dashboard',
            'view_audit_logs',
            'export_audit_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Auditor - Compliance and audit
        $auditor = Role::firstOrCreate(
            ['name' => 'auditor'],
            ['guard_name' => 'web']
        );
        $auditor->syncPermissions([
            'view_users',
            'view_roles',
            'view_permissions',
            'view_schools',
            'view_all_schools',
            'export_schools',
            'view_all_sales',
            'view_products',
            'view_inventory',
            'view_financial_reports',
            'export_financial_data',
            'view_reports',
            'view_advanced_reports',
            'export_reports',
            'view_dashboards',
            'view_audit_trail',
            'view_audit_dashboard',
            'view_audit_logs',
            'export_audit_logs',
            'view_activity_logs',
            'view_communication_logs',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Teacher - Class and student management
        $teacher = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['guard_name' => 'web']
        );
        $teacher->syncPermissions([
            'view_users',
            'view_schools',
            'manage_school_classes',
            'manage_academic_years',
            'view_reports',
            'view_dashboards',
            'send_notifications',
            'view_audit_trail',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Staff - Basic operational access
        $staff = Role::firstOrCreate(
            ['name' => 'staff'],
            ['guard_name' => 'web']
        );
        $staff->syncPermissions([
            'view_own_sales',
            'create_sales',
            'edit_own_sales',
            'view_products',
            'view_inventory',
            'edit_profile',
            'delete_own_account',
            'view_own_activity',
            'manage_preferences',
        ]);

        // Guest/Observer - Very limited access
        $guest = Role::firstOrCreate(
            ['name' => 'guest'],
            ['guard_name' => 'web']
        );
        $guest->syncPermissions([
            'view_schools',
            'view_products',
            'access_help_center',
        ]);
    }
}
