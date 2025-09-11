<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, timeline } from '@/routes/audits';
import type { Audit, BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, GitBranch, Globe, Smartphone, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    audit: Audit;
    old_values?: Record<string, any>;
    new_values?: Record<string, any>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audits',
        href: index().url,
    },
    {
        title: `Audit #${props.audit.id}`,
        href: show(props.audit.id).url,
    },
];

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

function formatValue(value: any): string {
    if (value === null || value === undefined) {
        return 'null';
    }
    if (typeof value === 'boolean') {
        return value ? 'true' : 'false';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }
    return String(value);
}

// Compute changes between old and new values
const changes = computed(() => {
    const oldVals = props.old_values || {};
    const newVals = props.new_values || {};
    const allKeys = new Set([...Object.keys(oldVals), ...Object.keys(newVals)]);

    return Array.from(allKeys)
        .map((key) => {
            const oldVal = oldVals[key];
            const newVal = newVals[key];

            let changeType: 'added' | 'modified' | 'removed' = 'modified';
            if (!(key in oldVals)) changeType = 'added';
            else if (!(key in newVals)) changeType = 'removed';

            return {
                field: key,
                old_value: oldVal,
                new_value: newVal,
                type: changeType,
                hasChanged: JSON.stringify(oldVal) !== JSON.stringify(newVal),
            };
        })
        .filter((change) => change.hasChanged);
});

function getChangeTypeColor(type: string): string {
    switch (type) {
        case 'added':
            return 'text-green-600 dark:text-green-400';
        case 'removed':
            return 'text-red-600 dark:text-red-400';
        case 'modified':
            return 'text-blue-600 dark:text-blue-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
}

function getChangeTypeLabel(type: string): string {
    switch (type) {
        case 'added':
            return 'Added';
        case 'removed':
            return 'Removed';
        case 'modified':
            return 'Modified';
        default:
            return 'Changed';
    }
}
</script>

<template>
    <Head :title="`Audit Record #${audit.id}`" />

    <AppLayout>
        <template #header>
            <PageHeader
                :title="`Audit Record #${audit.id}`"
                :description="`${audit.event.charAt(0).toUpperCase() + audit.event.slice(1)} event for ${formatModelName(audit.auditable_type)} #${audit.auditable_id}`"
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Audits
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="timeline([encodeURIComponent(audit.auditable_type), audit.auditable_id]).url">
                            <GitBranch class="mr-2 h-4 w-4" />
                            View Timeline
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- Audit Details -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Event Details</CardTitle>
                        <CardDescription>Basic information about this audit record</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Event Type</span>
                            <Badge :class="getEventColor(audit.event)">
                                {{ audit.event }}
                            </Badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Model</span>
                            <span class="text-sm">{{ formatModelName(audit.auditable_type) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Model ID</span>
                            <span class="font-mono text-sm">#{{ audit.auditable_id }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Timestamp</span>
                            <span class="text-sm">{{ formatDate(audit.created_at) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- User & Context -->
                <Card>
                    <CardHeader>
                        <CardTitle>Context Information</CardTitle>
                        <CardDescription>Who performed this action and from where</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start justify-between">
                            <span class="flex items-center text-sm font-medium">
                                <User class="mr-2 h-4 w-4" />
                                User
                            </span>
                            <div class="text-right">
                                <div class="text-sm">{{ audit.user?.name || 'System' }}</div>
                                <div v-if="audit.user?.email" class="text-xs text-muted-foreground">{{ audit.user.email }}</div>
                            </div>
                        </div>

                        <div v-if="audit.ip_address" class="flex items-center justify-between">
                            <span class="flex items-center text-sm font-medium">
                                <Globe class="mr-2 h-4 w-4" />
                                IP Address
                            </span>
                            <span class="font-mono text-sm">{{ audit.ip_address }}</span>
                        </div>

                        <div v-if="audit.user_agent" class="flex items-start justify-between">
                            <span class="flex items-center text-sm font-medium">
                                <Smartphone class="mr-2 h-4 w-4" />
                                User Agent
                            </span>
                            <span class="max-w-xs truncate text-right text-xs text-muted-foreground">{{ audit.user_agent }}</span>
                        </div>

                        <div v-if="audit.url" class="flex items-start justify-between">
                            <span class="text-sm font-medium">URL</span>
                            <span class="max-w-xs truncate text-right text-xs text-muted-foreground">{{ audit.url }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Changes -->
            <Card v-if="changes.length > 0">
                <CardHeader>
                    <CardTitle>Changes Made</CardTitle>
                    <CardDescription>Detailed comparison of what was changed</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div v-for="change in changes" :key="change.field" class="rounded-lg border p-4">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-medium">{{ change.field }}</h4>
                                <Badge variant="outline" :class="getChangeTypeColor(change.type)">
                                    {{ getChangeTypeLabel(change.type) }}
                                </Badge>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <!-- Old Value -->
                                <div v-if="change.type !== 'added'">
                                    <h5 class="mb-2 text-sm font-medium text-red-600 dark:text-red-400">Before</h5>
                                    <div class="rounded border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                        <pre class="text-sm whitespace-pre-wrap text-red-800 dark:text-red-200">{{
                                            formatValue(change.old_value)
                                        }}</pre>
                                    </div>
                                </div>

                                <!-- New Value -->
                                <div v-if="change.type !== 'removed'">
                                    <h5 class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">After</h5>
                                    <div class="rounded border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                                        <pre class="text-sm whitespace-pre-wrap text-green-800 dark:text-green-200">{{
                                            formatValue(change.new_value)
                                        }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Raw Data -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Old Values -->
                <Card v-if="old_values && Object.keys(old_values).length > 0">
                    <CardHeader>
                        <CardTitle>Previous State</CardTitle>
                        <CardDescription>Complete previous values (JSON)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre class="max-h-96 overflow-auto rounded bg-muted p-4 text-xs">{{ JSON.stringify(old_values, null, 2) }}</pre>
                    </CardContent>
                </Card>

                <!-- New Values -->
                <Card v-if="new_values && Object.keys(new_values).length > 0">
                    <CardHeader>
                        <CardTitle>Current State</CardTitle>
                        <CardDescription>Complete current values (JSON)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre class="max-h-96 overflow-auto rounded bg-muted p-4 text-xs">{{ JSON.stringify(new_values, null, 2) }}</pre>
                    </CardContent>
                </Card>
            </div>

            <!-- No Changes Message -->
            <Card
                v-if="
                    changes.length === 0 &&
                    (!old_values || Object.keys(old_values).length === 0) &&
                    (!new_values || Object.keys(new_values).length === 0)
                "
            >
                <CardContent class="py-8 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <Clock class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <h3 class="mb-2 text-lg font-medium">No change data available</h3>
                    <p class="text-muted-foreground">This audit record doesn't contain detailed change information.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
