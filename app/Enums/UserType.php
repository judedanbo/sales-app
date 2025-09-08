<?php

namespace App\Enums;

enum UserType: string
{
    // Core System Roles
    case SUPER_ADMIN = 'super_admin';
    case SYSTEM_ADMIN = 'system_admin';

    // Educational Management Roles
    case SCHOOL_ADMIN = 'school_admin';
    case PRINCIPAL = 'principal';
    case ACADEMIC_COORDINATOR = 'academic_coordinator';
    case DEPARTMENT_HEAD = 'department_head';
    case TEACHER = 'teacher';

    // Sales & Business Roles
    case SALES_MANAGER = 'sales_manager';
    case SALES_REP = 'sales_rep';

    // Operational Roles
    case FINANCE_OFFICER = 'finance_officer';
    case HR_MANAGER = 'hr_manager';
    case IT_SUPPORT = 'it_support';
    case DATA_ANALYST = 'data_analyst';

    // Support & Compliance Roles
    case AUDITOR = 'auditor';
    case STAFF = 'staff';
    case GUEST = 'guest';

    // Legacy roles (for backward compatibility)
    case ADMIN = 'admin';
    case AUDIT = 'audit';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::SYSTEM_ADMIN => 'System Administrator',
            self::SCHOOL_ADMIN => 'School Administrator',
            self::PRINCIPAL => 'Principal/Head Teacher',
            self::ACADEMIC_COORDINATOR => 'Academic Coordinator',
            self::DEPARTMENT_HEAD => 'Department Head',
            self::TEACHER => 'Teacher',
            self::SALES_MANAGER => 'Sales Manager',
            self::SALES_REP => 'Sales Representative',
            self::FINANCE_OFFICER => 'Finance Officer',
            self::HR_MANAGER => 'HR Manager',
            self::IT_SUPPORT => 'IT Support',
            self::DATA_ANALYST => 'Data Analyst',
            self::AUDITOR => 'Auditor',
            self::STAFF => 'Staff',
            self::GUEST => 'Guest/Observer',
            // Legacy
            self::ADMIN => 'Administrator (Legacy)',
            self::AUDIT => 'Auditor (Legacy)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Complete system control with all permissions and overrides',
            self::SYSTEM_ADMIN => 'Full system access for daily operations and management',
            self::SCHOOL_ADMIN => 'Complete management of assigned schools and their operations',
            self::PRINCIPAL => 'School leadership, oversight, and academic management',
            self::ACADEMIC_COORDINATOR => 'Manage academic programs, curriculum, and class structures',
            self::DEPARTMENT_HEAD => 'Lead department operations, staff, and resource management',
            self::TEACHER => 'Manage classes, students, and teaching-related activities',
            self::SALES_MANAGER => 'Full sales team management and sales operations oversight',
            self::SALES_REP => 'Manage own sales, customers, and sales activities',
            self::FINANCE_OFFICER => 'Handle financial operations, budgets, and financial reporting',
            self::HR_MANAGER => 'Manage human resources, staff, and organizational matters',
            self::IT_SUPPORT => 'Provide technical support, system maintenance, and user assistance',
            self::DATA_ANALYST => 'Analyze data, generate reports, and provide business insights',
            self::AUDITOR => 'Ensure compliance, conduct audits, and maintain audit trails',
            self::STAFF => 'Basic operational access for daily tasks and activities',
            self::GUEST => 'Limited read-only access for observers and temporary users',
            // Legacy
            self::ADMIN => 'Full CRUD on all entities, void sales, user management, reports (Legacy)',
            self::AUDIT => 'Read-only access to all data, audit trail access, export reports (Legacy)',
        };
    }

    public function getHierarchyLevel(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 1,
            self::SYSTEM_ADMIN => 2,
            self::SCHOOL_ADMIN => 3,
            self::PRINCIPAL => 4,
            self::SALES_MANAGER, self::HR_MANAGER => 5,
            self::ACADEMIC_COORDINATOR, self::DEPARTMENT_HEAD, self::FINANCE_OFFICER => 6,
            self::DATA_ANALYST, self::IT_SUPPORT => 7,
            self::TEACHER, self::SALES_REP => 8,
            self::AUDITOR => 9,
            self::STAFF => 10,
            self::GUEST => 11,
            // Legacy
            self::ADMIN => 3,
            self::AUDIT => 9,
        };
    }

    public function canManageUserType(self $targetUserType): bool
    {
        return $this->getHierarchyLevel() < $targetUserType->getHierarchyLevel();
    }

    public static function getManagerialTypes(): array
    {
        return [
            self::SUPER_ADMIN,
            self::SYSTEM_ADMIN,
            self::SCHOOL_ADMIN,
            self::PRINCIPAL,
            self::SALES_MANAGER,
            self::HR_MANAGER,
            self::DEPARTMENT_HEAD,
            self::ACADEMIC_COORDINATOR,
        ];
    }

    public static function getEducationalTypes(): array
    {
        return [
            self::SCHOOL_ADMIN,
            self::PRINCIPAL,
            self::ACADEMIC_COORDINATOR,
            self::DEPARTMENT_HEAD,
            self::TEACHER,
        ];
    }

    public static function getSalesTypes(): array
    {
        return [
            self::SALES_MANAGER,
            self::SALES_REP,
        ];
    }
}
