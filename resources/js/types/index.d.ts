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
    is_active: boolean;
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

// Product Management Types
export interface Product {
    id: number;
    sku: string;
    name: string;
    description?: string;
    category_id: number;
    category?: Category;
    status: 'active' | 'inactive' | 'discontinued';
    unit_price: number;
    unit_type: string;
    reorder_level?: number;
    tax_rate: number;
    weight?: number;
    dimensions?: {
        length?: number;
        width?: number;
        height?: number;
    };
    color?: string;
    brand?: string;
    attributes?: Record<string, any>;
    barcode?: string;
    image_url?: string;
    gallery?: string[];
    meta_title?: string;
    meta_description?: string;
    tags?: string[];
    created_by?: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attributes
    formatted_price?: string;
    formatted_tax_rate?: string;
    formatted_weight?: string;
    low_stock?: boolean;
    status_label?: string;
    status_color?: string;

    // Relationships
    creator?: User;
    updater?: User;
    current_price?: ProductPrice;
    prices?: ProductPrice[];
    classProductRequirements?: ClassProductRequirement[];
    inventory?: ProductInventory;
}

export interface ProductPrice {
    id: number;
    product_id: number;
    version_number: number;
    price: number;
    final_price: number;
    status: 'draft' | 'pending' | 'active' | 'expired' | 'rejected';
    valid_from: string;
    valid_to?: string;
    created_by: number;
    approved_by?: number;
    approved_at?: string;
    approval_notes?: string;
    cost_price?: number;
    markup_percentage?: number;
    currency: string;
    bulk_discounts?: Array<{
        min_quantity: number;
        discount_percentage: number;
    }>;
    notes?: string;
    created_at: string;
    updated_at: string;

    // Computed attributes
    formatted_price?: string;
    formatted_final_price?: string;
    profit_margin?: number;
    is_valid?: boolean;
    is_approved?: boolean;

    // Relationships
    product?: Product;
    creator?: User;
    approver?: User;
}

export interface ClassProductRequirement {
    id: number;
    school_id: number;
    academic_year_id: number;
    class_id: number;
    product_id: number;
    is_required: boolean;
    min_quantity?: number;
    max_quantity?: number;
    recommended_quantity?: number;
    required_by?: string;
    is_active: boolean;
    description?: string;
    notes?: string;
    estimated_cost?: number;
    budget_allocation?: number;
    priority: 'low' | 'medium' | 'high' | 'critical';
    requirement_category: 'textbooks' | 'stationery' | 'uniforms' | 'supplies' | 'technology' | 'sports' | 'art' | 'science' | 'other';
    created_by: number;
    approved_by?: number;
    approved_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    // Computed attributes
    formatted_estimated_cost?: string;
    formatted_budget_allocation?: string;
    total_estimated_cost?: number;
    is_overdue?: boolean;
    is_upcoming?: boolean;
    is_approved?: boolean;
    priority_color?: string;

    // Relationships
    school?: School;
    academicYear?: AcademicYear;
    schoolClass?: SchoolClass;
    product?: Product;
    creator?: User;
    approver?: User;
}

export interface ProductInventory {
    id: number;
    product_id: number;
    quantity_on_hand: number;
    quantity_available: number;
    quantity_reserved: number;
    minimum_stock_level?: number;
    maximum_stock_level?: number;
    reorder_point?: number;
    reorder_quantity?: number;
    last_stock_count?: string;
    last_movement_at?: string;
    created_at: string;
    updated_at: string;

    // Computed attributes
    is_low_stock?: boolean;
    is_out_of_stock?: boolean;
    stock_status?: 'in_stock' | 'low_stock' | 'out_of_stock' | 'reorder_needed';

    // Relationships
    product?: Product;
}

export interface ProductFilters {
    search?: string;
    category_id?: string | number;
    status?: string;
    price_from?: string | number;
    price_to?: string | number;
    low_stock?: string | boolean;
    created_from?: string;
    created_to?: string;
    updated_from?: string;
    updated_to?: string;
    created_by?: string | number;
    has_image?: string | boolean;
    has_barcode?: string | boolean;
    unit_type?: string;
    brand?: string;
    include_deleted?: string | boolean;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    page?: number;
}

export interface ProductStatistics {
    total: number;
    active: number;
    inactive: number;
    discontinued: number;
    low_stock: number;
    out_of_stock: number;
    recent: number;
    by_category: Array<{
        category_id: number;
        category_name: string;
        count: number;
    }>;
    by_status: Record<string, number>;
    by_price_range: Array<{
        range: string;
        count: number;
    }>;
    value_breakdown: {
        total_value: number;
        average_price: number;
        highest_price: number;
        lowest_price: number;
    };
}

// Product Variant Types
export interface ProductVariant {
    id: number;
    product_id: number;
    sku: string;
    name?: string;
    size?: string;
    color?: string;
    material?: string;
    attributes?: Record<string, any>;
    unit_price?: number;
    cost_price?: number;
    weight?: number;
    dimensions?: {
        length?: number;
        width?: number;
        height?: number;
    };
    image_url?: string;
    gallery?: string[];
    status: 'active' | 'inactive' | 'discontinued';
    is_default: boolean;
    sort_order: number;
    barcode?: string;
    created_by?: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;

    // Computed attributes
    effective_price?: number;
    formatted_price?: string;
    display_name?: string;
    variant_attributes?: string;
    has_inventory?: boolean;
    current_stock?: number;
    is_low_stock?: boolean;
    is_available?: boolean;
    profit_margin?: number;

    // Relationships
    product?: Product;
    inventory?: ProductInventory;
    creator?: User;
    updater?: User;
    stock_movements?: StockMovement[];
}

export interface ProductVariantFilters {
    search?: string;
    product_id?: string | number;
    status?: string;
    size?: string;
    color?: string;
    material?: string;
    price_from?: string | number;
    price_to?: string | number;
    low_stock?: string | boolean;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

// Stock Movement Types
export interface StockMovement {
    id: number;
    product_id: number;
    product_variant_id?: number;
    movement_type: 'in' | 'out' | 'adjustment' | 'transfer' | 'return' | 'damage' | 'sale' | 'purchase';
    quantity: number;
    unit_cost?: number;
    total_cost?: number;
    movement_date: string;
    reference_type?: string;
    reference_id?: number;
    reference_number?: string;
    notes?: string;
    created_by: number;
    approved_by?: number;
    approved_at?: string;
    created_at: string;
    updated_at: string;

    // Computed attributes
    formatted_quantity?: string;
    formatted_cost?: string;
    movement_direction?: 'positive' | 'negative';
    movement_type_label?: string;
    movement_type_color?: string;

    // Relationships
    product?: Product;
    variant?: ProductVariant;
    creator?: User;
    approver?: User;
}

export interface StockMovementFilters {
    search?: string;
    product_id?: string | number;
    variant_id?: string | number;
    movement_type?: string;
    date_from?: string;
    date_to?: string;
    created_by?: string | number;
    reference_type?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

// Pricing Rule Types
export interface PricingRule {
    id: number;
    name: string;
    description?: string;
    rule_type: 'bulk_discount' | 'time_based' | 'customer_group' | 'category_discount' | 'clearance';
    status: 'active' | 'inactive' | 'scheduled' | 'expired';
    priority: number;
    valid_from: string;
    valid_to?: string;
    conditions: PricingRuleCondition[];
    actions: PricingRuleAction[];
    usage_limit?: number;
    usage_count: number;
    can_be_combined: boolean;
    applies_to: 'all_products' | 'specific_products' | 'categories';
    product_ids?: number[];
    category_ids?: number[];
    created_by: number;
    updated_by?: number;
    created_at: string;
    updated_at: string;

    // Computed attributes
    is_active?: boolean;
    is_valid?: boolean;
    formatted_discount?: string;
    usage_percentage?: number;

    // Relationships
    creator?: User;
    updater?: User;
    products?: Product[];
    categories?: Category[];
}

export interface PricingRuleCondition {
    field: string;
    operator: 'equals' | 'greater_than' | 'less_than' | 'between' | 'in' | 'not_in';
    value: any;
    logic?: 'and' | 'or';
}

export interface PricingRuleAction {
    type: 'percentage_discount' | 'fixed_discount' | 'fixed_price' | 'buy_x_get_y';
    value: number;
    applies_to?: 'cart' | 'product' | 'category';
    max_discount?: number;
    free_quantity?: number;
}

export interface PricingRuleFilters {
    search?: string;
    rule_type?: string;
    status?: string;
    valid_from?: string;
    valid_to?: string;
    applies_to?: string;
    created_by?: string | number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

// Inventory Statistics Types
export interface InventoryStatistics {
    total_products: number;
    total_variants: number;
    total_stock_value: number;
    low_stock_items: number;
    out_of_stock_items: number;
    recent_movements: number;
    by_movement_type: Record<string, number>;
    by_status: Record<string, number>;
    stock_value_by_category: Array<{
        category_id: number;
        category_name: string;
        total_value: number;
        total_quantity: number;
    }>;
    top_products_by_value: Array<{
        product: Product;
        total_value: number;
        quantity_on_hand: number;
    }>;
    recent_stock_movements: StockMovement[];
}

// Pricing Statistics Types
export interface PricingStatistics {
    total_rules: number;
    active_rules: number;
    expired_rules: number;
    total_discount_amount: number;
    rules_by_type: Record<string, number>;
    most_used_rules: Array<{
        rule: PricingRule;
        usage_count: number;
        total_discount: number;
    }>;
    upcoming_expirations: PricingRule[];
}

// Bulk Operations Types
export interface BulkOperation {
    type: 'update_prices' | 'update_stock' | 'update_status' | 'apply_discount' | 'export' | 'import';
    items: number[];
    data: Record<string, any>;
    progress?: number;
    status?: 'pending' | 'processing' | 'completed' | 'failed';
    result?: {
        success: number;
        failed: number;
        errors: string[];
    };
}

export interface BulkPriceUpdate {
    product_ids: number[];
    price_change_type: 'percentage' | 'fixed_amount' | 'set_price';
    price_change_value: number;
    apply_to_variants: boolean;
    valid_from?: string;
    valid_to?: string;
    notes?: string;
}

export interface BulkStockUpdate {
    items: Array<{
        product_id: number;
        variant_id?: number;
        quantity: number;
        movement_type: 'in' | 'out' | 'adjustment';
        notes?: string;
    }>;
    movement_date: string;
    reference_number?: string;
    notes?: string;
}
