<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/ui/PageHeader.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show } from '@/routes/categories-simple';
import { type BreadcrumbItem, type Category, type CategoryFilters, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    Calendar,
    ChevronDown,
    ChevronRight,
    Edit,
    Eye,
    Filter,
    Folder,
    FolderOpen,
    MoreHorizontal,
    Package,
    Plus,
    RotateCcw,
    Search,
    Settings,
    Trash2,
    TreePine,
    User,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    categories: PaginatedData<Category>;
    filters: CategoryFilters;
    parentCategories: Array<{ id: number; name: string; full_name?: string }>;
    creators: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: index().url,
    },
];

// Local filter state
const localFilters = ref<CategoryFilters>({
    search: props.filters.search || '',
    parent_id: props.filters.parent_id || '',
    is_active: props.filters.is_active || '',
    created_from: props.filters.created_from || '',
    created_to: props.filters.created_to || '',
    updated_from: props.filters.updated_from || '',
    updated_to: props.filters.updated_to || '',
    created_by: props.filters.created_by || '',
    has_children: props.filters.has_children || '',
    has_products: props.filters.has_products || '',
    sort_order_from: props.filters.sort_order_from || '',
    sort_order_to: props.filters.sort_order_to || '',
    include_deleted: props.filters.include_deleted || '',
    sort_by: props.filters.sort_by || 'sort_order',
    sort_direction: props.filters.sort_direction || 'asc',
});

// Update local filters when props change
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            search: newFilters.search || '',
            parent_id: newFilters.parent_id || '',
            is_active: newFilters.is_active || '',
            created_from: newFilters.created_from || '',
            created_to: newFilters.created_to || '',
            updated_from: newFilters.updated_from || '',
            updated_to: newFilters.updated_to || '',
            created_by: newFilters.created_by || '',
            has_children: newFilters.has_children || '',
            has_products: newFilters.has_products || '',
            sort_order_from: newFilters.sort_order_from || '',
            sort_order_to: newFilters.sort_order_to || '',
            include_deleted: newFilters.include_deleted || '',
            sort_by: newFilters.sort_by || 'sort_order',
            sort_direction: newFilters.sort_direction || 'asc',
        };
    },
    { immediate: true },
);

const isLoading = ref(false);
const selectedCategories = ref<number[]>([]);
const showAdvancedFilters = ref(false);

// Statistics computed from data
const stats = computed(() => ({
    total: props.categories.total,
    active: props.categories.data.filter((cat) => cat.is_active).length,
    inactive: props.categories.data.filter((cat) => !cat.is_active).length,
    root: props.categories.data.filter((cat) => !cat.parent_id).length,
    withChildren: props.categories.data.filter((cat) => cat.children_count && cat.children_count > 0).length,
}));

// Active filters for chips display
const activeFilters = computed(() => {
    const filters = [];

    if (localFilters.value.search) {
        filters.push({ key: 'search', label: `Search: "${localFilters.value.search}"`, value: localFilters.value.search });
    }

    if (localFilters.value.parent_id) {
        const parentName =
            localFilters.value.parent_id === 'null'
                ? 'Root Categories'
                : props.parentCategories.find((p) => p.id.toString() === localFilters.value.parent_id?.toString())?.name || 'Unknown';
        filters.push({ key: 'parent_id', label: `Parent: ${parentName}`, value: localFilters.value.parent_id });
    }

    if (localFilters.value.is_active) {
        const statusLabel = localFilters.value.is_active === '1' ? 'Active Only' : 'Inactive Only';
        filters.push({ key: 'is_active', label: `Status: ${statusLabel}`, value: localFilters.value.is_active });
    }

    if (localFilters.value.created_by) {
        const creatorName = props.creators.find((c) => c.id.toString() === localFilters.value.created_by?.toString())?.name || 'Unknown';
        filters.push({ key: 'created_by', label: `Creator: ${creatorName}`, value: localFilters.value.created_by });
    }

    if (localFilters.value.has_children) {
        const label = localFilters.value.has_children === '1' ? 'With Children' : 'Without Children';
        filters.push({ key: 'has_children', label, value: localFilters.value.has_children });
    }

    if (localFilters.value.has_products) {
        const label = localFilters.value.has_products === '1' ? 'With Products' : 'Without Products';
        filters.push({ key: 'has_products', label, value: localFilters.value.has_products });
    }

    if (localFilters.value.include_deleted === '1') {
        filters.push({ key: 'include_deleted', label: 'Including Deleted', value: localFilters.value.include_deleted });
    }

    return filters;
});

// Check if any advanced filters are active
const hasAdvancedFilters = computed(() => {
    return !!(
        localFilters.value.created_from ||
        localFilters.value.created_to ||
        localFilters.value.updated_from ||
        localFilters.value.updated_to ||
        localFilters.value.created_by ||
        localFilters.value.has_children ||
        localFilters.value.has_products ||
        localFilters.value.sort_order_from ||
        localFilters.value.sort_order_to ||
        localFilters.value.include_deleted
    );
});

// Selection handlers
const toggleSelection = (categoryId: number) => {
    const index = selectedCategories.value.indexOf(categoryId);
    if (index > -1) {
        selectedCategories.value.splice(index, 1);
    } else {
        selectedCategories.value.push(categoryId);
    }
};

const selectAll = () => {
    if (selectedCategories.value.length === props.categories.data.length) {
        selectedCategories.value = [];
    } else {
        selectedCategories.value = props.categories.data.map((category) => category.id);
    }
};

const clearSelection = () => {
    selectedCategories.value = [];
};

// Clear selection when page changes
watch(
    () => props.categories.current_page,
    () => clearSelection(),
);

// Debounced search
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch for search changes specifically with debouncing
watch(
    () => localFilters.value.search,
    () => {
        debouncedSearch();
    },
);

// Watch for other filter changes
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        // Skip if this is initial load
        if (!oldFilters) return;

        // Only apply filters immediately for non-search changes
        if (
            newFilters.parent_id !== oldFilters.parent_id ||
            newFilters.is_active !== oldFilters.is_active ||
            newFilters.created_from !== oldFilters.created_from ||
            newFilters.created_to !== oldFilters.created_to ||
            newFilters.updated_from !== oldFilters.updated_from ||
            newFilters.updated_to !== oldFilters.updated_to ||
            newFilters.created_by !== oldFilters.created_by ||
            newFilters.has_children !== oldFilters.has_children ||
            newFilters.has_products !== oldFilters.has_products ||
            newFilters.sort_order_from !== oldFilters.sort_order_from ||
            newFilters.sort_order_to !== oldFilters.sort_order_to ||
            newFilters.include_deleted !== oldFilters.include_deleted
        ) {
            applyFilters();
        }
    },
    { deep: true },
);

// Apply filters
function applyFilters() {
    isLoading.value = true;

    const params: Record<string, any> = {};

    if (localFilters.value.search) params.search = localFilters.value.search;
    if (localFilters.value.parent_id && localFilters.value.parent_id !== '') params.parent_id = localFilters.value.parent_id;
    if (localFilters.value.is_active && localFilters.value.is_active !== '') params.is_active = localFilters.value.is_active;
    if (localFilters.value.created_from) params.created_from = localFilters.value.created_from;
    if (localFilters.value.created_to) params.created_to = localFilters.value.created_to;
    if (localFilters.value.updated_from) params.updated_from = localFilters.value.updated_from;
    if (localFilters.value.updated_to) params.updated_to = localFilters.value.updated_to;
    if (localFilters.value.created_by && localFilters.value.created_by !== '') params.created_by = localFilters.value.created_by;
    if (localFilters.value.has_children && localFilters.value.has_children !== '') params.has_children = localFilters.value.has_children;
    if (localFilters.value.has_products && localFilters.value.has_products !== '') params.has_products = localFilters.value.has_products;
    if (localFilters.value.sort_order_from) params.sort_order_from = localFilters.value.sort_order_from;
    if (localFilters.value.sort_order_to) params.sort_order_to = localFilters.value.sort_order_to;
    if (localFilters.value.include_deleted === '1') params.include_deleted = localFilters.value.include_deleted;
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'sort_order') params.sort_by = localFilters.value.sort_by;
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') params.sort_direction = localFilters.value.sort_direction;

    router.get('/categories', params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Pagination
function goToPage(page: number) {
    isLoading.value = true;

    const params: Record<string, any> = {};

    if (localFilters.value.search) params.search = localFilters.value.search;
    if (localFilters.value.parent_id && localFilters.value.parent_id !== '') params.parent_id = localFilters.value.parent_id;
    if (localFilters.value.is_active && localFilters.value.is_active !== '') params.is_active = localFilters.value.is_active;
    if (localFilters.value.created_from) params.created_from = localFilters.value.created_from;
    if (localFilters.value.created_to) params.created_to = localFilters.value.created_to;
    if (localFilters.value.updated_from) params.updated_from = localFilters.value.updated_from;
    if (localFilters.value.updated_to) params.updated_to = localFilters.value.updated_to;
    if (localFilters.value.created_by && localFilters.value.created_by !== '') params.created_by = localFilters.value.created_by;
    if (localFilters.value.has_children && localFilters.value.has_children !== '') params.has_children = localFilters.value.has_children;
    if (localFilters.value.has_products && localFilters.value.has_products !== '') params.has_products = localFilters.value.has_products;
    if (localFilters.value.sort_order_from) params.sort_order_from = localFilters.value.sort_order_from;
    if (localFilters.value.sort_order_to) params.sort_order_to = localFilters.value.sort_order_to;
    if (localFilters.value.include_deleted === '1') params.include_deleted = localFilters.value.include_deleted;
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'sort_order') params.sort_by = localFilters.value.sort_by;
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') params.sort_direction = localFilters.value.sort_direction;

    if (page > 1) params.page = page;

    router.get('/categories', params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Sort handler
function handleSort(column: string) {
    if (localFilters.value.sort_by === column) {
        localFilters.value.sort_direction = localFilters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_by = column;
        localFilters.value.sort_direction = 'asc';
    }
    applyFilters();
}

// Clear filters
const clearFilters = () => {
    localFilters.value = {
        search: '',
        parent_id: '',
        is_active: '',
        created_from: '',
        created_to: '',
        updated_from: '',
        updated_to: '',
        created_by: '',
        has_children: '',
        has_products: '',
        sort_order_from: '',
        sort_order_to: '',
        include_deleted: '',
        sort_by: 'sort_order',
        sort_direction: 'asc',
    };
    applyFilters();
};

// Remove specific filter
const removeFilter = (filterKey: string) => {
    (localFilters.value as any)[filterKey] = '';
    applyFilters();
};

// Quick filter presets
const applyQuickFilter = (preset: string) => {
    clearFilters();

    switch (preset) {
        case 'root_categories':
            localFilters.value.parent_id = 'null';
            break;
        case 'active_with_products':
            localFilters.value.is_active = '1';
            localFilters.value.has_products = '1';
            break;
        case 'categories_with_children':
            localFilters.value.has_children = '1';
            break;
        case 'recently_created':
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
            localFilters.value.created_from = oneWeekAgo.toISOString().split('T')[0];
            break;
        case 'inactive_categories':
            localFilters.value.is_active = '0';
            break;
    }

    applyFilters();
};

// Delete handler
function handleDelete(category: Category) {
    if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
        router.delete(`/categories/${category.id}`, {
            preserveScroll: true,
        });
    }
}

// Get hierarchy indicator
function getHierarchyPrefix(category: Category): string {
    if (!category.depth || category.depth === 0) return '';
    return '│ '.repeat(category.depth - 1) + '├─ ';
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader title="Categories" description="Manage product categories and their hierarchy">
                <template #action>
                    <div class="flex gap-2">
                        <Button variant="outline" as="a" href="/categories/tree">
                            <TreePine class="mr-2 h-4 w-4" />
                            Tree View
                        </Button>
                        <PermissionGuard permission="create_categories">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Add Category
                            </Button>
                        </PermissionGuard>
                    </div>
                </template>
            </PageHeader>

            <!-- Statistics Cards -->

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Categories</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Active</CardDescription>
                        <CardTitle class="text-3xl text-green-600">{{ stats.active }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Inactive</CardDescription>
                        <CardTitle class="text-3xl text-orange-600">{{ stats.inactive }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Root Categories</CardDescription>
                        <CardTitle class="text-3xl text-blue-600">{{ stats.root }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>With Children</CardDescription>
                        <CardTitle class="text-3xl text-purple-600">{{ stats.withChildren }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Active Filter Chips -->
            <div v-if="activeFilters.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Active Filters:</span>
                <div
                    v-for="filter in activeFilters"
                    :key="filter.key"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-200"
                >
                    {{ filter.label }}
                    <button @click="removeFilter(filter.key)" class="ml-1 rounded-full p-0.5 hover:bg-blue-200 dark:hover:bg-blue-800">
                        <X class="h-3 w-3" />
                    </button>
                </div>
                <Button variant="ghost" size="sm" @click="clearFilters" class="text-xs">
                    <RotateCcw class="mr-1 h-3 w-3" />
                    Clear All
                </Button>
            </div>

            <!-- Quick Filter Presets -->
            <div class="flex flex-wrap gap-2">
                <span class="text-sm font-medium text-muted-foreground">Quick Filters:</span>
                <Button variant="outline" size="sm" @click="applyQuickFilter('root_categories')">
                    <TreePine class="mr-2 h-3 w-3" />
                    Root Categories
                </Button>
                <Button variant="outline" size="sm" @click="applyQuickFilter('active_with_products')">
                    <Package class="mr-2 h-3 w-3" />
                    Active with Products
                </Button>
                <Button variant="outline" size="sm" @click="applyQuickFilter('categories_with_children')">
                    <Folder class="mr-2 h-3 w-3" />
                    With Children
                </Button>
                <Button variant="outline" size="sm" @click="applyQuickFilter('recently_created')">
                    <Calendar class="mr-2 h-3 w-3" />
                    Recently Created
                </Button>
                <Button variant="outline" size="sm" @click="applyQuickFilter('inactive_categories')">
                    <Eye class="mr-2 h-3 w-3" />
                    Inactive
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <Filter class="h-5 w-5" />
                            Filters
                        </CardTitle>
                        <Button variant="ghost" size="sm" @click="showAdvancedFilters = !showAdvancedFilters" class="flex items-center gap-2">
                            <Settings class="h-4 w-4" />
                            Advanced
                            <ChevronDown :class="{ 'rotate-180': showAdvancedFilters }" class="h-4 w-4 transition-transform" />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Basic Filters -->
                    <div class="grid gap-4 md:grid-cols-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <div class="relative">
                                <Search class="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                <Input v-model="localFilters.search" placeholder="Search categories..." class="pl-9" />
                            </div>
                        </div>

                        <!-- Parent Category -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Parent Category</label>
                            <Select v-model="localFilters.parent_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="All categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All categories</SelectItem>
                                    <SelectItem value="null">Root categories only</SelectItem>
                                    <SelectItem v-for="parent in parentCategories" :key="parent.id" :value="parent.id.toString()">
                                        {{ parent.full_name || parent.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <Select v-model="localFilters.is_active">
                                <SelectTrigger>
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All statuses</SelectItem>
                                    <SelectItem value="1">Active only</SelectItem>
                                    <SelectItem value="0">Inactive only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end space-x-2">
                            <Button variant="outline" @click="clearFilters"> Clear Filters </Button>
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div v-show="showAdvancedFilters" class="space-y-4 border-t pt-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <!-- Date Range Filters -->
                            <div class="space-y-4 md:col-span-3">
                                <h4 class="flex items-center gap-2 text-sm font-medium">
                                    <Calendar class="h-4 w-4" />
                                    Date Ranges
                                </h4>
                                <div class="grid gap-4 md:grid-cols-4">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Created From</label>
                                        <Input v-model="localFilters.created_from" type="date" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Created To</label>
                                        <Input v-model="localFilters.created_to" type="date" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Updated From</label>
                                        <Input v-model="localFilters.updated_from" type="date" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Updated To</label>
                                        <Input v-model="localFilters.updated_to" type="date" />
                                    </div>
                                </div>
                            </div>

                            <!-- Creator Filter -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <User class="h-4 w-4" />
                                    Created By
                                </label>
                                <Select v-model="localFilters.created_by">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Any creator" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Any creator</SelectItem>
                                        <SelectItem v-for="creator in creators" :key="creator.id" :value="creator.id.toString()">
                                            {{ creator.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Children Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Has Children</label>
                                <Select v-model="localFilters.has_children">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Any" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Any</SelectItem>
                                        <SelectItem value="1">With children</SelectItem>
                                        <SelectItem value="0">Without children</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Products Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Has Products</label>
                                <Select v-model="localFilters.has_products">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Any" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">Any</SelectItem>
                                        <SelectItem value="1">With products</SelectItem>
                                        <SelectItem value="0">Without products</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <!-- Sort Order Range -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-medium">Sort Order Range</h4>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">From</label>
                                    <Input v-model="localFilters.sort_order_from" type="number" placeholder="0" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">To</label>
                                    <Input v-model="localFilters.sort_order_to" type="number" placeholder="999" />
                                </div>
                            </div>
                        </div>

                        <!-- Include Deleted -->
                        <div class="flex items-center space-x-2">
                            <input
                                id="include-deleted"
                                type="checkbox"
                                v-model="localFilters.include_deleted"
                                class="rounded"
                                :value="'1'"
                                :true-value="'1'"
                                :false-value="''"
                            />
                            <label for="include-deleted" class="cursor-pointer text-sm font-medium"> Include deleted categories </label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Categories Table -->
            <Card>
                <CardHeader class="pb-0">
                    <div class="flex items-center justify-between">
                        <CardTitle>Categories</CardTitle>
                        <div class="text-sm text-muted-foreground">{{ categories.from }}-{{ categories.to }} of {{ categories.total }}</div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative">
                        <!-- Loading Overlay -->
                        <div v-if="isLoading" class="absolute inset-0 z-10 bg-background/50 backdrop-blur-sm">
                            <div class="flex h-full items-center justify-center">
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-48" />
                                    <Skeleton class="h-4 w-32" />
                                </div>
                            </div>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead class="border-b">
                                <tr class="border-b">
                                    <th class="w-12 px-4 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            :checked="selectedCategories.length === categories.data.length"
                                            :indeterminate="selectedCategories.length > 0 && selectedCategories.length < categories.data.length"
                                            @change="selectAll"
                                            class="rounded"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button @click="handleSort('name')" class="flex items-center gap-1 hover:text-foreground">
                                            Category Name
                                            <ChevronDown
                                                v-if="localFilters.sort_by === 'name' && localFilters.sort_direction === 'asc'"
                                                class="h-4 w-4"
                                            />
                                            <ChevronRight v-else-if="localFilters.sort_by === 'name'" class="h-4 w-4 rotate-180" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">Description</th>
                                    <th class="px-4 py-3 text-left">Parent</th>
                                    <th class="px-4 py-3 text-left">Children</th>
                                    <th class="px-4 py-3 text-left">Products</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">
                                        <button @click="handleSort('sort_order')" class="flex items-center gap-1 hover:text-foreground">
                                            Order
                                            <ChevronDown
                                                v-if="localFilters.sort_by === 'sort_order' && localFilters.sort_direction === 'asc'"
                                                class="h-4 w-4"
                                            />
                                            <ChevronRight v-else-if="localFilters.sort_by === 'sort_order'" class="h-4 w-4 rotate-180" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button @click="handleSort('created_at')" class="flex items-center gap-1 hover:text-foreground">
                                            Created
                                            <ChevronDown
                                                v-if="localFilters.sort_by === 'created_at' && localFilters.sort_direction === 'asc'"
                                                class="h-4 w-4"
                                            />
                                            <ChevronRight v-else-if="localFilters.sort_by === 'created_at'" class="h-4 w-4 rotate-180" />
                                        </button>
                                    </th>
                                    <th class="w-24 px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="categories.data.length === 0" class="border-b">
                                    <td colspan="10" class="h-32 px-4 py-3 text-center">
                                        <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                            <Package class="h-8 w-8" />
                                            <p>No categories found</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="category in categories.data" :key="category.id" class="border-b hover:bg-muted/50">
                                    <!-- Selection -->
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedCategories.includes(category.id)"
                                            @change="toggleSelection(category.id)"
                                            class="rounded"
                                        />
                                    </td>

                                    <!-- Name with hierarchy -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="font-mono text-xs text-muted-foreground">
                                                {{ getHierarchyPrefix(category) }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <component :is="category.has_children ? FolderOpen : Folder" class="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <Link :href="show(category.id).url" class="font-medium hover:underline">
                                                        {{ category.name }}
                                                    </Link>
                                                    <div v-if="category.slug" class="text-xs text-muted-foreground">/{{ category.slug }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-3">
                                        <div v-if="category.description" class="max-w-xs truncate text-sm">
                                            {{ category.description }}
                                        </div>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <!-- Parent -->
                                    <td class="px-4 py-3">
                                        <Link v-if="category.parent" :href="show(category.parent.id).url" class="text-sm hover:underline">
                                            {{ category.parent.name }}
                                        </Link>
                                        <span v-else class="text-sm text-muted-foreground">Root</span>
                                    </td>

                                    <!-- Children Count -->
                                    <td class="px-4 py-3">
                                        <Badge v-if="category.children_count && category.children_count > 0" variant="secondary">
                                            {{ category.children_count }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <!-- Products Count -->
                                    <td class="px-4 py-3">
                                        <Badge v-if="category.products_count && category.products_count > 0" variant="outline">
                                            {{ category.products_count }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <Badge :variant="category.is_active ? 'default' : 'secondary'">
                                            {{ category.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>

                                    <!-- Sort Order -->
                                    <td class="px-4 py-3">
                                        <span class="text-sm tabular-nums">{{ category.sort_order }}</span>
                                    </td>

                                    <!-- Created Date -->
                                    <td class="px-4 py-3">
                                        <span class="text-sm">{{ formatDate(category.created_at) }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <Link :href="show(category.id).url">
                                                    <DropdownMenuItem>
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </DropdownMenuItem>
                                                </Link>

                                                <PermissionGuard permission="edit_categories">
                                                    <DropdownMenuItem>
                                                        <Edit class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                </PermissionGuard>

                                                <PermissionGuard permission="delete_categories">
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(category)">
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </PermissionGuard>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="categories.last_page > 1" class="flex items-center justify-center space-x-2">
                <Button variant="outline" size="sm" :disabled="categories.current_page === 1" @click="goToPage(categories.current_page - 1)">
                    Previous
                </Button>

                <Button
                    v-for="page in categories.links.slice(1, -1)"
                    :key="page.page"
                    :variant="page.active ? 'default' : 'outline'"
                    size="sm"
                    @click="goToPage(page.page!)"
                    :disabled="!page.url"
                >
                    {{ page.label }}
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="categories.current_page === categories.last_page"
                    @click="goToPage(categories.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
