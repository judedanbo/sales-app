<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { forUser, index, show } from '@/routes/audits';
import type { Audit, AuditFilters, BreadcrumbItem, PaginatedData, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ArrowLeft, Eye, Filter, User as UserIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    audits: PaginatedData<Audit>;
    user: User;
    filters: Pick<AuditFilters, 'auditable_type' | 'event'>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audits',
        href: index().url,
    },
    {
        title: `${props.user.name}'s Activity`,
        href: forUser(props.user.id).url,
    },
];

// Local filter state
const localFilters = ref<Pick<AuditFilters, 'auditable_type' | 'event'>>({
    auditable_type: props.filters.auditable_type || '',
    event: props.filters.event || '',
});

// Watch for prop changes and update local filters
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            auditable_type: newFilters.auditable_type || '',
            event: newFilters.event || '',
        };
    },
    { immediate: false },
);

// Debounced function to update filters
const debouncedUpdateFilters = useDebounceFn(() => {
    const cleanFilters = Object.fromEntries(
        Object.entries(localFilters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );

    router.get(forUser(props.user.id).url, cleanFilters, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

// Watch local filters and trigger debounced update
watch(localFilters, debouncedUpdateFilters, { deep: true });

function clearFilters() {
    localFilters.value = {
        auditable_type: '',
        event: '',
    };
}

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

function getUserInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

const hasActiveFilters = computed(() => {
    return Object.values(localFilters.value).some((value) => value !== '' && value !== null && value !== undefined);
});

// Get unique model types from audits for filter options
const modelTypes = computed(() => {
    const types = new Set(props.audits.data.map((audit) => audit.auditable_type));
    return Array.from(types).map((type) => ({
        value: type,
        label: formatModelName(type),
    }));
});

const eventTypes = ['created', 'updated', 'deleted', 'restored'];

// Activity summary
const activitySummary = computed(() => {
    const events = props.audits.data.reduce(
        (acc, audit) => {
            acc[audit.event] = (acc[audit.event] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>,
    );

    const models = props.audits.data.reduce(
        (acc, audit) => {
            const modelName = formatModelName(audit.auditable_type);
            acc[modelName] = (acc[modelName] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>,
    );

    return { events, models };
});
</script>

<template>
    <Head :title="`${user.name}'s Audit Activity`" />

    <AppLayout>
        <template #header>
            <PageHeader
                :title="`${user.name}'s Activity`"
                :description="`Complete audit trail for ${user.name}'s actions in the system`"
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            All Audits
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- User Info & Summary -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- User Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center space-x-3">
                            <Avatar>
                                <AvatarFallback>{{ getUserInitials(user.name) }}</AvatarFallback>
                            </Avatar>
                            <div>
                                <div>{{ user.name }}</div>
                                <div class="text-sm font-normal text-muted-foreground">{{ user.email }}</div>
                            </div>
                        </CardTitle>
                        <CardDescription v-if="user.user_type_label">{{ user.user_type_label }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div v-if="user.department" class="flex justify-between">
                                <span class="text-muted-foreground">Department:</span>
                                <span>{{ user.department }}</span>
                            </div>
                            <div v-if="user.school" class="flex justify-between">
                                <span class="text-muted-foreground">School:</span>
                                <span>{{ user.school.school_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Audits:</span>
                                <span class="font-medium">{{ audits.total }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Activity by Event Type -->
                <Card>
                    <CardHeader>
                        <CardTitle>Activity by Type</CardTitle>
                        <CardDescription>Breakdown of actions performed</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="(count, event) in activitySummary.events" :key="event" class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <Badge :class="getEventColor(event)" class="text-xs">
                                        {{ event }}
                                    </Badge>
                                </div>
                                <span class="text-sm font-medium">{{ count }}</span>
                            </div>

                            <div v-if="Object.keys(activitySummary.events).length === 0" class="py-2 text-center text-sm text-muted-foreground">
                                No activity recorded
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Activity by Model -->
                <Card>
                    <CardHeader>
                        <CardTitle>Models Affected</CardTitle>
                        <CardDescription>Records modified by this user</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="(count, model) in activitySummary.models" :key="model" class="flex items-center justify-between">
                                <span class="text-sm">{{ model }}</span>
                                <span class="text-sm font-medium">{{ count }}</span>
                            </div>

                            <div v-if="Object.keys(activitySummary.models).length === 0" class="py-2 text-center text-sm text-muted-foreground">
                                No models affected
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filters</CardTitle>
                    <CardDescription>Filter {{ user.name }}'s audit records</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <!-- Model Type -->
                        <Select v-model="localFilters.auditable_type">
                            <SelectTrigger>
                                <SelectValue placeholder="All Models" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Models</SelectItem>
                                <SelectItem v-for="model in modelTypes" :key="model.value" :value="model.value">
                                    {{ model.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Event Type -->
                        <Select v-model="localFilters.event">
                            <SelectTrigger>
                                <SelectValue placeholder="All Events" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Events</SelectItem>
                                <SelectItem v-for="event in eventTypes" :key="event" :value="event">
                                    {{ event.charAt(0).toUpperCase() + event.slice(1) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Clear Filters -->
                        <div class="flex items-center">
                            <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters">
                                <Filter class="mr-2 h-4 w-4" />
                                Clear Filters
                            </Button>
                        </div>
                    </div>

                    <div v-if="hasActiveFilters" class="mt-4">
                        <span class="text-sm text-muted-foreground"> {{ audits.total }} result{{ audits.total !== 1 ? 's' : '' }} found </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Audit Records -->
            <Card>
                <CardContent class="p-0">
                    <!-- Table Header -->
                    <div class="grid grid-cols-12 gap-4 border-b bg-muted/50 p-4 text-sm font-medium">
                        <div class="col-span-2">Event</div>
                        <div class="col-span-3">Model</div>
                        <div class="col-span-2">IP Address</div>
                        <div class="col-span-3">Time</div>
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
                            <div class="col-span-3">
                                <div class="text-sm font-medium">{{ formatModelName(audit.auditable_type) }}</div>
                                <div class="text-xs text-muted-foreground">#{{ audit.auditable_id }}</div>
                            </div>

                            <!-- IP Address -->
                            <div class="col-span-2 text-sm">
                                {{ audit.ip_address || 'N/A' }}
                            </div>

                            <!-- Time -->
                            <div class="col-span-3">
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
                            <UserIcon class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <h3 class="mb-2 text-lg font-medium">No audit records found</h3>
                        <p class="mb-4 text-muted-foreground">
                            {{
                                hasActiveFilters
                                    ? `No audit records found for ${user.name} with the current filters.`
                                    : `${user.name} hasn't performed any audited actions yet.`
                            }}
                        </p>
                        <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters"> Clear Filters </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="audits.data.length > 0" class="flex justify-center">
                <Pagination :data="audits" />
            </div>
        </div>
    </AppLayout>
</template>
