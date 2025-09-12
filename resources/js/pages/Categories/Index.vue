<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    ChevronDown, 
    ChevronRight, 
    Edit, 
    Eye, 
    Folder, 
    FolderOpen, 
    Package, 
    Plus, 
    Search, 
    Trash2,
    TreePine
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    categories: PaginatedData<Category>;
    filters: CategoryFilters;
    parentCategories: Array<{ id: number; name: string; full_name?: string }>;
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
            sort_by: newFilters.sort_by || 'sort_order',
            sort_direction: newFilters.sort_direction || 'asc',
        };
    },
    { immediate: true }
);

const isLoading = ref(false);
const selectedCategories = ref<number[]>([]);

// Statistics computed from data
const stats = computed(() => ({
    total: props.categories.total,
    active: props.categories.data.filter(cat => cat.is_active).length,
    inactive: props.categories.data.filter(cat => !cat.is_active).length,
    root: props.categories.data.filter(cat => !cat.parent_id).length,
    withChildren: props.categories.data.filter(cat => cat.children_count && cat.children_count > 0).length,
}));

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
    () => clearSelection()
);

// Debounced search
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch for filter changes
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        if (newFilters.search !== oldFilters?.search) {
            debouncedSearch();
        } else if (
            newFilters.parent_id !== oldFilters?.parent_id ||
            newFilters.is_active !== oldFilters?.is_active
        ) {
            applyFilters();
        }
    },
    { deep: true }
);

// Apply filters
function applyFilters() {
    isLoading.value = true;
    
    const params: Record<string, any> = {};
    
    if (localFilters.value.search) {
        params.search = localFilters.value.search;
    }
    if (localFilters.value.parent_id && localFilters.value.parent_id !== '') {
        params.parent_id = localFilters.value.parent_id;
    }
    if (localFilters.value.is_active && localFilters.value.is_active !== '') {
        params.is_active = localFilters.value.is_active;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'sort_order') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }
    
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
        sort_by: 'sort_order',
        sort_direction: 'asc',
    };
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

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Filters</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-4">
                        <!-- Search -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input 
                                    v-model="localFilters.search"
                                    placeholder="Search categories..."
                                    class="pl-9"
                                />
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
                                    <SelectItem 
                                        v-for="parent in parentCategories" 
                                        :key="parent.id" 
                                        :value="parent.id.toString()"
                                    >
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
                            <Button variant="outline" @click="clearFilters">
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Categories Table -->
            <Card>
                <CardHeader class="pb-0">
                    <div class="flex items-center justify-between">
                        <CardTitle>Categories</CardTitle>
                        <div class="text-sm text-muted-foreground">
                            {{ categories.from }}-{{ categories.to }} of {{ categories.total }}
                        </div>
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
                                        <button 
                                            @click="handleSort('name')"
                                            class="flex items-center gap-1 hover:text-foreground"
                                        >
                                            Category Name
                                            <ChevronDown v-if="localFilters.sort_by === 'name' && localFilters.sort_direction === 'asc'" class="h-4 w-4" />
                                            <ChevronRight v-else-if="localFilters.sort_by === 'name'" class="h-4 w-4 rotate-180" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">Description</th>
                                    <th class="px-4 py-3 text-left">Parent</th>
                                    <th class="px-4 py-3 text-left">Children</th>
                                    <th class="px-4 py-3 text-left">Products</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">
                                        <button 
                                            @click="handleSort('sort_order')"
                                            class="flex items-center gap-1 hover:text-foreground"
                                        >
                                            Order
                                            <ChevronDown v-if="localFilters.sort_by === 'sort_order' && localFilters.sort_direction === 'asc'" class="h-4 w-4" />
                                            <ChevronRight v-else-if="localFilters.sort_by === 'sort_order'" class="h-4 w-4 rotate-180" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button 
                                            @click="handleSort('created_at')"
                                            class="flex items-center gap-1 hover:text-foreground"
                                        >
                                            Created
                                            <ChevronDown v-if="localFilters.sort_by === 'created_at' && localFilters.sort_direction === 'asc'" class="h-4 w-4" />
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
                                            <div class="text-muted-foreground font-mono text-xs">
                                                {{ getHierarchyPrefix(category) }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <component 
                                                    :is="category.has_children ? FolderOpen : Folder"
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <div>
                                                    <Link 
                                                        :href="show(category.id).url"
                                                        class="font-medium hover:underline"
                                                    >
                                                        {{ category.name }}
                                                    </Link>
                                                    <div v-if="category.slug" class="text-xs text-muted-foreground">
                                                        /{{ category.slug }}
                                                    </div>
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
                                        <Link 
                                            v-if="category.parent"
                                            :href="show(category.parent.id).url"
                                            class="text-sm hover:underline"
                                        >
                                            {{ category.parent.name }}
                                        </Link>
                                        <span v-else class="text-muted-foreground text-sm">Root</span>
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
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                as="a"
                                                :href="show(category.id).url"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <PermissionGuard permission="edit_categories">
                                                <Button variant="ghost" size="sm">
                                                    <Edit class="h-4 w-4" />
                                                </Button>
                                            </PermissionGuard>

                                            <PermissionGuard permission="delete_categories">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="handleDelete(category)"
                                                    class="text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
                                            </PermissionGuard>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="categories.last_page > 1" class="flex items-center justify-center space-x-2">
                <Button 
                    variant="outline" 
                    size="sm"
                    :disabled="categories.current_page === 1"
                    @click="goToPage(categories.current_page - 1)"
                >
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