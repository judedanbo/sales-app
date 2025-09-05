<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { RoleFilters } from '@/types';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    filters: RoleFilters;
    guardNames: string[];
}

interface Emits {
    (e: 'update:filters', filters: RoleFilters): void;
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

const hasUsersModel = computed({
    get: () => props.filters.has_users || '',
    set: (value: string) => emit('update:filters', { ...props.filters, has_users: value }),
});

// Check if any filters are active
const hasFilters = computed(() => {
    return props.filters.search || props.filters.guard_name || props.filters.has_users;
});

function clearFilters() {
    emit('clear');
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Search and filter roles</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-4">
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

                <!-- Has Users Filter -->
                <select
                    v-model="hasUsersModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Roles</option>
                    <option value="1">With Users</option>
                    <option value="0">Without Users</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div v-if="hasFilters" class="mt-4">
                <Button variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
            </div>
        </CardContent>
    </Card>
</template>
