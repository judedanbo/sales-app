<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index, show } from '@/routes/schools';
import { type BreadcrumbItem, type PaginatedData, type School, type SchoolFilters } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    Building2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Download,
    Edit as EditIcon,
    Eye,
    GraduationCap,
    MoreHorizontal,
    Plus,
    School as SchoolIcon,
    Search,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    schools: PaginatedData<School>;
    filters: SchoolFilters;
    schoolTypes: Record<string, string>;
    boardAffiliations: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schools',
        href: index().url,
    },
];

// Local filter state
const localFilters = ref<SchoolFilters>({
    search: props.filters.search || '',
    school_type: props.filters.school_type || '',
    is_active: props.filters.is_active || '',
    board_affiliation: props.filters.board_affiliation || '',
    sort_by: props.filters.sort_by || 'school_name',
    sort_direction: props.filters.sort_direction || 'asc',
});

const isLoading = ref(false);
const selectedSchools = ref<number[]>([]);

// Selection handlers
const isSelected = (schoolId: number) => selectedSchools.value.includes(schoolId);

const toggleSelection = (schoolId: number) => {
    const index = selectedSchools.value.indexOf(schoolId);
    if (index > -1) {
        selectedSchools.value.splice(index, 1);
    } else {
        selectedSchools.value.push(schoolId);
    }
};

const selectAll = () => {
    if (selectedSchools.value.length === props.schools.data.length) {
        selectedSchools.value = [];
    } else {
        selectedSchools.value = props.schools.data.map((school) => school.id);
    }
};

const clearSelection = () => {
    selectedSchools.value = [];
};

// Computed for select all checkbox state
const isAllSelected = computed(() => props.schools.data.length > 0 && selectedSchools.value.length === props.schools.data.length);

const isIndeterminate = computed(() => selectedSchools.value.length > 0 && selectedSchools.value.length < props.schools.data.length);

// Clear selection when page changes
watch(
    () => props.schools.current_page,
    () => {
        clearSelection();
    },
);

// Debounced search function
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

// Watch for search changes
watch(
    () => localFilters.value.search,
    () => {
        debouncedSearch();
    },
);

// Apply filters immediately for non-search filters
watch([() => localFilters.value.school_type, () => localFilters.value.is_active, () => localFilters.value.board_affiliation], () => {
    applyFilters();
});

// Apply filters to the server
function applyFilters() {
    isLoading.value = true;
    router.get(index().url, localFilters.value, {
        preserveState: true,
        preserveScroll: true,
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

// Delete handler
function handleDelete(school: School) {
    if (confirm(`Are you sure you want to delete ${school.school_name}?`)) {
        router.delete(destroy(school).url, {
            preserveScroll: true,
        });
    }
}

// Format date
function formatDate(date: string | undefined) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Get sort icon
function getSortIcon(column: string) {
    if (localFilters.value.sort_by !== column) {
        return null;
    }
    return localFilters.value.sort_direction === 'asc' ? ChevronUp : ChevronDown;
}

// Computed properties
const hasFilters = computed(() => {
    return localFilters.value.search || localFilters.value.school_type || localFilters.value.is_active || localFilters.value.board_affiliation;
});

const clearFilters = () => {
    localFilters.value = {
        search: '',
        school_type: '',
        is_active: '',
        board_affiliation: '',
        sort_by: 'school_name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <Head title="Schools" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Schools</h2>
                    <p class="text-muted-foreground">Manage your schools and their information</p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Add School
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Schools</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.schools.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Schools</CardTitle>
                        <SchoolIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.schools.data.filter((s) => s.is_active).length }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.schools.data.reduce((sum, s) => sum + (s.total_students || 0), 0).toLocaleString() }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Teachers</CardTitle>
                        <GraduationCap class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ props.schools.data.reduce((sum, s) => sum + (s.total_teachers || 0), 0).toLocaleString() }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>Search and filter schools</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-5">
                        <!-- Search -->
                        <div class="relative md:col-span-2">
                            <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                            <Input v-model="localFilters.search" placeholder="Search by name or code..." class="pl-8" />
                        </div>

                        <!-- School Type Filter -->
                        <select
                            v-model="localFilters.school_type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in props.schoolTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Board Filter -->
                        <select
                            v-model="localFilters.board_affiliation"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">All Boards</option>
                            <option v-for="(label, value) in props.boardAffiliations" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select
                            v-model="localFilters.is_active"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div v-if="hasFilters" class="mt-4">
                        <Button variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Bulk Actions Bar -->
            <div v-if="selectedSchools.length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
                <Card class="border-2 border-primary/20 shadow-lg">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-4">
                            <div class="text-sm font-medium">
                                {{ selectedSchools.length }} {{ selectedSchools.length === 1 ? 'school' : 'schools' }} selected
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="clearSelection"> Clear </Button>
                                <Button size="sm" class="gap-2">
                                    <Download class="h-4 w-4" />
                                    Export Selected
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Schools Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Schools List</CardTitle>
                    <CardDescription>
                        Showing {{ props.schools.from || 0 }} to {{ props.schools.to || 0 }} of {{ props.schools.total }} schools
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="relative overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/50 text-xs uppercase">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3">
                                        <Checkbox
                                            :model-value="isAllSelected"
                                            :indeterminate="isIndeterminate"
                                            @update:model-value="selectAll"
                                            aria-label="Select all schools"
                                        />
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        <button
                                            @click="handleSort('school_name')"
                                            class="flex items-center gap-1 transition-colors hover:text-foreground"
                                        >
                                            School Name
                                            <component :is="getSortIcon('school_name')" v-if="getSortIcon('school_name')" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        <button
                                            @click="handleSort('school_code')"
                                            class="flex items-center gap-1 transition-colors hover:text-foreground"
                                        >
                                            Code
                                            <component :is="getSortIcon('school_code')" v-if="getSortIcon('school_code')" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        <button
                                            @click="handleSort('school_type')"
                                            class="flex items-center gap-1 transition-colors hover:text-foreground"
                                        >
                                            Type
                                            <component :is="getSortIcon('school_type')" v-if="getSortIcon('school_type')" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3">Board</th>
                                    <th scope="col" class="px-4 py-3">Students</th>
                                    <th scope="col" class="px-4 py-3">Teachers</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    <th scope="col" class="px-4 py-3">
                                        <button
                                            @click="handleSort('established_date')"
                                            class="flex items-center gap-1 transition-colors hover:text-foreground"
                                        >
                                            Established
                                            <component :is="getSortIcon('established_date')" v-if="getSortIcon('established_date')" class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="isLoading">
                                    <td colspan="10" class="px-4 py-8 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Skeleton class="h-4 w-32" />
                                            <Skeleton class="h-4 w-24" />
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-else-if="!props.schools.data.length">
                                    <td colspan="10" class="px-4 py-8 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <SchoolIcon class="h-8 w-8 text-muted-foreground" />
                                            <p class="text-muted-foreground">No schools found</p>
                                            <Link :href="create().url">
                                                <Button size="sm">
                                                    <Plus class="mr-2 h-4 w-4" />
                                                    Add First School
                                                </Button>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <tr v-else v-for="school in props.schools.data" :key="school.id" class="border-b hover:bg-muted/50">
                                    <td class="px-4 py-3">
                                        <Checkbox
                                            :model-value="isSelected(school.id)"
                                            @update:model-value="() => toggleSelection(school.id)"
                                            :aria-label="`Select ${school.school_name}`"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ school.school_name }}
                                        <div v-if="school.principal_name" class="text-xs text-muted-foreground">
                                            Principal: {{ school.principal_name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="rounded bg-muted px-1 py-0.5 text-xs">{{ school.school_code }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="capitalize">{{ school.school_type?.replace('_', ' ') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="school.board_affiliation" class="text-xs font-medium uppercase">
                                            {{ school.board_affiliation }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ school.total_students?.toLocaleString() || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ school.total_teachers?.toLocaleString() || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                                school.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            ]"
                                        >
                                            {{ school.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatDate(school.established_date) }}
                                    </td>
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
                                                <Link :href="show(school).url">
                                                    <DropdownMenuItem>
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </DropdownMenuItem>
                                                </Link>
                                                <Link :href="edit(school).url">
                                                    <DropdownMenuItem>
                                                        <EditIcon class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                </Link>
                                                <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(school)">
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.schools.last_page > 1" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">Page {{ props.schools.current_page }} of {{ props.schools.last_page }}</div>
                        <div class="flex gap-2">
                            <Link v-if="props.schools.links.prev" :href="props.schools.links.prev" preserve-scroll preserve-state>
                                <Button variant="outline" size="sm">
                                    <ChevronLeft class="h-4 w-4" />
                                    Previous
                                </Button>
                            </Link>
                            <Button v-else variant="outline" size="sm" disabled>
                                <ChevronLeft class="h-4 w-4" />
                                Previous
                            </Button>

                            <Link v-if="props.schools.links.next" :href="props.schools.links.next" preserve-scroll preserve-state>
                                <Button variant="outline" size="sm">
                                    Next
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Button v-else variant="outline" size="sm" disabled>
                                Next
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
