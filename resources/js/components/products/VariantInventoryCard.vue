<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { type ProductInventory, type ProductVariant } from '@/types';
import { AlertTriangle, Edit, Package, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    variant: ProductVariant;
    inventory?: ProductInventory | null;
    isLoading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'edit-inventory': [variant: ProductVariant];
    'view-movements': [variant: ProductVariant];
}>();

const stockPercentage = computed(() => {
    if (!props.inventory?.maximum_stock_level || props.inventory.maximum_stock_level === 0) {
        return 0;
    }
    return Math.min((props.inventory.quantity_on_hand / props.inventory.maximum_stock_level) * 100, 100);
});

const stockStatus = computed(() => {
    if (!props.inventory) {
        return { text: 'No Inventory', color: 'gray', bgColor: 'bg-gray-100 dark:bg-gray-800' };
    }

    if (props.inventory.quantity_on_hand === 0) {
        return { text: 'Out of Stock', color: 'red', bgColor: 'bg-red-100 dark:bg-red-900/20' };
    } else if (props.inventory.is_low_stock) {
        return { text: 'Low Stock', color: 'yellow', bgColor: 'bg-yellow-100 dark:bg-yellow-900/20' };
    } else {
        return { text: 'In Stock', color: 'green', bgColor: 'bg-green-100 dark:bg-green-900/20' };
    }
});

const reorderNeeded = computed(() => {
    if (!props.inventory?.reorder_point) return false;
    return props.inventory.quantity_on_hand <= props.inventory.reorder_point;
});

const availabilityPercentage = computed(() => {
    if (!props.inventory?.quantity_on_hand) return 0;
    return Math.round((props.inventory.quantity_available / props.inventory.quantity_on_hand) * 100);
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Package class="h-5 w-5" />
                    Inventory
                </div>
                <Button size="sm" variant="outline" @click="$emit('edit-inventory', variant)">
                    <Edit class="h-4 w-4" />
                </Button>
            </CardTitle>
            <CardDescription>
                Stock levels and inventory management for {{ variant.display_name }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Skeleton class="h-4 w-20" />
                        <Skeleton class="h-8 w-16" />
                    </div>
                    <div class="space-y-2">
                        <Skeleton class="h-4 w-24" />
                        <Skeleton class="h-8 w-20" />
                    </div>
                </div>
                <Skeleton class="h-2 w-full" />
            </div>

            <div v-else-if="!inventory" class="text-center py-8">
                <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No inventory record</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    This variant doesn't have an inventory record yet.
                </p>
                <Button @click="$emit('edit-inventory', variant)">
                    Create Inventory Record
                </Button>
            </div>

            <div v-else class="space-y-6">
                <!-- Stock Status -->
                <div class="flex items-center justify-between">
                    <Badge :class="`${stockStatus.bgColor} text-${stockStatus.color}-800 dark:text-${stockStatus.color}-400`">
                        {{ stockStatus.text }}
                    </Badge>
                    <div v-if="reorderNeeded" class="flex items-center gap-1 text-orange-600 dark:text-orange-400">
                        <AlertTriangle class="h-4 w-4" />
                        <span class="text-sm font-medium">Reorder needed</span>
                    </div>
                </div>

                <!-- Stock Numbers -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ inventory.quantity_on_hand }}
                        </div>
                        <div class="text-sm text-gray-500">On Hand</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ inventory.quantity_available }}
                        </div>
                        <div class="text-sm text-gray-500">Available</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ inventory.quantity_reserved }}
                        </div>
                        <div class="text-sm text-gray-500">Reserved</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ availabilityPercentage }}%
                        </div>
                        <div class="text-sm text-gray-500">Available</div>
                    </div>
                </div>

                <!-- Stock Level Progress -->
                <div v-if="inventory.maximum_stock_level" class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Stock Level</span>
                        <span>{{ inventory.quantity_on_hand }} / {{ inventory.maximum_stock_level }}</span>
                    </div>
                    <Progress :value="stockPercentage" class="h-2" />
                </div>

                <!-- Stock Thresholds -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div v-if="inventory.minimum_stock_level" class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/10 rounded-lg">
                        <div class="flex items-center gap-2">
                            <TrendingDown class="h-4 w-4 text-red-600 dark:text-red-400" />
                            <span class="font-medium">Min Level</span>
                        </div>
                        <span class="font-mono">{{ inventory.minimum_stock_level }}</span>
                    </div>

                    <div v-if="inventory.reorder_point" class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/10 rounded-lg">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            <span class="font-medium">Reorder Point</span>
                        </div>
                        <span class="font-mono">{{ inventory.reorder_point }}</span>
                    </div>

                    <div v-if="inventory.maximum_stock_level" class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/10 rounded-lg">
                        <div class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <span class="font-medium">Max Level</span>
                        </div>
                        <span class="font-mono">{{ inventory.maximum_stock_level }}</span>
                    </div>
                </div>

                <!-- Reorder Information -->
                <div v-if="inventory.reorder_quantity" class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                    <div class="flex items-center gap-2">
                        <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium">Reorder Quantity</span>
                    </div>
                    <span class="font-mono text-blue-600 dark:text-blue-400">{{ inventory.reorder_quantity }}</span>
                </div>

                <!-- Last Activity -->
                <div v-if="inventory.last_movement_at" class="text-sm text-gray-500 border-t pt-4">
                    <div class="flex justify-between">
                        <span>Last stock movement:</span>
                        <span>{{ new Date(inventory.last_movement_at).toLocaleDateString() }}</span>
                    </div>
                    <div v-if="inventory.last_stock_count" class="flex justify-between mt-1">
                        <span>Last stock count:</span>
                        <span>{{ new Date(inventory.last_stock_count).toLocaleDateString() }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 pt-4 border-t">
                    <Button size="sm" variant="outline" @click="$emit('view-movements', variant)" class="flex-1">
                        View Movements
                    </Button>
                    <Button size="sm" variant="outline" @click="$emit('edit-inventory', variant)" class="flex-1">
                        Adjust Stock
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>