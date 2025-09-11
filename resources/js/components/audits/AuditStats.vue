<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { AuditStatistics } from '@/types';
import { Activity, BarChart3, Calendar, Database, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    stats: AuditStatistics;
    title?: string;
    description?: string;
    showBreakdown?: boolean;
    layout?: 'grid' | 'horizontal' | 'compact';
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Audit Statistics',
    description: 'System activity overview',
    showBreakdown: true,
    layout: 'grid',
});

// Transform events for display
const eventsList = computed(() => {
    return Object.entries(props.stats.events_breakdown).map(([event, count]) => ({
        event,
        count,
        label: event.charAt(0).toUpperCase() + event.slice(1),
        color: getEventColor(event),
        percentage: props.stats.stats.total_audits > 0 ? Math.round((count / props.stats.stats.total_audits) * 100) : 0,
    }));
});

// Transform models for display
const modelsList = computed(() => {
    return Object.entries(props.stats.models_breakdown)
        .map(([model, count]) => ({
            model,
            count,
            label: model,
            percentage: props.stats.stats.total_audits > 0 ? Math.round((count / props.stats.stats.total_audits) * 100) : 0,
        }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 5); // Show top 5 models
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

function formatNumber(num: number): string {
    return num.toLocaleString();
}

const growthRate = computed(() => {
    if (props.stats.stats.this_week_audits === 0) return 0;
    const weeklyAverage = props.stats.stats.this_month_audits / 4;
    return weeklyAverage > 0 ? Math.round(((props.stats.stats.this_week_audits - weeklyAverage) / weeklyAverage) * 100) : 0;
});
</script>

<template>
    <div>
        <!-- Header -->
        <div v-if="title || description" class="mb-6">
            <h2 v-if="title" class="text-xl font-semibold">{{ title }}</h2>
            <p v-if="description" class="text-muted-foreground">{{ description }}</p>
        </div>

        <!-- Main Statistics -->
        <div
            :class="{
                'grid gap-4 md:grid-cols-2 lg:grid-cols-4': layout === 'grid',
                'flex flex-wrap gap-4': layout === 'horizontal',
                'grid grid-cols-2 gap-2 md:grid-cols-4': layout === 'compact',
            }"
            class="mb-6"
        >
            <Card :class="layout === 'compact' ? 'p-3' : ''">
                <CardHeader :class="layout === 'compact' ? 'p-2 pb-1' : 'flex flex-row items-center justify-between space-y-0 pb-2'">
                    <CardTitle :class="layout === 'compact' ? 'text-xs' : 'text-sm'" class="font-medium">Total Audits</CardTitle>
                    <Database :class="layout === 'compact' ? 'h-3 w-3' : 'h-4 w-4'" class="text-muted-foreground" />
                </CardHeader>
                <CardContent :class="layout === 'compact' ? 'p-2 pt-0' : ''">
                    <div :class="layout === 'compact' ? 'text-lg' : 'text-2xl'" class="font-bold">
                        {{ formatNumber(stats.stats.total_audits) }}
                    </div>
                    <p :class="layout === 'compact' ? 'text-xs' : 'text-xs'" class="text-muted-foreground">All recorded activities</p>
                </CardContent>
            </Card>

            <Card :class="layout === 'compact' ? 'p-3' : ''">
                <CardHeader :class="layout === 'compact' ? 'p-2 pb-1' : 'flex flex-row items-center justify-between space-y-0 pb-2'">
                    <CardTitle :class="layout === 'compact' ? 'text-xs' : 'text-sm'" class="font-medium">Today</CardTitle>
                    <Calendar :class="layout === 'compact' ? 'h-3 w-3' : 'h-4 w-4'" class="text-muted-foreground" />
                </CardHeader>
                <CardContent :class="layout === 'compact' ? 'p-2 pt-0' : ''">
                    <div :class="layout === 'compact' ? 'text-lg' : 'text-2xl'" class="font-bold">
                        {{ formatNumber(stats.stats.today_audits) }}
                    </div>
                    <p :class="layout === 'compact' ? 'text-xs' : 'text-xs'" class="text-muted-foreground">Activities today</p>
                </CardContent>
            </Card>

            <Card :class="layout === 'compact' ? 'p-3' : ''">
                <CardHeader :class="layout === 'compact' ? 'p-2 pb-1' : 'flex flex-row items-center justify-between space-y-0 pb-2'">
                    <CardTitle :class="layout === 'compact' ? 'text-xs' : 'text-sm'" class="font-medium">This Week</CardTitle>
                    <TrendingUp :class="layout === 'compact' ? 'h-3 w-3' : 'h-4 w-4'" class="text-muted-foreground" />
                </CardHeader>
                <CardContent :class="layout === 'compact' ? 'p-2 pt-0' : ''">
                    <div :class="layout === 'compact' ? 'text-lg' : 'text-2xl'" class="font-bold">
                        {{ formatNumber(stats.stats.this_week_audits) }}
                    </div>
                    <p :class="layout === 'compact' ? 'text-xs' : 'text-xs'" class="flex items-center space-x-1 text-muted-foreground">
                        <span>Activities this week</span>
                        <span v-if="growthRate !== 0" :class="growthRate > 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                            ({{ growthRate > 0 ? '+' : '' }}{{ growthRate }}%)
                        </span>
                    </p>
                </CardContent>
            </Card>

            <Card :class="layout === 'compact' ? 'p-3' : ''">
                <CardHeader :class="layout === 'compact' ? 'p-2 pb-1' : 'flex flex-row items-center justify-between space-y-0 pb-2'">
                    <CardTitle :class="layout === 'compact' ? 'text-xs' : 'text-sm'" class="font-medium">This Month</CardTitle>
                    <Activity :class="layout === 'compact' ? 'h-3 w-3' : 'h-4 w-4'" class="text-muted-foreground" />
                </CardHeader>
                <CardContent :class="layout === 'compact' ? 'p-2 pt-0' : ''">
                    <div :class="layout === 'compact' ? 'text-lg' : 'text-2xl'" class="font-bold">
                        {{ formatNumber(stats.stats.this_month_audits) }}
                    </div>
                    <p :class="layout === 'compact' ? 'text-xs' : 'text-xs'" class="text-muted-foreground">Activities this month</p>
                </CardContent>
            </Card>
        </div>

        <!-- Breakdowns -->
        <div v-if="showBreakdown && (eventsList.length > 0 || modelsList.length > 0 || stats.top_users.length > 0)" class="grid gap-6 md:grid-cols-3">
            <!-- Events Breakdown -->
            <Card v-if="eventsList.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2 text-base">
                        <BarChart3 class="h-4 w-4" />
                        <span>Activity Types</span>
                    </CardTitle>
                    <CardDescription>Breakdown by event type</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="event in eventsList" :key="event.event" class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div :class="event.color" class="h-3 w-3 rounded-full"></div>
                                <span class="text-sm font-medium">{{ event.label }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-muted-foreground">{{ formatNumber(event.count) }}</span>
                                <Badge variant="outline" class="text-xs">{{ event.percentage }}%</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Models Breakdown -->
            <Card v-if="modelsList.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2 text-base">
                        <Database class="h-4 w-4" />
                        <span>Top Models</span>
                    </CardTitle>
                    <CardDescription>Most audited model types</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="model in modelsList" :key="model.model" class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ model.label }}</span>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-muted-foreground">{{ formatNumber(model.count) }}</span>
                                <Badge variant="outline" class="text-xs">{{ model.percentage }}%</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Users -->
            <Card v-if="stats.top_users.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2 text-base">
                        <Users class="h-4 w-4" />
                        <span>Most Active</span>
                    </CardTitle>
                    <CardDescription>Users with most activities</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="userStat in stats.top_users" :key="userStat.user.id" class="flex items-center justify-between">
                            <div class="flex min-w-0 items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                    {{
                                        userStat.user.name
                                            .split(' ')
                                            .map((n) => n[0])
                                            .join('')
                                            .toUpperCase()
                                            .slice(0, 2)
                                    }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium">{{ userStat.user.name }}</div>
                                    <div class="truncate text-xs text-muted-foreground">{{ userStat.user.email }}</div>
                                </div>
                            </div>
                            <div class="text-sm font-medium">{{ formatNumber(userStat.audit_count) }}</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Empty State -->
        <div v-if="stats.stats.total_audits === 0" class="py-8 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                <BarChart3 class="h-6 w-6 text-muted-foreground" />
            </div>
            <h3 class="mb-2 text-lg font-medium">No audit data available</h3>
            <p class="text-muted-foreground">Start using the system to see audit statistics here.</p>
        </div>
    </div>
</template>
