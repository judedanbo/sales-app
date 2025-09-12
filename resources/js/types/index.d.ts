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

export type Permissions = Permission[];

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
    total: number;
    active: number;
    inactive: number;
    recent: number;
    by_type: Record<
        string,
        {
            label: string;
            count: number;
        }
    >;
    by_school: Array<{
        school_id: number;
        school_name: string;
        count: number;
    }>;
}

export interface RoleStatistics {
    total: number;
    with_users: number;
    without_users: number;
    with_permissions: number;
    total_permissions: number;
    recent: number;
    popular_roles: Array<{
        name: string;
        display_name: string;
        users_count: number;
        guard_name: string;
    }>;
    by_guard: Record<string, number>;
}

export interface PermissionStatistics {
    total: number;
    with_roles: number;
    without_roles: number;
    categories: number;
    usage_percentage: number;
    by_category: Array<{
        category: string;
        label: string;
        count: number;
        percentage: number;
    }>;
    by_guard?: Record<string, number>;
    most_used: Array<{
        name: string;
        display_name: string;
        roles_count: number;
        category: string;
    }>;
}

// Audit-related types
export interface Audit {
    id: number;
    user_type?: string;
    user_id?: number;
    user?: User;
    event: 'created' | 'updated' | 'deleted' | 'restored';
    auditable_type: string;
    auditable_id: number;
    old_values?: Record<string, any>;
    new_values?: Record<string, any>;
    url?: string;
    ip_address?: string;
    user_agent?: string;
    tags?: string;
    created_at: string;
    updated_at: string;
    changes_summary?: string[];
}

export interface AuditFilters {
    auditable_type?: string;
    auditable_id?: number;
    user_id?: number;
    event?: string;
    from_date?: string;
    to_date?: string;
    search?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface AuditStatistics {
    stats: {
        total_audits: number;
        today_audits: number;
        this_week_audits: number;
        this_month_audits: number;
    };
    events_breakdown: Record<string, number>;
    models_breakdown: Record<string, number>;
    top_users: Array<{
        user: User;
        audit_count: number;
    }>;
}

export interface AuditChange {
    field: string;
    old_value: any;
    new_value: any;
    type: 'added' | 'modified' | 'removed';
}

export interface AuditModel {
    class: string;
    name: string;
    count: number;
}

export interface AuditTimelineEntry {
    id: number;
    event: string;
    user?: User;
    created_at: string;
    old_values?: Record<string, any>;
    new_values?: Record<string, any>;
    changes_summary: string[];
}

// Category Management Types
export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    parent_id?: number | null;
    sort_order: number;
    is_active: boolean;
    color?: string | null;
    icon?: string | null;
    created_by?: number | null;
    updated_by?: number | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attributes
    full_name?: string;
    depth?: number;
    is_root?: boolean;
    is_leaf?: boolean;
    has_children?: boolean;
    breadcrumb?: CategoryBreadcrumb[];

    // Relationships
    parent?: Category;
    children?: Category[];
    children_count?: number;
    active_children_count?: number;
    products_count?: number;

    // Creator/updater info
    created_by_user?: {
        id: number;
        name: string;
    };
    updated_by_user?: {
        id: number;
        name: string;
    };

    // Status info
    status?: {
        is_active: boolean;
        label: string;
        class: string;
    };
}

export interface CategoryBreadcrumb {
    id: number;
    name: string;
    slug: string;
}

export interface CategoryFilters {
    search?: string;
    parent_id?: string | number;
    is_active?: string | boolean;
    created_from?: string;
    created_to?: string;
    updated_from?: string;
    updated_to?: string;
    created_by?: string | number;
    has_children?: string | boolean;
    has_products?: string | boolean;
    sort_order_from?: string | number;
    sort_order_to?: string | number;
    include_deleted?: string | boolean;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    page?: number;
}
