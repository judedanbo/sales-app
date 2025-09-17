<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCurrency } from '@/composables/useCurrency';
import { type PaginatedData, type StockMovement, type StockMovementFilters } from '@/types';
import { ArrowDown, ArrowUp, Calendar, FileText, Package, Search, TrendingUp, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    movements: PaginatedData<StockMovement>;
    filters: StockMovementFilters;
    isLoading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:filters': [filters: StockMovementFilters];
    sort: [column: string];
    'page-change': [page: number];
    'view-details': [movement: StockMovement];
}>();

const localFilters = ref<StockMovementFilters>({ ...props.filters });

const movementTypes = [
    { value: 'initial_stock', label: 'Initial Stock', icon: Package, color: 'blue' },
    { value: 'purchase', label: 'Purchase', icon: ArrowUp, color: 'green' },
    { value: 'sale', label: 'Sale', icon: ArrowDown, color: 'orange' },
    { value: 'return_from_customer', label: 'Customer Return', icon: ArrowUp, color: 'green' },
    { value: 'return_to_supplier', label: 'Supplier Return', icon: ArrowDown, color: 'red' },
    { value: 'adjustment', label: 'Stock Adjustment', icon: Package, color: 'blue' },
    { value: 'transfer_in', label: 'Transfer In', icon: ArrowUp, color: 'purple' },
    { value: 'transfer_out', label: 'Transfer Out', icon: ArrowDown, color: 'purple' },
    { value: 'damaged', label: 'Damaged', icon: ArrowDown, color: 'red' },
    { value: 'expired', label: 'Expired', icon: ArrowDown, color: 'red' },
    { value: 'theft', label: 'Theft/Loss', icon: ArrowDown, color: 'red' },
    { value: 'manufacturing', label: 'Manufacturing', icon: Package, color: 'blue' },
    { value: 'reservation', label: 'Reserved', icon: ArrowDown, color: 'yellow' },
    { value: 'release_reservation', label: 'Released Reservation', icon: ArrowUp, color: 'green' },
];

const referenceTypes = [
    { value: 'purchase_order', label: 'Purchase Order' },
    { value: 'sales_order', label: 'Sales Order' },
    { value: 'transfer_order', label: 'Transfer Order' },
    { value: 'adjustment', label: 'Stock Adjustment' },
    { value: 'return', label: 'Return' },
];

watch(
    () => localFilters.value,
    (newFilters) => {
        emit('update:filters', newFilters);
    },
    { deep: true },
);

const getMovementTypeDetails = (type: string) => {
    return (
        movementTypes.find((mt) => mt.value === type) || {
            value: type,
            label: type.charAt(0).toUpperCase() + type.slice(1),
            icon: Package,
            color: 'gray',
        }
    );
};

const getMovementIcon = (type: string) => {
    const details = getMovementTypeDetails(type);
    return details.icon;
};

const getMovementColor = (type: string) => {
    const details = getMovementTypeDetails(type);
    switch (details.color) {
        case 'green':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'red':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        case 'blue':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
        case 'purple':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400';
        case 'orange':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400';
        case 'yellow':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};
const { formatCurrency } = useCurrency();
const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();
const formatDateTime = (dateString: string) => new Date(dateString).toLocaleString();

const clearFilters = () => {
    localFilters.value = {
        search: '',
        product_id: '',
        variant_id: '',
        movement_type: '',
        date_from: '',
        date_to: '',
        created_by: '',
        reference_type: '',
        sort_by: 'movement_date',
        sort_direction: 'desc',
    };
};

const totalMovements = computed(() => props.movements.total || 0);
const hasFilters = computed(() => {
    return Object.values(localFilters.value).some((value) => value && value !== '');
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <TrendingUp class="h-5 w-5" />
                Stock Movements
            </CardTitle>
            <CardDescription> Track all inventory transactions and stock changes. </CardDescription>
        </CardHeader>
        <CardContent>
            <!-- Filters -->
            <div class="mb-6 space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Search</label>
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                            <Input v-model="localFilters.search" placeholder="Search products, SKU, or reference..." class="pl-10" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Movement Type</label>
                        <Select v-model="localFilters.movement_type">
                            <SelectTrigger>
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Types</SelectItem>
                                <SelectItem v-for="type in movementTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">From Date</label>
                        <Input v-model="localFilters.date_from" type="date" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">To Date</label>
                        <Input v-model="localFilters.date_to" type="date" />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ totalMovements }} movements found</div>
                    <div class="flex gap-2">
                        <Button v-if="hasFilters" variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="space-y-4">
                <div v-for="i in 10" :key="i" class="flex items-center space-x-4">
                    <Skeleton class="h-8 w-8 rounded" />
                    <div class="flex-1 space-y-2">
                        <Skeleton class="h-4 w-[250px]" />
                        <Skeleton class="h-4 w-[200px]" />
                    </div>
                    <Skeleton class="h-8 w-[100px]" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="movements.data.length === 0" class="py-8 text-center">
                <TrendingUp class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">No movements found</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ hasFilters ? 'Try adjusting your filters to see more results.' : 'Stock movements will appear here as inventory changes.' }}
                </p>
            </div>

            <!-- Movements Table -->
            <div v-else class="space-y-4">
                <div class="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="cursor-pointer" @click="$emit('sort', 'movement_date')">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        Date
                                    </div>
                                </TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead class="cursor-pointer" @click="$emit('sort', 'product.name')"> Product </TableHead>
                                <TableHead class="text-right">Quantity</TableHead>
                                <TableHead class="text-right">Cost</TableHead>
                                <TableHead>Reference</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ formatDate(movement.movement_date) }}</div>
                                        <div class="text-sm text-gray-500">{{ formatDateTime(movement.created_at) }}</div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 items-center justify-center rounded-full',
                                                getMovementColor(movement.movement_type),
                                            ]"
                                        >
                                            <component :is="getMovementIcon(movement.movement_type)" class="h-4 w-4" />
                                        </div>
                                        <Badge :class="getMovementColor(movement.movement_type)" variant="secondary">
                                            {{ getMovementTypeDetails(movement.movement_type).label }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ movement.product?.name || 'Unknown Product' }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ movement.product?.sku }}{{ movement.variant ? ` - ${movement.variant.display_name}` : '' }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div
                                        :class="[
                                            'font-medium',
                                            movement.quantity > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400',
                                        ]"
                                    >
                                        {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
                                        <div class="text-sm text-gray-500">from: {{ movement.quantity_before }} to {{ movement.quantity_after }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div v-if="movement.total_cost" class="space-y-1">
                                        <div class="font-medium">{{ formatCurrency(movement.total_cost) }}</div>
                                        <div v-if="movement.unit_cost" class="text-sm text-gray-500">
                                            {{ formatCurrency(movement.unit_cost) }}/unit
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell>
                                    <div v-if="movement.reference_id || movement.reference_type" class="space-y-1">
                                        <div v-if="movement.reference_id" class="font-medium">
                                            {{ movement.reference_id }}
                                        </div>
                                        <div v-if="movement.reference_type" class="text-sm text-gray-500">
                                            {{ referenceTypes.find((rt) => rt.value === movement.reference_type)?.label || movement.reference_type }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm">{{ movement.user?.name || 'System' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button size="sm" variant="outline" @click="$emit('view-details', movement)">
                                        <FileText class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="movements.last_page > 1" class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="movements.current_page === 1"
                            @click="$emit('page-change', movements.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm"> Page {{ movements.current_page }} of {{ movements.last_page }} </span>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="movements.current_page === movements.last_page"
                            @click="$emit('page-change', movements.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
