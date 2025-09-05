<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { PermissionFilters } from '@/types';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    filters: PermissionFilters;
    guardNames: string[];
    categories: Array<{ value: string; label: string; display_name: string }>;
}

interface Emits {
    (e: 'update:filters', filters: PermissionFilters): void;
    (e: 'clear'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties for v-model
const searchModel = computed({
    get: () => props.filters.search || '',
    set: (value: string) => emit('update:filters', { ...props.filters, search: value }),
});

const guardNameModel = computed({
    get: () => props.filters.guard_name || '',
    set: (value: string) => emit('update:filters', { ...props.filters, guard_name: value }),
});

const categoryModel = computed({
    get: () => props.filters.category || '',
    set: (value: string) => emit('update:filters', { ...props.filters, category: value }),
});

const hasRolesModel = computed({
    get: () => props.filters.has_roles || '',
    set: (value: string) => emit('update:filters', { ...props.filters, has_roles: value }),
});

// Check if any filters are active
const hasFilters = computed(() => {
    return props.filters.search || props.filters.guard_name || props.filters.category || props.filters.has_roles;
});

function clearFilters() {
    emit('clear');
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Search and filter permissions</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-5">
                <!-- Search -->
                <div class="relative md:col-span-2">
                    <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchModel" placeholder="Search by name or display name..." class="pl-8" />
                </div>

                <!-- Guard Name Filter -->
                <select
                    v-model="guardNameModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Guards</option>
                    <option v-for="guardName in props.guardNames" :key="guardName" :value="guardName">
                        {{ guardName }}
                    </option>
                </select>

                <!-- Category Filter -->
                <select
                    v-model="categoryModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Categories</option>
                    <option v-for="category in props.categories" :key="category.value" :value="category.value">
                        {{ category.display_name }}
                    </option>
                </select>

                <!-- Has Roles Filter -->
                <select
                    v-model="hasRolesModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Permissions</option>
                    <option value="1">With Roles</option>
                    <option value="0">Without Roles</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div v-if="hasFilters" class="mt-4">
                <Button variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
            </div>
        </CardContent>
    </Card>
</template>
