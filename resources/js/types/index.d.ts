import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    user_type?: UserType;
    user_type_label?: string;
    user_type_description?: string;
    school_id?: number | null;
    school?: School;
    phone?: string | null;
    department?: string | null;
    bio?: string | null;
    is_active: boolean;
    last_login_at?: string | null;
    display_name?: string;
    roles?: Role[];
    permissions?: Permission[];
    all_permissions?: string[];
    school_official?: SchoolOfficial;
    is_school_user?: boolean;
    is_system_user?: boolean;
    can_manage_schools?: boolean;
    can_manage_users?: boolean;
    created_by?: number | null;
    updated_by?: number | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface School {
    id: number;
    school_name: string;
    school_code: string;
    school_type: 'primary' | 'secondary' | 'higher_secondary' | 'k12';
    board_affiliation?: 'cbse' | 'icse' | 'state_board' | 'ib' | 'cambridge';
    established_date?: string;
    // principal_name?: string;
    // medium_of_instruction?: 'english' | 'hindi' | 'regional' | 'bilingual';
    total_students?: number;
    total_teachers?: number;
    website?: string;
    description?: string;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    contacts?: SchoolContact[];
    addresses?: SchoolAddress[];
    management?: SchoolManagement[];
    officials?: SchoolOfficial[];
}

export interface SchoolContact {
    id: number;
    school_id: number;
    contact_type: string;
    contact_value: string;
    is_primary: boolean;
}

export interface SchoolAddress {
    id: number;
    school_id: number;
    address_type: string;
    address_line1: string;
    address_line2?: string;
    city: string;
    state: string;
    country: string;
    postal_code: string;
    is_primary: boolean;
}

export interface SchoolManagement {
    id: number;
    school_id: number;
    management_type: string;
    organization_name?: string;
    registration_number?: string;
}

export interface SchoolOfficial {
    id: number;
    school_id: number;
    name: string;
    designation: string;
    contact_number?: string;
    email?: string;
    is_active: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
    prev_page_url?: string | null;
    next_page_url?: string | null;
    first_page_url?: string | null;
    last_page_url?: string | null;
}

export interface SchoolFilters {
    search?: string;
    school_type?: string;
    status?: string;
    board_affiliation?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface SchoolClass {
    id: number;
    school_id: number;
    class_name: string;
    class_code: string;
    grade_level: number;
    min_age?: number | null;
    max_age?: number | null;
    order_sequence: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    school?: School;
}

export interface AcademicYear {
    id: number;
    school_id: number;
    year_name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    school?: School;
}

// User Management Types
export type UserType = 'staff' | 'admin' | 'audit' | 'school_admin' | 'principal' | 'teacher' | 'system_admin';

export interface UserTypeOption {
    value: UserType;
    label: string;
    description?: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    display_name?: string;
    users_count?: number;
    permissions_count?: number;
    permissions?: Permission[];
    users?: User[];
    sample_users?: User[];
    permission_groups?: PermissionGroup[];
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    display_name?: string;
    category?: string;
    category_display?: string;
    action?: string;
    action_display?: string;
    roles_count?: number;
    roles?: Role[];
    sample_roles?: Role[];
    users_count?: number;
    source?: 'direct' | 'role' | 'unknown';
    created_at: string;
    updated_at: string;
}

export interface PermissionGroup {
    group: string;
    category?: string;
    display_name: string;
    permissions: Permission[];
    count: number;
}

export interface UserFilters {
    search?: string;
    user_type?: UserType;
    school_id?: number;
    is_active?: string;
    role?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface RoleFilters {
    search?: string;
    guard_name?: string;
    has_users?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface PermissionFilters {
    search?: string;
    guard_name?: string;
    category?: string;
    has_roles?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface UserStatistics {
    total_users: number;
    active_users: number;
    inactive_users: number;
    users_by_type: Record<
        UserType,
        {
            label: string;
            count: number;
        }
    >;
    users_by_school: Record<string, number>;
}

export interface RoleStatistics {
    total_roles: number;
    roles_with_users: number;
    roles_without_users: number;
    total_permissions: number;
    roles_by_guard: Record<string, number>;
    popular_roles: Array<{
        name: string;
        users_count: number;
    }>;
}

export interface PermissionStatistics {
    total_permissions: number;
    permissions_with_roles: number;
    permissions_without_roles: number;
    total_roles: number;
    usage_percentage: number;
    permissions_by_guard: Record<string, number>;
    permissions_by_category: Array<{
        category: string;
        display_name: string;
        count: number;
        percentage?: number;
    }>;
    most_used_permissions: Array<{
        name: string;
        display_name: string;
        roles_count: number;
        category: string;
    }>;
}
