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
    created_at: string;
    updated_at: string;
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
    is_active: boolean;
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
    is_active?: string;
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
