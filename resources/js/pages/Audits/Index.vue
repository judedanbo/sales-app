<script setup lang="ts">
import AuditFilters from '@/components/audits/AuditFilters.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/components/ui/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, index, show } from '@/routes/audits';
import type { Audit, AuditFilters as AuditFiltersType, BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { BarChart3, Eye, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    audits: PaginatedData<Audit>;
    filters: AuditFiltersType;
    filter_options: {
        models: Array<{ value: string; label: string }>;
        events: string[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audits',
        href: index().url,
    },
];

// Local filter state - initialize from props
const localFilters = ref<AuditFiltersType>({
    auditable_type: props.filters.auditable_type || '',
    user_id: props.filters.user_id || undefined,
    event: props.filters.event || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
    search: props.filters.search || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_direction: props.filters.sort_direction || 'desc',
});

// Watch for prop changes and update local filters
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            auditable_type: newFilters.auditable_type || '',
            user_id: newFilters.user_id || undefined,
            event: newFilters.event || '',
            from_date: newFilters.from_date || '',
            to_date: newFilters.to_date || '',
            search: newFilters.search || '',
            sort_by: newFilters.sort_by || 'created_at',
            sort_direction: newFilters.sort_direction || 'desc',
        };
    },
    { immediate: false },
);

// Debounced function to update filters
const debouncedUpdateFilters = useDebounceFn(() => {
    const cleanFilters = Object.fromEntries(
        Object.entries(localFilters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );

    router.get(index().url, cleanFilters as any, {
        preserveState: true,
        preserveUrl: true,
    });
}, 300);

// Watch local filters and trigger debounced update
watch(localFilters, debouncedUpdateFilters, { deep: true });

function clearFilters() {
    localFilters.value = {
        auditable_type: '',
        user_id: undefined,
        event: '',
        from_date: '',
        to_date: '',
        search: '',
        sort_by: 'created_at',
        sort_direction: 'desc',
    };
}

// Removed updateSort function as it's not used in the current component

function getEventColor(event: string): string {
    switch (event) {
        case 'created':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'updated':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'deleted':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        case 'restored':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
}

function formatModelName(auditableType: string): string {
    return auditableType.split('\\').pop() || auditableType;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString();
}

function getRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return `${Math.floor(diffInMinutes / 1440)}d ago`;
}

const hasActiveFilters = computed(() => {
    return Object.values(localFilters.value).some(
        (value) =>
            value !== '' &&
            value !== null &&
            value !== undefined &&
            !(value === 'created_at' && localFilters.value.sort_by === value) &&
            !(value === 'desc' && localFilters.value.sort_direction === value),
    );
});

const isLoading = ref(false);

function goToPage(page: number) {
    isLoading.value = true;
    router.reload({
        data: {
            ...localFilters.value,
            page: page,
        },
        preserveUrl: true,
        only: ['audits'],
        onFinish: () => {
            isLoading.value = false;
        },
    });
}
const handlePageChange = (page: number) => {
    goToPage(page);
};
</script>

<template>
    <Head title="Audit Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <PageHeader title="Audit Records" description="View and search all system activity logs and changes">
                <template #action>
                    <Button as-child variant="outline">
                        <Link :href="dashboard().url">
                            <BarChart3 class="mr-2 h-4 w-4" />
                            Dashboard
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- Filters -->
            <AuditFilters
                v-model:filters="localFilters"
                :filter-options="filter_options"
                :total-results="audits.total"
                @clear-filters="clearFilters"
            />

            <!-- Results -->
            <Card data-testid="audit-table" class="px-8">
                <CardContent class="p-0">
                    <!-- Table Header -->
                    <div class="grid grid-cols-12 gap-4 border-b bg-muted/50 p-4 text-sm font-medium">
                        <div class="col-span-2">Event</div>
                        <div class="col-span-2">Model</div>
                        <div class="col-span-2">User</div>
                        <div class="col-span-2">IP Address</div>
                        <div class="col-span-2">Time</div>
                        <div class="col-span-2 text-right">Actions</div>
                    </div>

                    <!-- Table Rows -->
                    <div v-if="audits.data.length > 0" class="divide-y">
                        <div v-for="audit in audits.data" :key="audit.id" class="grid grid-cols-12 gap-4 p-4 transition-colors hover:bg-muted/50">
                            <!-- Event -->
                            <div class="col-span-2">
                                <Badge :class="getEventColor(audit.event)">
                                    {{ audit.event }}
                                </Badge>
                            </div>

                            <!-- Model -->
                            <div class="col-span-2">
                                <div class="text-sm font-medium">{{ formatModelName(audit.auditable_type) }}</div>
                                <div class="text-xs text-muted-foreground">#{{ audit.auditable_id }}</div>
                            </div>

                            <!-- User -->
                            <div class="col-span-2">
                                <div class="text-sm">{{ audit.user?.name || 'System' }}</div>
                                <div v-if="audit.user?.email" class="text-xs text-muted-foreground">{{ audit.user.email }}</div>
                            </div>

                            <!-- IP Address -->
                            <div class="col-span-2 text-sm">
                                {{ audit.ip_address || 'N/A' }}
                            </div>

                            <!-- Time -->
                            <div class="col-span-2">
                                <div class="text-sm">{{ getRelativeTime(audit.created_at) }}</div>
                                <div class="text-xs text-muted-foreground">{{ formatDate(audit.created_at) }}</div>
                            </div>

                            <!-- Actions -->
                            <div class="col-span-2 flex justify-end">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="show(audit.id).url">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-8 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                            <Search class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <h3 class="mb-2 text-lg font-medium">No audit records found</h3>
                        <p class="mb-4 text-muted-foreground">
                            {{ hasActiveFilters ? 'Try adjusting your filters to see more results.' : 'No audit records have been created yet.' }}
                        </p>
                        <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters"> Clear Filters </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="audits.data.length > 0" class="flex justify-center">
                <Pagination :current-page="audits.current_page" :last-page="audits.last_page" :disabled="isLoading" @page-change="handlePageChange" />
                <!-- {{ audits }} -->
            </div>
        </div>
    </AppLayout>
</template>
