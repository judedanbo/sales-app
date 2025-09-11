<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown, ChevronRight, Edit, Minus, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    oldValues?: Record<string, any>;
    newValues?: Record<string, any>;
    event: string;
    title?: string;
    collapsible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Changes',
    collapsible: false,
});

const isOpen = ref(!props.collapsible);

// Compute changes between old and new values
const changes = computed(() => {
    const oldVals = props.oldValues || {};
    const newVals = props.newValues || {};
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

function formatValue(value: any): string {
    if (value === null || value === undefined) {
        return 'null';
    }
    if (typeof value === 'boolean') {
        return value ? 'true' : 'false';
    }
    if (typeof value === 'object') {
        try {
            return JSON.stringify(value, null, 2);
        } catch {
            return String(value);
        }
    }
    return String(value);
}

function getChangeTypeColor(type: string): string {
    switch (type) {
        case 'added':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'removed':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        case 'modified':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
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

function getChangeTypeIcon(type: string) {
    switch (type) {
        case 'added':
            return Plus;
        case 'removed':
            return Minus;
        case 'modified':
            return Edit;
        default:
            return Edit;
    }
}

function isComplexValue(value: any): boolean {
    return typeof value === 'object' && value !== null;
}

function shouldTruncateValue(value: string): boolean {
    return value.length > 100 || value.includes('\n');
}

function truncateValue(value: string, maxLength = 100): string {
    if (value.length <= maxLength && !value.includes('\n')) return value;

    const firstLine = value.split('\n')[0];
    if (firstLine.length <= maxLength) return firstLine + '...';

    return firstLine.substring(0, maxLength) + '...';
}
</script>

<template>
    <div>
        <div v-if="changes.length > 0">
            <Collapsible v-if="collapsible" v-model:open="isOpen">
                <CollapsibleTrigger class="flex w-full items-center space-x-2 rounded p-2 hover:bg-muted/50">
                    <component :is="isOpen ? ChevronDown : ChevronRight" class="h-4 w-4" />
                    <h3 class="font-medium">{{ title }}</h3>
                    <Badge variant="outline">{{ changes.length }} changes</Badge>
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <div class="mt-4 space-y-4">
                        <div v-for="change in changes" :key="change.field" class="rounded-lg border p-4">
                            <div class="mb-4 flex items-center justify-between">
                                <h4 class="font-medium">{{ change.field }}</h4>
                                <div class="flex items-center space-x-2">
                                    <Badge :class="getChangeTypeColor(change.type)">
                                        <component :is="getChangeTypeIcon(change.type)" class="mr-1 h-3 w-3" />
                                        {{ getChangeTypeLabel(change.type) }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="grid gap-4" :class="change.type === 'modified' ? 'md:grid-cols-2' : ''">
                                <!-- Old Value -->
                                <div v-if="change.type !== 'added'">
                                    <h5 class="mb-2 text-sm font-medium text-red-600 dark:text-red-400">Before</h5>
                                    <div class="rounded border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                        <pre
                                            v-if="isComplexValue(change.old_value)"
                                            class="max-h-32 overflow-auto text-sm whitespace-pre-wrap text-red-800 dark:text-red-200"
                                            >{{ formatValue(change.old_value) }}</pre
                                        >
                                        <div v-else class="text-sm text-red-800 dark:text-red-200">
                                            <div v-if="shouldTruncateValue(formatValue(change.old_value))">
                                                <span class="truncate">{{ truncateValue(formatValue(change.old_value)) }}</span>
                                                <details class="mt-2">
                                                    <summary class="cursor-pointer text-xs text-red-600 hover:text-red-800">Show full value</summary>
                                                    <pre class="mt-2 whitespace-pre-wrap">{{ formatValue(change.old_value) }}</pre>
                                                </details>
                                            </div>
                                            <span v-else>{{ formatValue(change.old_value) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Value -->
                                <div v-if="change.type !== 'removed'">
                                    <h5 class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">After</h5>
                                    <div class="rounded border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                                        <pre
                                            v-if="isComplexValue(change.new_value)"
                                            class="max-h-32 overflow-auto text-sm whitespace-pre-wrap text-green-800 dark:text-green-200"
                                            >{{ formatValue(change.new_value) }}</pre
                                        >
                                        <div v-else class="text-sm text-green-800 dark:text-green-200">
                                            <div v-if="shouldTruncateValue(formatValue(change.new_value))">
                                                <span class="truncate">{{ truncateValue(formatValue(change.new_value)) }}</span>
                                                <details class="mt-2">
                                                    <summary class="cursor-pointer text-xs text-green-600 hover:text-green-800">
                                                        Show full value
                                                    </summary>
                                                    <pre class="mt-2 whitespace-pre-wrap">{{ formatValue(change.new_value) }}</pre>
                                                </details>
                                            </div>
                                            <span v-else>{{ formatValue(change.new_value) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>

            <!-- Non-collapsible version -->
            <div v-else>
                <h3 v-if="title" class="mb-4 flex items-center space-x-2 font-medium">
                    <span>{{ title }}</span>
                    <Badge variant="outline">{{ changes.length }} changes</Badge>
                </h3>

                <div class="space-y-4">
                    <div v-for="change in changes" :key="change.field" class="rounded-lg border p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="font-medium">{{ change.field }}</h4>
                            <Badge :class="getChangeTypeColor(change.type)">
                                <component :is="getChangeTypeIcon(change.type)" class="mr-1 h-3 w-3" />
                                {{ getChangeTypeLabel(change.type) }}
                            </Badge>
                        </div>

                        <div class="grid gap-4" :class="change.type === 'modified' ? 'md:grid-cols-2' : ''">
                            <!-- Old Value -->
                            <div v-if="change.type !== 'added'">
                                <h5 class="mb-2 text-sm font-medium text-red-600 dark:text-red-400">Before</h5>
                                <div class="rounded border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                    <pre
                                        v-if="isComplexValue(change.old_value)"
                                        class="max-h-32 overflow-auto text-sm whitespace-pre-wrap text-red-800 dark:text-red-200"
                                        >{{ formatValue(change.old_value) }}</pre
                                    >
                                    <div v-else class="text-sm text-red-800 dark:text-red-200">
                                        <div v-if="shouldTruncateValue(formatValue(change.old_value))">
                                            <span class="truncate">{{ truncateValue(formatValue(change.old_value)) }}</span>
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs text-red-600 hover:text-red-800">Show full value</summary>
                                                <pre class="mt-2 whitespace-pre-wrap">{{ formatValue(change.old_value) }}</pre>
                                            </details>
                                        </div>
                                        <span v-else>{{ formatValue(change.old_value) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- New Value -->
                            <div v-if="change.type !== 'removed'">
                                <h5 class="mb-2 text-sm font-medium text-green-600 dark:text-green-400">After</h5>
                                <div class="rounded border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                                    <pre
                                        v-if="isComplexValue(change.new_value)"
                                        class="max-h-32 overflow-auto text-sm whitespace-pre-wrap text-green-800 dark:text-green-200"
                                        >{{ formatValue(change.new_value) }}</pre
                                    >
                                    <div v-else class="text-sm text-green-800 dark:text-green-200">
                                        <div v-if="shouldTruncateValue(formatValue(change.new_value))">
                                            <span class="truncate">{{ truncateValue(formatValue(change.new_value)) }}</span>
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs text-green-600 hover:text-green-800">Show full value</summary>
                                                <pre class="mt-2 whitespace-pre-wrap">{{ formatValue(change.new_value) }}</pre>
                                            </details>
                                        </div>
                                        <span v-else>{{ formatValue(change.new_value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Changes Message -->
        <div v-else class="py-8 text-center text-muted-foreground">
            <p>No changes to display</p>
            <p class="text-sm">
                {{
                    event === 'created'
                        ? 'This record was created but no field changes were tracked.'
                        : event === 'deleted'
                          ? 'This record was deleted but no field changes were tracked.'
                          : 'No field changes were recorded for this event.'
                }}
            </p>
        </div>
    </div>
</template>
