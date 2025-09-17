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

const handleSaveReorderLevel = async () => {
    if (newReorderLevel.value === props.product.reorder_level) {
        isEditingReorderLevel.value = false;
        return;
    }

    isSavingReorderLevel.value = true;

    try {
        await router.put(
            `/products/${props.product.id}/reorder-level`,
            {
                reorder_level: newReorderLevel.value,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    addAlert(`Reorder level updated from ${props.product.reorder_level || 0} to ${newReorderLevel.value}`, 'success', {
                        title: 'Reorder Level Updated',
                    });

                    isEditingReorderLevel.value = false;
                },
                onError: () => {
                    addAlert('Failed to update reorder level. Please try again.', 'destructive', { title: 'Update Failed' });
                },
                onFinish: () => {
                    isSavingReorderLevel.value = false;
                },
            },
        );
    } catch (error) {
        console.error('Error updating reorder level:', error);
        addAlert('Failed to update reorder level. Please try again.', 'destructive', { title: 'Update Failed' });
        isSavingReorderLevel.value = false;
    }
};

const handleCancelReorderEdit = () => {
    isEditingReorderLevel.value = false;
    newReorderLevel.value = props.product.reorder_level || 0;
};

const handleStockAdjusted = () => {
    showAdjustmentModal.value = false;
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
        case 'purchase':
        case 'return_from_customer':
        case 'transfer_in':
        case 'manufacturing':
        case 'initial_stock':
            return TrendingUp;
        case 'sale':
        case 'return_to_supplier':
        case 'transfer_out':
        case 'damaged':
        case 'expired':
        case 'theft':
            return TrendingDown;
        case 'adjustment':
            return BarChart3;
        default:
            return Package;
    }
};

const getMovementTypeColor = (type: string): string => {
    switch (type) {
        case 'purchase':
        case 'return_from_customer':
        case 'transfer_in':
        case 'manufacturing':
        case 'initial_stock':
            return 'text-green-600';
        case 'sale':
        case 'return_to_supplier':
        case 'transfer_out':
        case 'damaged':
        case 'expired':
        case 'theft':
            return 'text-red-600';
        case 'adjustment':
            return 'text-blue-600';
        case 'reservation':
        case 'release_reservation':
            return 'text-orange-600';
        default:
            return 'text-gray-600';
    }
};

const formatMovementType = (type: string): string => {
    return type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const getQuantityChangeDisplay = (movement: any): string => {
    // Calculate the change based on before/after quantities
    const change = movement.quantity_after - movement.quantity_before;
    const sign = change > 0 ? '+' : '';
    return `${sign}${change.toLocaleString()}`;
};
</script>

<template>
    <Head :title="`${product.name} - Inventory`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader :title="`${product.name} Inventory`" :description="`Track stock levels and movements for ${product.name}`">
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
                <div class="space-y-6 lg:col-span-2">
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

                            <div v-else-if="!inventory" class="py-8 text-center">
                                <Package class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <p class="text-gray-500 dark:text-gray-400">No inventory data available</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    Set up inventory tracking for this product to see stock levels.
                                </p>
                            </div>

                            <div v-else class="space-y-6">
                                <!-- Stock Metrics -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ inventory.quantity_on_hand.toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">On Hand</div>
                                    </div>

                                    <div class="rounded-lg bg-green-50 p-4 text-center">
                                        <div class="text-2xl font-bold text-green-600">
                                            {{ inventory.quantity_available.toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Available</div>
                                    </div>

                                    <div class="rounded-lg bg-orange-50 p-4 text-center">
                                        <div class="text-2xl font-bold text-orange-600">
                                            {{ (inventory.quantity_on_hand - inventory.quantity_available).toLocaleString() }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Reserved</div>
                                    </div>
                                </div>

                                <!-- Stock Status -->
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
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
                                        <div class="mb-1 text-xs text-muted-foreground">Stock Health</div>
                                        <Progress :value="stockHealthPercentage" :class="stockHealthColor" />
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
                                <Button variant="ghost" size="sm" @click="router.visit(`/inventory/movements?product_id=${props.product.id}`)"> View All </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="recent_movements.length === 0" class="py-8 text-center">
                                <BarChart3 class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <p class="text-gray-500 dark:text-gray-400">No recent movements</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Stock movements will appear here as they occur.</p>
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
                                                :class="getMovementTypeColor(movement.movement_type)"
                                                class="font-medium"
                                            >
                                                {{ getQuantityChangeDisplay(movement) }}
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
                                        <Edit class="mr-2 h-4 w-4" />
                                        Update Level
                                    </Button>
                                </PermissionGuard>
                            </div>

                            <div v-else class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="reorder-level">Reorder Level</Label>
                                    <Input id="reorder-level" v-model.number="newReorderLevel" type="number" min="0" step="1" />
                                    <p class="text-xs text-muted-foreground">Alert when stock falls to or below this level</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Button size="sm" @click="handleSaveReorderLevel" :disabled="isSavingReorderLevel">
                                        <Save class="mr-2 h-4 w-4" />
                                        {{ isSavingReorderLevel ? 'Saving...' : 'Save' }}
                                    </Button>
                                    <Button variant="outline" size="sm" @click="handleCancelReorderEdit"> Cancel </Button>
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
                                    <Edit class="mr-2 h-4 w-4" />
                                    Stock Adjustment
                                </Button>
                            </PermissionGuard>

                            <Button variant="outline" class="w-full justify-start" @click="router.visit(`/inventory/movements?product_id=${props.product.id}`)">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                View All Movements
                            </Button>

                            <Button variant="outline" class="w-full justify-start" @click="router.visit('/inventory')">
                                <Warehouse class="mr-2 h-4 w-4" />
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
