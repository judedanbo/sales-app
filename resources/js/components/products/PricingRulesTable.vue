<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type PaginatedData, type PricingRule, type PricingRuleFilters } from '@/types';
import { Calendar, Clock, Edit, MoreHorizontal, Percent, Tag, Trash2, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    rules: PaginatedData<PricingRule>;
    filters: PricingRuleFilters;
    isLoading?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    edit: [rule: PricingRule];
    delete: [rule: PricingRule];
    toggle: [rule: PricingRule];
    sort: [column: string];
    'page-change': [page: number];
}>();

const getRuleTypeIcon = (ruleType: string) => {
    switch (ruleType) {
        case 'bulk_discount':
            return Tag;
        case 'time_based':
            return Clock;
        case 'customer_group':
            return Users;
        case 'category_discount':
            return Tag;
        case 'clearance':
            return Percent;
        default:
            return Tag;
    }
};

const getRuleTypeColor = (ruleType: string) => {
    switch (ruleType) {
        case 'bulk_discount':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'time_based':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400';
        case 'customer_group':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'category_discount':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400';
        case 'clearance':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
        case 'scheduled':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'expired':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};

const formatRuleType = (ruleType: string) => {
    return ruleType.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const formatDiscount = (rule: PricingRule) => {
    if (!rule.actions || rule.actions.length === 0) return 'No discount';

    const action = rule.actions[0];
    switch (action.type) {
        case 'percentage_discount':
            return `${action.value}% off`;
        case 'fixed_discount':
            return `GH₵${action.value.toFixed(2)} off`;
        case 'fixed_price':
            return `GH₵${action.value.toFixed(2)}`;
        case 'buy_x_get_y':
            return `Buy X Get ${action.free_quantity || 1} Free`;
        default:
            return 'Custom discount';
    }
};

const isExpiringSoon = (rule: PricingRule) => {
    if (!rule.valid_to) return false;
    const expiryDate = new Date(rule.valid_to);
    const now = new Date();
    const daysDiff = Math.ceil((expiryDate.getTime() - now.getTime()) / (1000 * 3600 * 24));
    return daysDiff <= 7 && daysDiff > 0;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Percent class="h-5 w-5" />
                Pricing Rules
            </CardTitle>
            <CardDescription>
                Manage bulk discounts, time-based pricing, and promotional rules.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="space-y-4">
                <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                    <Skeleton class="h-12 w-12 rounded" />
                    <div class="space-y-2 flex-1">
                        <Skeleton class="h-4 w-[250px]" />
                        <Skeleton class="h-4 w-[200px]" />
                    </div>
                    <Skeleton class="h-8 w-[100px]" />
                </div>
            </div>

            <div v-else-if="rules.data.length === 0" class="text-center py-8">
                <Percent class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No pricing rules found</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    Create pricing rules to offer discounts and promotions.
                </p>
            </div>

            <div v-else class="space-y-4">
                <div class="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="cursor-pointer" @click="$emit('sort', 'name')">
                                    Rule Name
                                </TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Discount</TableHead>
                                <TableHead>Valid Period</TableHead>
                                <TableHead>Usage</TableHead>
                                <TableHead>Priority</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="rule in rules.data" :key="rule.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ rule.name }}</div>
                                        <div v-if="rule.description" class="text-sm text-gray-500">
                                            {{ rule.description }}
                                        </div>
                                        <div v-if="isExpiringSoon(rule)" class="flex items-center gap-1 text-orange-600 dark:text-orange-400 text-xs">
                                            <Calendar class="h-3 w-3" />
                                            Expires soon
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <component :is="getRuleTypeIcon(rule.rule_type)" class="h-4 w-4" />
                                        <Badge :class="getRuleTypeColor(rule.rule_type)" variant="secondary">
                                            {{ formatRuleType(rule.rule_type) }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ formatDiscount(rule) }}</div>
                                    <div v-if="rule.actions[0]?.max_discount" class="text-sm text-gray-500">
                                        Max: GH₵{{ rule.actions[0].max_discount.toFixed(2) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">From:</span> {{ formatDate(rule.valid_from) }}
                                        </div>
                                        <div v-if="rule.valid_to" class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">To:</span> {{ formatDate(rule.valid_to) }}
                                        </div>
                                        <div v-else class="text-sm text-gray-500">
                                            No expiry
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <div class="text-sm">
                                            {{ rule.usage_count }}{{ rule.usage_limit ? ` / ${rule.usage_limit}` : '' }}
                                        </div>
                                        <div v-if="rule.usage_limit" class="w-full">
                                            <Progress :value="(rule.usage_count / rule.usage_limit) * 100" class="h-1" />
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-400 text-sm font-medium">
                                            {{ rule.priority }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStatusColor(rule.status)" variant="secondary">
                                        {{ rule.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Button size="sm" variant="outline" @click="$emit('edit', rule)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button size="sm" variant="outline" @click="$emit('delete', rule)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="rules.last_page > 1" class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing {{ rules.from }} to {{ rules.to }} of {{ rules.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="rules.current_page === 1"
                            @click="$emit('page-change', rules.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm">
                            Page {{ rules.current_page }} of {{ rules.last_page }}
                        </span>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="rules.current_page === rules.last_page"
                            @click="$emit('page-change', rules.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>