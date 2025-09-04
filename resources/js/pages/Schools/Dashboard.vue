<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SchoolStats from '@/components/schools/SchoolStats.vue';
import SchoolsTable from '@/components/schools/SchoolsTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/schools';
import { type BreadcrumbItem, type PaginatedData, type School, type SchoolFilters } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BarChart3, Plus, TrendingUp } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    recentSchools: PaginatedData<School>;
    stats: {
        total_schools: number;
        active_schools: number;
        inactive_schools: number;
        by_type: Record<string, number>;
        by_board: Record<string, number>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schools',
        href: index().url,
    },
    {
        title: 'Dashboard',
        href: '#',
    },
];

// For the reusable table component
const selectedSchools = ref<number[]>([]);
const isLoading = ref(false);

const mockFilters: SchoolFilters = {
    search: '',
    school_type: '',
    is_active: '',
    board_affiliation: '',
    sort_by: 'created_at',
    sort_direction: 'desc',
};

// Event handlers for the table
function handleSort(column: string) {
    // In a real implementation, this would handle sorting
    console.log('Sort by:', column);
}

function handleDelete(school: School) {
    if (confirm(`Are you sure you want to delete ${school.school_name}?`)) {
        router.delete(`/schools/${school.id}`, {
            preserveScroll: true,
        });
    }
}

function toggleSelection(schoolId: number) {
    const index = selectedSchools.value.indexOf(schoolId);
    if (index > -1) {
        selectedSchools.value.splice(index, 1);
    } else {
        selectedSchools.value.push(schoolId);
    }
}

function selectAll() {
    if (selectedSchools.value.length === props.recentSchools.data.length) {
        selectedSchools.value = [];
    } else {
        selectedSchools.value = props.recentSchools.data.map((school) => school.id);
    }
}

function clearSelection() {
    selectedSchools.value = [];
}

function handlePageChange(page: number) {
    // In a real implementation, this would handle pagination
    console.log('Page change:', page);
}
</script>

<template>
    <Head title="Schools Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Schools Dashboard</h2>
                    <p class="text-muted-foreground">Overview of your school management system</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="index().url">
                        <Button variant="outline">
                            <BarChart3 class="mr-2 h-4 w-4" />
                            View All Schools
                        </Button>
                    </Link>
                    <Link :href="create().url">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Add School
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards - Using the reusable component -->
            <SchoolStats 
                :schools="recentSchools.data" 
                :total-count="stats.total_schools" 
            />

            <!-- Additional Dashboard Stats -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Schools by Type
                        </CardTitle>
                        <CardDescription>Distribution of schools by their type</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-for="(count, type) in stats.by_type" :key="type" class="flex justify-between">
                                <span class="capitalize">{{ type.replace('_', ' ') }}</span>
                                <span class="font-medium">{{ count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Schools by Board
                        </CardTitle>
                        <CardDescription>Distribution of schools by board affiliation</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div v-for="(count, board) in stats.by_board" :key="board" class="flex justify-between">
                                <span class="uppercase text-xs font-medium">{{ board }}</span>
                                <span class="font-medium">{{ count }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Schools - Using the reusable table component -->
            <div>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Recent Schools</h3>
                    <p class="text-sm text-muted-foreground">Recently added schools in the system</p>
                </div>
                
                <SchoolsTable
                    :schools="recentSchools"
                    :filters="mockFilters"
                    :selected-schools="selectedSchools"
                    :is-loading="isLoading"
                    @sort="handleSort"
                    @delete="handleDelete"
                    @select="toggleSelection"
                    @select-all="selectAll"
                    @clear-selection="clearSelection"
                    @page-change="handlePageChange"
                />
            </div>
        </div>
    </AppLayout>
</template>