<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, timeline } from '@/routes/audits';
import type { AuditTimelineEntry, BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Edit, Eye, Plus, RotateCcw, Trash } from 'lucide-vue-next';

interface Props {
    audits: AuditTimelineEntry[];
    model_type: string;
    model_id: number;
    model_name: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audits',
        href: index().url,
    },
    {
        title: `${props.model_name} Timeline`,
        href: timeline([encodeURIComponent(props.model_type), props.model_id]).url,
    },
];

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
            return Plus;
        case 'updated':
            return Edit;
        case 'deleted':
            return Trash;
        case 'restored':
            return RotateCcw;
        default:
            return Clock;
    }
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

function formatModelName(auditableType: string): string {
    return auditableType.split('\\').pop() || auditableType;
}

function getChangesCount(audit: AuditTimelineEntry): number {
    if (!audit.new_values && !audit.old_values) return 0;
    if (audit.event === 'created') return Object.keys(audit.new_values || {}).length;
    if (audit.event === 'deleted') return Object.keys(audit.old_values || {}).length;

    const oldKeys = Object.keys(audit.old_values || {});
    const newKeys = Object.keys(audit.new_values || {});
    const allKeys = new Set([...oldKeys, ...newKeys]);

    return Array.from(allKeys).filter((key) => {
        const oldVal = audit.old_values?.[key];
        const newVal = audit.new_values?.[key];
        return JSON.stringify(oldVal) !== JSON.stringify(newVal);
    }).length;
}

function hasDetailedChanges(audit: AuditTimelineEntry): boolean {
    if (!audit.old_values && !audit.new_values) return false;
    if (audit.event === 'created' && audit.new_values && Object.keys(audit.new_values).length > 0) return true;
    if (audit.event === 'updated' && (audit.old_values || audit.new_values)) return true;
    if (audit.event === 'deleted' && audit.old_values && Object.keys(audit.old_values).length > 0) return true;
    return false;
}

function formatValue(value: any): string {
    if (value === null || value === undefined) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') {
        try {
            const stringified = JSON.stringify(value);
            return stringified.length > 50 ? stringified.substring(0, 50) + '...' : stringified;
        } catch {
            return '[Object]';
        }
    }
    const stringValue = String(value);
    return stringValue.length > 50 ? stringValue.substring(0, 50) + '...' : stringValue;
}

function getFormattedChanges(audit: AuditTimelineEntry): Record<string, { oldValue: string | null; newValue: string }> {
    const changes: Record<string, { oldValue: string | null; newValue: string }> = {};

    if (audit.event === 'created' && audit.new_values) {
        Object.entries(audit.new_values).forEach(([key, value]) => {
            changes[key] = {
                oldValue: null,
                newValue: formatValue(value),
            };
        });
    } else if (audit.event === 'updated') {
        const oldValues = audit.old_values || {};
        const newValues = audit.new_values || {};
        const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);

        allKeys.forEach((key) => {
            const oldVal = oldValues[key];
            const newVal = newValues[key];

            if (JSON.stringify(oldVal) !== JSON.stringify(newVal)) {
                changes[key] = {
                    oldValue: oldVal !== undefined ? formatValue(oldVal) : null,
                    newValue: formatValue(newVal),
                };
            }
        });
    } else if (audit.event === 'deleted' && audit.old_values) {
        Object.entries(audit.old_values).forEach(([key, value]) => {
            changes[key] = {
                oldValue: formatValue(value),
                newValue: '[DELETED]',
            };
        });
    }

    return changes;
}
</script>

<template>
    <Head :title="`${model_name} Timeline`" />

    <AppLayout>
        <template #header>
            <PageHeader
                :title="`${model_name} #${model_id} Timeline`"
                :description="`Complete audit trail for ${model_name} record #${model_id}`"
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Audits
                        </Link>
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- Summary Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Timeline Summary</CardTitle>
                    <CardDescription>Activity overview for this record</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ audits.length }}</div>
                            <div class="text-sm text-muted-foreground">Total Events</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ audits.filter((a) => a.event === 'created').length }}</div>
                            <div class="text-sm text-muted-foreground">Created</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ audits.filter((a) => a.event === 'updated').length }}</div>
                            <div class="text-sm text-muted-foreground">Updates</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ audits.filter((a) => a.event === 'deleted').length }}</div>
                            <div class="text-sm text-muted-foreground">Deletions</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Timeline -->
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute top-0 bottom-0 left-8 w-px bg-border"></div>

                <!-- Timeline Events -->
                <div class="space-y-6">
                    <div v-for="(audit, index) in audits" :key="audit.id" class="relative flex items-start space-x-4">
                        <!-- Event Icon -->
                        <div class="relative flex-shrink-0">
                            <div :class="[getEventColor(audit.event), 'flex h-8 w-8 items-center justify-center rounded-full']">
                                <component :is="getEventIcon(audit.event)" class="h-4 w-4 text-white" />
                            </div>
                            <!-- Connection line for non-first items -->
                            <div v-if="index < audits.length - 1" class="absolute top-8 left-1/2 h-6 w-px -translate-x-1/2 transform bg-border"></div>
                        </div>

                        <!-- Event Content -->
                        <Card class="flex-1">
                            <CardContent class="p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Event Header -->
                                        <div class="mb-2 flex items-center space-x-2">
                                            <Badge
                                                :class="
                                                    getEventColor(audit.event)
                                                        .replace('bg-', 'bg-')
                                                        .replace(
                                                            '500',
                                                            '100 text-' + audit.event === 'created'
                                                                ? 'green'
                                                                : audit.event === 'updated'
                                                                  ? 'blue'
                                                                  : audit.event === 'deleted'
                                                                    ? 'red'
                                                                    : 'yellow',
                                                        ) + '-800'
                                                "
                                            >
                                                {{ audit.event.charAt(0).toUpperCase() + audit.event.slice(1) }}
                                            </Badge>
                                            <span class="text-sm text-muted-foreground">{{ getRelativeTime(audit.created_at) }}</span>
                                        </div>

                                        <!-- Event Description -->
                                        <div class="mb-3">
                                            <p class="text-sm">
                                                <span class="font-medium">{{ audit.user?.name || 'System' }}</span>
                                                <span class="text-muted-foreground">
                                                    {{
                                                        audit.event === 'created'
                                                            ? ' created this record'
                                                            : audit.event === 'updated'
                                                              ? ' updated this record'
                                                              : audit.event === 'deleted'
                                                                ? ' deleted this record'
                                                                : audit.event === 'restored'
                                                                  ? ' restored this record'
                                                                  : ` performed ${audit.event} on this record`
                                                    }}
                                                </span>
                                            </p>
                                            <p class="text-xs text-muted-foreground">{{ formatDate(audit.created_at) }}</p>
                                        </div>

                                        <!-- Changes Summary -->
                                        <div v-if="audit.changes_summary && audit.changes_summary.length > 0" class="mb-3">
                                            <div class="mb-1 text-xs font-medium text-muted-foreground">Changes:</div>
                                            <ul class="space-y-1 text-sm">
                                                <li v-for="summary in audit.changes_summary" :key="summary" class="text-muted-foreground">
                                                    • {{ summary }}
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Change Count -->
                                        <div v-if="getChangesCount(audit) > 0" class="text-xs text-muted-foreground">
                                            {{ getChangesCount(audit) }} field{{ getChangesCount(audit) !== 1 ? 's' : '' }}
                                            {{ audit.event === 'created' ? 'set' : audit.event === 'deleted' ? 'removed' : 'changed' }}
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex-shrink-0">
                                        <Button variant="ghost" size="sm" as-child>
                                            <Link :href="show(audit.id).url">
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Detailed Changes (for updates) -->
                                <div v-if="hasDetailedChanges(audit)" class="mt-4 border-t pt-4">
                                    <div class="grid max-h-40 gap-2 overflow-y-auto">
                                        <div v-for="(change, key) in getFormattedChanges(audit)" :key="key" class="flex justify-between text-xs">
                                            <span class="font-medium">{{ key }}:</span>
                                            <div class="flex space-x-2">
                                                <span v-if="change.oldValue !== null" class="text-red-600 line-through">
                                                    {{ change.oldValue }}
                                                </span>
                                                <span class="text-green-600">
                                                    {{ change.newValue }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <Card v-if="audits.length === 0">
                <CardContent class="py-8 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                        <Clock class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <h3 class="mb-2 text-lg font-medium">No audit records found</h3>
                    <p class="text-muted-foreground">This record doesn't have any audit trail yet.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
