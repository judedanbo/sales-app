<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import Progress from '@/components/ui/progress.vue';
import { type ProductInventory } from '@/types';
import { AlertTriangle, CheckCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    inventory: ProductInventory;
    reorderLevel?: number;
    size?: 'sm' | 'md' | 'lg';
    showProgress?: boolean;
    showBadge?: boolean;
    showDetails?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showProgress: true,
    showBadge: true,
    showDetails: false,
});

const stockLevel = computed(() => {
    const current = props.inventory.quantity_on_hand || 0;
    const minimum = props.inventory.minimum_stock_level || props.reorderLevel || 0;

    if (current === 0) {
        return {
            status: 'out-of-stock',
            label: 'Out of Stock',
            percentage: 0,
            color: 'destructive',
            bgColor: 'bg-red-100 dark:bg-red-900/20',
            textColor: 'text-red-700 dark:text-red-300',
            icon: XCircle,
        };
    }

    if (current <= minimum * 0.25) {
        return {
            status: 'critical',
            label: 'Critical Low',
            percentage: (current / minimum) * 100,
            color: 'destructive',
            bgColor: 'bg-red-100 dark:bg-red-900/20',
            textColor: 'text-red-700 dark:text-red-300',
            icon: AlertTriangle,
        };
    }

    if (current <= minimum * 0.5) {
        return {
            status: 'very-low',
            label: 'Very Low',
            percentage: (current / minimum) * 100,
            color: 'destructive',
            bgColor: 'bg-orange-100 dark:bg-orange-900/20',
            textColor: 'text-orange-700 dark:text-orange-300',
            icon: AlertTriangle,
        };
    }

    if (current <= minimum) {
        return {
            status: 'low',
            label: 'Low Stock',
            percentage: (current / minimum) * 100,
            color: 'secondary',
            bgColor: 'bg-yellow-100 dark:bg-yellow-900/20',
            textColor: 'text-yellow-700 dark:text-yellow-300',
            icon: AlertTriangle,
        };
    }

    return {
        status: 'in-stock',
        label: 'In Stock',
        percentage: Math.min((current / minimum) * 100, 100),
        color: 'secondary',
        bgColor: 'bg-green-100 dark:bg-green-900/20',
        textColor: 'text-green-700 dark:text-green-300',
        icon: CheckCircle,
    };
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return {
                container: 'space-y-1',
                badge: 'text-xs px-2 py-1',
                progress: 'h-1',
                text: 'text-xs',
                icon: 'h-3 w-3',
            };
        case 'lg':
            return {
                container: 'space-y-3',
                badge: 'text-sm px-3 py-1',
                progress: 'h-3',
                text: 'text-sm',
                icon: 'h-5 w-5',
            };
        default:
            return {
                container: 'space-y-2',
                badge: 'text-xs px-2.5 py-1',
                progress: 'h-2',
                text: 'text-sm',
                icon: 'h-4 w-4',
            };
    }
});

const progressColor = computed(() => {
    switch (stockLevel.value.status) {
        case 'out-of-stock':
        case 'critical':
        case 'very-low':
            return 'bg-red-500';
        case 'low':
            return 'bg-yellow-500';
        default:
            return 'bg-green-500';
    }
});
</script>

<template>
    <div :class="sizeClasses.container">
        <!-- Stock Badge -->
        <div v-if="showBadge" class="flex items-center space-x-2">
            <Badge
                :variant="stockLevel.color"
                :class="[sizeClasses.badge, stockLevel.bgColor, stockLevel.textColor]"
            >
                <component :is="stockLevel.icon" :class="['mr-1', sizeClasses.icon]" />
                {{ stockLevel.label }}
            </Badge>
        </div>

        <!-- Progress Bar -->
        <div v-if="showProgress" class="space-y-1">
            <Progress
                :value="Math.min(stockLevel.percentage, 100)"
                :class="[sizeClasses.progress, `progress-${stockLevel.status}`]"
            />
            <div v-if="showDetails" class="flex justify-between" :class="sizeClasses.text">
                <span class="text-muted-foreground">
                    {{ inventory.quantity_on_hand }} / {{ inventory.minimum_stock_level || reorderLevel || 0 }}
                </span>
                <span :class="stockLevel.textColor">
                    {{ stockLevel.percentage.toFixed(0) }}%
                </span>
            </div>
        </div>

        <!-- Detailed Info -->
        <div v-if="showDetails && !showProgress" class="space-y-1" :class="sizeClasses.text">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">On Hand:</span>
                <span class="font-mono font-medium">{{ inventory.quantity_on_hand }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Available:</span>
                <span class="font-mono font-medium">{{ inventory.quantity_available }}</span>
            </div>
            <div v-if="inventory.quantity_reserved > 0" class="flex items-center justify-between">
                <span class="text-muted-foreground">Reserved:</span>
                <span class="font-mono font-medium text-orange-600">{{ inventory.quantity_reserved }}</span>
            </div>
            <div v-if="inventory.minimum_stock_level" class="flex items-center justify-between">
                <span class="text-muted-foreground">Min Level:</span>
                <span class="font-mono font-medium">{{ inventory.minimum_stock_level }}</span>
            </div>
            <div v-if="inventory.maximum_stock_level" class="flex items-center justify-between">
                <span class="text-muted-foreground">Max Level:</span>
                <span class="font-mono font-medium">{{ inventory.maximum_stock_level }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom progress colors */
.progress-out-of-stock :deep(.bg-primary) {
    background-color: rgb(239, 68, 68);
}

.progress-critical :deep(.bg-primary) {
    background-color: rgb(239, 68, 68);
}

.progress-very-low :deep(.bg-primary) {
    background-color: rgb(249, 115, 22);
}

.progress-low :deep(.bg-primary) {
    background-color: rgb(234, 179, 8);
}

.progress-in-stock :deep(.bg-primary) {
    background-color: rgb(34, 197, 94);
}
</style>