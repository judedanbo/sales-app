<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Core System Administration
            [
                'name' => 'Super Admin',
                'email' => 'super@admin.com',
                'password' => 'password123',
                'user_type' => UserType::SUPER_ADMIN,
                'role' => 'super_admin',
                'is_active' => true,
            ],
            [
                'name' => 'System Administrator',
                'email' => 'system@admin.com',
                'password' => 'password123',
                'user_type' => UserType::SYSTEM_ADMIN,
                'role' => 'system_admin',
                'is_active' => true,
            ],

            // Educational Management
            [
                'name' => 'John School Admin',
                'email' => 'school@admin.com',
                'password' => 'password123',
                'user_type' => UserType::SCHOOL_ADMIN,
                'role' => 'school_admin',
                'is_active' => true,
                'department' => 'Administration',
            ],
            [
                'name' => 'Sarah Principal',
                'email' => 'principal@school.com',
                'password' => 'password123',
                'user_type' => UserType::PRINCIPAL,
                'role' => 'principal',
                'is_active' => true,
                'department' => 'Academic Leadership',
            ],
            [
                'name' => 'Dr. Michael Academic',
                'email' => 'academic@coordinator.com',
                'password' => 'password123',
                'user_type' => UserType::ACADEMIC_COORDINATOR,
                'role' => 'academic_coordinator',
                'is_active' => true,
                'department' => 'Academic Affairs',
            ],
            [
                'name' => 'Lisa Department Head',
                'email' => 'department@head.com',
                'password' => 'password123',
                'user_type' => UserType::DEPARTMENT_HEAD,
                'role' => 'department_head',
                'is_active' => true,
                'department' => 'Mathematics',
            ],
            [
                'name' => 'Emma Teacher',
                'email' => 'teacher@school.com',
                'password' => 'password123',
                'user_type' => UserType::TEACHER,
                'role' => 'teacher',
                'is_active' => true,
                'department' => 'Science',
            ],

            // Sales & Business
            [
                'name' => 'Robert Sales Manager',
                'email' => 'sales@manager.com',
                'password' => 'password123',
                'user_type' => UserType::SALES_MANAGER,
                'role' => 'sales_manager',
                'is_active' => true,
                'department' => 'Sales',
            ],
            [
                'name' => 'Jennifer Sales Rep',
                'email' => 'sales@rep.com',
                'password' => 'password123',
                'user_type' => UserType::SALES_REP,
                'role' => 'sales_rep',
                'is_active' => true,
                'department' => 'Sales',
            ],

            // Operational Roles
            [
                'name' => 'David Finance',
                'email' => 'finance@officer.com',
                'password' => 'password123',
                'user_type' => UserType::FINANCE_OFFICER,
                'role' => 'finance_officer',
                'is_active' => true,
                'department' => 'Finance',
            ],
            [
                'name' => 'Michelle HR Manager',
                'email' => 'hr@manager.com',
                'password' => 'password123',
                'user_type' => UserType::HR_MANAGER,
                'role' => 'hr_manager',
                'is_active' => true,
                'department' => 'Human Resources',
            ],
            [
                'name' => 'Alex IT Support',
                'email' => 'it@support.com',
                'password' => 'password123',
                'user_type' => UserType::IT_SUPPORT,
                'role' => 'it_support',
                'is_active' => true,
                'department' => 'Information Technology',
            ],
            [
                'name' => 'Rachel Data Analyst',
                'email' => 'data@analyst.com',
                'password' => 'password123',
                'user_type' => UserType::DATA_ANALYST,
                'role' => 'data_analyst',
                'is_active' => true,
                'department' => 'Business Intelligence',
            ],

            // Support & Compliance
            [
                'name' => 'Thomas Auditor',
                'email' => 'auditor@company.com',
                'password' => 'password123',
                'user_type' => UserType::AUDITOR,
                'role' => 'auditor',
                'is_active' => true,
                'department' => 'Compliance',
            ],
            [
                'name' => 'General Staff',
                'email' => 'staff@company.com',
                'password' => 'password123',
                'user_type' => UserType::STAFF,
                'role' => 'staff',
                'is_active' => true,
                'department' => 'General',
            ],
            [
                'name' => 'Guest Observer',
                'email' => 'guest@observer.com',
                'password' => 'password123',
                'user_type' => UserType::GUEST,
                'role' => 'guest',
                'is_active' => true,
                'department' => 'Visitor',
            ],

            // Legacy roles for backward compatibility (note: admin role will be created by UserRolesSeeder)
            [
                'name' => 'Legacy Admin',
                'email' => 'legacy@admin.com',
                'password' => 'password123',
                'user_type' => UserType::ADMIN,
                'role' => null, // Will be assigned by UserRolesSeeder later
                'is_active' => true,
                'department' => 'Legacy',
            ],
        ];

        foreach ($users as $userData) {
            // Create the user
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'user_type' => $userData['user_type'],
                    'is_active' => $userData['is_active'],
                    'department' => $userData['department'] ?? null,
                    'email_verified_at' => now(),
                ]
            );

            // Assign the role if it exists and the role exists in the system
            if (isset($userData['role']) && $userData['role']) {
                try {
                    $user->assignRole($userData['role']);
                } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                    $this->command->warn("Role '{$userData['role']}' does not exist for user '{$userData['name']}'");
                }
            }
        }

        $this->command->info('Sample users created and roles assigned successfully.');
    }
}
