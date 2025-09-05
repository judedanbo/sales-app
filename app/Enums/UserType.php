<?php

namespace App\Enums;

enum UserType: string
{
    case STAFF = 'staff';
    case ADMIN = 'admin';
    case AUDIT = 'audit';
    case SCHOOL_ADMIN = 'school_admin';
    case PRINCIPAL = 'principal';
    case TEACHER = 'teacher';
    case SYSTEM_ADMIN = 'system_admin';

    public function label(): string
    {
        return match ($this) {
            self::STAFF => 'Staff',
            self::ADMIN => 'Administrator',
            self::AUDIT => 'Auditor',
            self::SCHOOL_ADMIN => 'School Administrator',
            self::PRINCIPAL => 'Principal',
            self::TEACHER => 'Teacher',
            self::SYSTEM_ADMIN => 'System Administrator',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::STAFF => 'Create sales, view products, manage own sales',
            self::ADMIN => 'Full CRUD on all entities, void sales, user management, reports',
            self::AUDIT => 'Read-only access to all data, audit trail access, export reports',
            self::SCHOOL_ADMIN => 'School-level management for school officials',
            self::PRINCIPAL => 'Academic oversight and school operations',
            self::TEACHER => 'Teaching-related functions and student management',
            self::SYSTEM_ADMIN => 'Full system access across all schools',
        };
    }
}
