<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import StockAdjustmentModal from '@/components/products/StockAdjustmentModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PageHeader from '@/components/ui/PageHeader.vue';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product, type ProductInventory, type StockMovement } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, BarChart3, Edit, Package, Save, TrendingDown, TrendingUp, Warehouse } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { addAlert } = useAlerts();

interface Props {
    product: Product;
    inventory: ProductInventory | null;
    recent_movements: StockMovement[];
    isLoading?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
    {
        title: props.product.name,
        href: `/products/${props.product.id}`,
    },
    {
        title: 'Inventory',
        href: `/products/${props.product.id}/inventory`,
    },
];

const showAdjustmentModal = ref(false);
const isEditingReorderLevel = ref(false);
const newReorderLevel = ref(props.product.reorder_level || 0);
const isSavingReorderLevel = ref(false);

// Computed properties
const stockHealthPercentage = computed(() => {
    if (!props.inventory || !props.product.reorder_level) return 100;
    const current = props.inventory.quantity_on_hand;
    const reorder = props.product.reorder_level;
    const optimal = reorder * 3; // Assume optimal stock is 3x reorder level
    return Math.min(100, (current / optimal) * 100);
});

const stockStatus = computed(() => {
    if (!props.inventory) return { text: 'No inventory data', color: 'text-muted-foreground' };
    if (props.inventory.is_out_of_stock) return { text: 'Out of Stock', color: 'text-red-600' };
    if (props.inventory.quantity_on_hand <= (props.product.reorder_level || 0)) {
        return { text: 'Low Stock', color: 'text-orange-600' };
    }
    return { text: 'In Stock', color: 'text-green-600' };
});

const stockHealthColor = computed(() => {
    const percentage = stockHealthPercentage.value;
    if (percentage < 25) return 'bg-red-500';
    if (percentage < 50) return 'bg-orange-500';
    if (percentage < 75) return 'bg-yellow-500';
    return 'bg-green-500';
});

// Event handlers
const handleAdjustStock = () => {
    showAdjustmentModal.value = true;
};

const handleEditReorderLevel = () => {
    isEditingReorderLevel.value = true;
    newReorderLevel.value = props.product.reorder_level || 0;
};

const handleSaveReorderLevel = () => {
    if (newReorderLevel.value === props.product.reorder_level) {
        isEditingReorderLevel.value = false;
        return;
    }

    isSavingReorderLevel.value = true;

    // In a real implementation, this would make an API call to update the reorder level
    setTimeout(() => {
        addAlert(
            `Reorder level updated from ${props.product.reorder_level || 0} to ${newReorderLevel.value}`,
            'success',
            { title: 'Reorder Level Updated' }
        );

        isSavingReorderLevel.value = false;
        isEditingReorderLevel.value = false;

        // Refresh the page to show the updated reorder level
        router.reload();
    }, 1000);
};

const handleCancelReorderEdit = () => {
    isEditingReorderLevel.value = false;
    newReorderLevel.value = props.product.reorder_level || 0;
};

const handleStockAdjusted = () => {
    showAdjustmentModal.value = false;
    addAlert(
        'Stock adjustment has been recorded successfully.',
        'success',
        { title: 'Stock Adjusted' }
    );
    // Refresh the page to show the updated stock
    router.reload();
};

// Helper functions
const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getMovementTypeIcon = (type: string) => {
    switch (type) {
        case 'in':
        case 'restock':
        case 'adjustment_in':
            return TrendingUp;
        case 'out':
        case 'sale':
        case 'adjustment_out':
            return TrendingDown;
        default:
            return BarChart3;
    }
};

const getMovementTypeColor = (type: string): string => {
    switch (type) {
        case 'in':
        case 'restock':
        case 'adjustment_in':
            return 'text-green-600';
        case 'out':
        case 'sale':
        case 'adjustment_out':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
};

const formatMovementType = (type: string): string => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head :title="`${product.name} - Inventory`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader
                :title="`${product.name} Inventory`"
                :description="`Track stock levels and movements for ${product.name}`"
            >
                <template #action>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" @click="router.visit(`/products/${product.id}`)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Product
                        </Button>

                        <PermissionGuard permission="edit_products">
                            <Button @click="handleAdjustStock" :disabled="!inventory">
                                <Edit class="mr-2 h-4 w-4" />
                                Adjust Stock
                            </Button>
                        </PermissionGuard>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Stock Overview -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Stock Levels -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Warehouse class="h-5 w-5" />
                                Current Stock Levels
                            </CardTitle>
                            <CardDescription>Real-time inventory status for {{ product.name }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="isLoading" class="space-y-4">
                                <div class="grid grid-cols-3 gap-4">
                                    <div v-for="i in 3" :key="i" class="space-y-2">
                                        <Skeleton class="h-4 w-20" />
                                        <Skeleton class="h-8 w-16" />
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="!inventory" class="text-center py-8">
                                <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <p class="text-gray-500 dark:text-gray-400">No inventory data available</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    Set up inventory tracking for this product to see stock levels.
                                </p>
                            </div>

                            <div v-else class="space-y-6">
                                <!-- Stock Metrics -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ inventory.quantity_on_hand.toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">On Hand</div>
                                    </div>

                                    <div class="text-center p-4 bg-green-50 rounded-lg">
                                        <div class="text-2xl font-bold text-green-600">
                                            {{ inventory.quantity_available.toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Available</div>
                                    </div>

                                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                                        <div class="text-2xl font-bold text-orange-600">
                                            {{ (inventory.quantity_on_hand - inventory.quantity_available).toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Reserved</div>
                                    </div>
                                </div>

                                <!-- Stock Status -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <AlertTriangle
                                            v-if="inventory.is_out_of_stock || inventory.quantity_on_hand <= (product.reorder_level || 0)"
                                            class="h-5 w-5"
                                            :class="stockStatus.color"
                                        />
                                        <Warehouse v-else class="h-5 w-5 text-green-600" />
                                        <div>
                                            <div class="font-medium" :class="stockStatus.color">{{ stockStatus.text }}</div>
                                            <div class="text-sm text-muted-foreground">Current inventory status</div>
                                        </div>
                                    </div>

                                    <!-- Stock Health Bar -->
                                    <div class="w-32">
                                        <div class="text-xs text-muted-foreground mb-1">Stock Health</div>
                                        <Progress
                                            :value="stockHealthPercentage"
                                            :class="stockHealthColor"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Stock Movements -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Recent Stock Movements</CardTitle>
                                    <CardDescription>Latest inventory transactions for this product</CardDescription>
                                </div>
                                <Button variant="ghost" size="sm" @click="router.visit('/inventory')">
                                    View All
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="recent_movements.length === 0" class="text-center py-8">
                                <BarChart3 class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <p class="text-gray-500 dark:text-gray-400">No recent movements</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    Stock movements will appear here as they occur.
                                </p>
                            </div>

                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Quantity</TableHead>
                                        <TableHead>Reason</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>By</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="movement in recent_movements.slice(0, 10)" :key="movement.id">
                                        <!-- Movement Type -->
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <component
                                                    :is="getMovementTypeIcon(movement.movement_type)"
                                                    :class="getMovementTypeColor(movement.movement_type)"
                                                    class="h-4 w-4"
                                                />
                                                <span class="font-medium">{{ formatMovementType(movement.movement_type) }}</span>
                                            </div>
                                        </TableCell>

                                        <!-- Quantity -->
                                        <TableCell>
                                            <span
                                                :class="movement.movement_type.includes('in') || movement.movement_type === 'restock' ? 'text-green-600' : 'text-red-600'"
                                                class="font-medium"
                                            >
                                                {{ movement.movement_type.includes('in') || movement.movement_type === 'restock' ? '+' : '-' }}{{ movement.quantity.toLocaleString() }}
                                            </span>
                                        </TableCell>

                                        <!-- Reason -->
                                        <TableCell>
                                            <span class="text-sm">{{ movement.reason || 'No reason provided' }}</span>
                                        </TableCell>

                                        <!-- Date -->
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ formatDate(movement.created_at) }}
                                        </TableCell>

                                        <!-- User -->
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ movement.user?.name || 'System' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <!-- Side Panel -->
                <div class="space-y-6">
                    <!-- Reorder Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Reorder Settings</CardTitle>
                            <CardDescription>Configure automatic reorder alerts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!isEditingReorderLevel" class="space-y-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ product.reorder_level || 0 }}</div>
                                    <div class="text-sm text-muted-foreground">Reorder Level</div>
                                </div>

                                <div v-if="inventory && product.reorder_level" class="text-center">
                                    <div
                                        :class="inventory.quantity_on_hand <= product.reorder_level ? 'text-red-600' : 'text-green-600'"
                                        class="text-sm font-medium"
                                    >
                                        {{ inventory.quantity_on_hand <= product.reorder_level ? 'Reorder needed!' : 'Stock level OK' }}
                                    </div>
                                </div>

                                <PermissionGuard permission="edit_products">
                                    <Button variant="outline" class="w-full" @click="handleEditReorderLevel">
                                        <Edit class="h-4 w-4 mr-2" />
                                        Update Level
                                    </Button>
                                </PermissionGuard>
                            </div>

                            <div v-else class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="reorder-level">Reorder Level</Label>
                                    <Input
                                        id="reorder-level"
                                        v-model.number="newReorderLevel"
                                        type="number"
                                        min="0"
                                        step="1"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Alert when stock falls to or below this level
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button size="sm" @click="handleSaveReorderLevel" :disabled="isSavingReorderLevel">
                                        <Save class="h-4 w-4 mr-2" />
                                        {{ isSavingReorderLevel ? 'Saving...' : 'Save' }}
                                    </Button>
                                    <Button variant="outline" size="sm" @click="handleCancelReorderEdit">
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription>Common inventory operations</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <PermissionGuard permission="edit_products">
                                <Button variant="outline" class="w-full justify-start" @click="handleAdjustStock" :disabled="!inventory">
                                    <Edit class="h-4 w-4 mr-2" />
                                    Stock Adjustment
                                </Button>
                            </PermissionGuard>

                            <Button variant="outline" class="w-full justify-start" @click="router.visit(`/inventory?product_id=${product.id}`)">
                                <BarChart3 class="h-4 w-4 mr-2" />
                                View All Movements
                            </Button>

                            <Button variant="outline" class="w-full justify-start" @click="router.visit('/inventory')">
                                <Warehouse class="h-4 w-4 mr-2" />
                                Inventory Dashboard
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <PermissionGuard permission="edit_products">
            <StockAdjustmentModal
                v-if="inventory"
                :open="showAdjustmentModal"
                :product="product"
                :current-stock="inventory.quantity_on_hand"
                @update:open="showAdjustmentModal = $event"
                @stock-adjusted="handleStockAdjusted"
            />
        </PermissionGuard>
    </AppLayout>
</template>