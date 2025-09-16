<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type ProductVariant } from '@/types';
import { Edit, MoreHorizontal, Package, Star, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    variants: ProductVariant[];
    isLoading?: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    edit: [variant: ProductVariant];
    delete: [variant: ProductVariant];
    setDefault: [variant: ProductVariant];
}>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        case 'inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
        case 'discontinued':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};

const getStockStatusColor = (variant: ProductVariant) => {
    if (variant.current_stock === 0) {
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
    } else if (variant.is_low_stock) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
    } else {
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
    }
};

const getStockStatusText = (variant: ProductVariant) => {
    if (variant.current_stock === 0) {
        return 'Out of Stock';
    } else if (variant.is_low_stock) {
        return 'Low Stock';
    } else {
        return 'In Stock';
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Package class="h-5 w-5" />
                Product Variants
            </CardTitle>
            <CardDescription>
                Manage different variations of this product including size, color, and material options.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="flex items-center space-x-4">
                    <Skeleton class="h-12 w-12 rounded" />
                    <div class="space-y-2">
                        <Skeleton class="h-4 w-[200px]" />
                        <Skeleton class="h-4 w-[150px]" />
                    </div>
                </div>
            </div>

            <div v-else-if="variants.length === 0" class="text-center py-8">
                <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No variants found</h3>
                <p class="text-gray-500 dark:text-gray-400">
                    This product doesn't have any variants yet. Create variants to offer different options.
                </p>
            </div>

            <div v-else class="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Variant</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Attributes</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Stock</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="variant in variants" :key="variant.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <TableCell>
                                <div class="flex items-center space-x-3">
                                    <div v-if="variant.image_url" class="h-10 w-10 flex-shrink-0">
                                        <img
                                            :src="variant.image_url"
                                            :alt="variant.display_name"
                                            class="h-10 w-10 rounded object-cover"
                                        />
                                    </div>
                                    <div v-else class="h-10 w-10 flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center">
                                        <Package class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ variant.display_name || variant.name || 'Unnamed Variant' }}</span>
                                            <Star v-if="variant.is_default" class="h-4 w-4 text-yellow-500 fill-current" title="Default variant" />
                                        </div>
                                        <div v-if="variant.barcode" class="text-sm text-gray-500">
                                            Barcode: {{ variant.barcode }}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                    {{ variant.sku }}
                                </code>
                            </TableCell>
                            <TableCell>
                                <div class="space-y-1">
                                    <div v-if="variant.size" class="text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Size:</span> {{ variant.size }}
                                    </div>
                                    <div v-if="variant.color" class="text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Color:</span> {{ variant.color }}
                                    </div>
                                    <div v-if="variant.material" class="text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Material:</span> {{ variant.material }}
                                    </div>
                                    <div v-if="!variant.size && !variant.color && !variant.material" class="text-sm text-gray-400">
                                        No specific attributes
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="space-y-1">
                                    <div class="font-medium">{{ variant.formatted_price || 'GH₵ 0.00' }}</div>
                                    <div v-if="variant.cost_price" class="text-sm text-gray-500">
                                        Cost: GH₵ {{ variant.cost_price.toFixed(2) }}
                                    </div>
                                    <div v-if="variant.profit_margin" class="text-sm text-green-600">
                                        {{ variant.profit_margin.toFixed(1) }}% margin
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="space-y-2">
                                    <div class="font-medium">{{ variant.current_stock || 0 }} units</div>
                                    <Badge :class="getStockStatusColor(variant)" variant="secondary">
                                        {{ getStockStatusText(variant) }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusColor(variant.status)" variant="secondary">
                                    {{ variant.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <Button
                                        v-if="!variant.is_default"
                                        size="sm"
                                        variant="outline"
                                        @click="$emit('setDefault', variant)"
                                        title="Set as default variant"
                                    >
                                        <Star class="h-4 w-4" />
                                    </Button>
                                    <Button size="sm" variant="outline" @click="$emit('edit', variant)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button size="sm" variant="outline" @click="$emit('delete', variant)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>