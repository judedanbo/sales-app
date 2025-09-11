<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show, timeline } from '@/routes/audits';
import type { Audit } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Clock, Eye, GitBranch, Globe, User } from 'lucide-vue-next';

interface Props {
    audit: Audit;
    showActions?: boolean;
    compact?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
    compact: false,
});

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

function getChangesSummary(): string {
    if (!props.audit.new_values && !props.audit.old_values) return 'No changes tracked';

    if (props.audit.event === 'created') {
        const count = Object.keys(props.audit.new_values || {}).length;
        return `${count} field${count !== 1 ? 's' : ''} set`;
    }

    if (props.audit.event === 'deleted') {
        return 'Record deleted';
    }

    if (props.audit.event === 'restored') {
        return 'Record restored';
    }

    const oldKeys = Object.keys(props.audit.old_values || {});
    const newKeys = Object.keys(props.audit.new_values || {});
    const allKeys = new Set([...oldKeys, ...newKeys]);

    const changedCount = Array.from(allKeys).filter((key) => {
        const oldVal = props.audit.old_values?.[key];
        const newVal = props.audit.new_values?.[key];
        return JSON.stringify(oldVal) !== JSON.stringify(newVal);
    }).length;

    return changedCount > 0 ? `${changedCount} field${changedCount !== 1 ? 's' : ''} changed` : 'No changes';
}
</script>

<template>
    <Card class="transition-shadow hover:shadow-md">
        <CardContent :class="compact ? 'p-4' : 'p-6'">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <!-- Event & Model -->
                    <div class="mb-3 flex items-center space-x-3">
                        <Badge :class="getEventColor(audit.event)">
                            {{ audit.event }}
                        </Badge>
                        <div class="text-sm">
                            <span class="font-medium">{{ formatModelName(audit.auditable_type) }}</span>
                            <span class="text-muted-foreground">#{{ audit.auditable_id }}</span>
                        </div>
                    </div>

                    <!-- User & Time -->
                    <div class="mb-3 space-y-2">
                        <div class="flex items-center space-x-2 text-sm">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <span>{{ audit.user?.name || 'System' }}</span>
                            <span v-if="audit.user?.email" class="text-muted-foreground">({{ audit.user.email }})</span>
                        </div>

                        <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                            <Clock class="h-4 w-4" />
                            <span>{{ getRelativeTime(audit.created_at) }}</span>
                            <span>•</span>
                            <span>{{ formatDate(audit.created_at) }}</span>
                        </div>

                        <div v-if="audit.ip_address" class="flex items-center space-x-2 text-sm text-muted-foreground">
                            <Globe class="h-4 w-4" />
                            <span>{{ audit.ip_address }}</span>
                        </div>
                    </div>

                    <!-- Changes Summary -->
                    <div v-if="!compact" class="mb-4 text-sm text-muted-foreground">
                        {{ getChangesSummary() }}
                    </div>

                    <!-- Custom Changes Summary from Backend -->
                    <div v-if="audit.changes_summary && audit.changes_summary.length > 0" class="mb-4">
                        <div class="mb-1 text-xs font-medium text-muted-foreground">Changes:</div>
                        <ul class="space-y-1 text-sm">
                            <li v-for="summary in audit.changes_summary.slice(0, 3)" :key="summary" class="text-muted-foreground">• {{ summary }}</li>
                            <li v-if="audit.changes_summary.length > 3" class="text-xs text-muted-foreground">
                                ... and {{ audit.changes_summary.length - 3 }} more
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="showActions" class="ml-4 flex-shrink-0">
                    <div class="flex space-x-2">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="show(audit.id).url">
                                <Eye class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="timeline([encodeURIComponent(audit.auditable_type), audit.auditable_id]).url">
                                <GitBranch class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
