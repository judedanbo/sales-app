<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, index } from '@/routes/audits';
import type { Audit, AuditStatistics, BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, Calendar, Database, Eye, TrendingUp, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    stats: AuditStatistics['stats'];
    recent_audits: Audit[];
    events_breakdown: Record<string, number>;
    models_breakdown: Record<string, number>;
    top_users: AuditStatistics['top_users'];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audits',
        href: index().url,
    },
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Transform events for display
const eventsList = computed(() => {
    return Object.entries(props.events_breakdown).map(([event, count]) => ({
        event,
        count,
        label: event.charAt(0).toUpperCase() + event.slice(1),
        color: getEventColor(event),
    }));
});

// Transform models for display
const modelsList = computed(() => {
    return Object.entries(props.models_breakdown).map(([model, count]) => ({
        model,
        count,
        label: model,
    }));
});

function getEventColor(event: string): string {
    switch (event) {
        case 'created':
            return 'bg-green-500';
        case 'updated':
            return 'bg-blue-500';
        case 'deleted':
            return 'bg-red-500';
        case 'restored':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-500';
    }
}

function getEventIcon(event: string) {
    switch (event) {
        case 'created':
            return 'plus';
        case 'updated':
            return 'edit';
        case 'deleted':
            return 'trash';
        case 'restored':
            return 'rotate-ccw';
        default:
            return 'activity';
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
</script>

<template>
    <Head title="Audit Dashboard" />

    <AppLayout>
        <template #header>
            <PageHeader
                title="Audit Dashboard"
                description="Monitor system activity and track all changes across your application"
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="index().url">
                            <Eye class="mr-2 h-4 w-4" />
                            View All Audits
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Audits</CardTitle>
                        <Database class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_audits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">All recorded activities</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.today_audits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">Activities today</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">This Week</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.this_week_audits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">Activities this week</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">This Month</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.this_month_audits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">Activities this month</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Recent Audits -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>Latest audit records from across the system</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="audit in recent_audits" :key="audit.id" class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <Badge :class="getEventColor(audit.event)" class="text-white">
                                        {{ audit.event }}
                                    </Badge>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatModelName(audit.auditable_type) }} #{{ audit.auditable_id }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">by {{ audit.user?.name || 'System' }}</p>
                                </div>
                                <div class="flex-shrink-0 text-sm text-muted-foreground">
                                    {{ getRelativeTime(audit.created_at) }}
                                </div>
                            </div>

                            <div v-if="recent_audits.length === 0" class="py-4 text-center text-muted-foreground">No recent audit records found</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Events Breakdown -->
                <Card>
                    <CardHeader>
                        <CardTitle>Activity Types</CardTitle>
                        <CardDescription>Breakdown of audit events by type</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="event in eventsList" :key="event.event" class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div :class="event.color" class="h-3 w-3 rounded-full"></div>
                                    <span class="text-sm font-medium">{{ event.label }}</span>
                                </div>
                                <span class="text-sm text-muted-foreground">{{ event.count.toLocaleString() }}</span>
                            </div>

                            <div v-if="eventsList.length === 0" class="py-4 text-center text-muted-foreground">No audit events found</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Models Breakdown -->
                <Card>
                    <CardHeader>
                        <CardTitle>Models Activity</CardTitle>
                        <CardDescription>Audit records by model type</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="model in modelsList.slice(0, 6)" :key="model.model" class="flex items-center justify-between">
                                <span class="text-sm font-medium">{{ model.label }}</span>
                                <span class="text-sm text-muted-foreground">{{ model.count.toLocaleString() }}</span>
                            </div>

                            <div v-if="modelsList.length === 0" class="py-4 text-center text-muted-foreground">No model activities found</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Users -->
                <Card>
                    <CardHeader>
                        <CardTitle>Most Active Users</CardTitle>
                        <CardDescription>Users with the most audit records</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="userStat in top_users" :key="userStat.user.id">
                                <!-- {{ userStat }} -->
                                <Link :href="'/audits/timeline/User/' + userStat.user.id" class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <User class="h-4 w-4 text-muted-foreground" />
                                        <span class="text-sm font-medium">{{ userStat.user.name }}</span>
                                    </div>
                                    <span class="text-sm text-muted-foreground">{{ userStat.audit_count.toLocaleString() }}</span>
                                </Link>
                            </div>

                            <div v-if="top_users.length === 0" class="py-4 text-center text-muted-foreground">No user activities found</div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
