<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { SchoolFilters } from '@/types';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    filters: SchoolFilters;
    schoolTypes: Array<{ value: string; label: string }>;
    boardAffiliations: Array<{ value: string; label: string }>;
    schoolStatuses: Array<{ value: string; label: string }>;
}

interface Emits {
    (e: 'update:filters', filters: SchoolFilters): void;
    (e: 'clear'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties for v-model
const searchModel = computed({
    get: () => props.filters.search,
    set: (value: string) => emit('update:filters', { search: value }),
});

const schoolTypeModel = computed({
    get: () => props.filters.school_type,
    set: (value: string) => emit('update:filters', { school_type: value }),
});

const boardAffiliationModel = computed({
    get: () => props.filters.board_affiliation,
    set: (value: string) => emit('update:filters', { board_affiliation: value }),
});

const statusModel = computed({
    get: () => props.filters.status,
    set: (value: string) => emit('update:filters', { status: value }),
});

// Check if any filters are active
const hasFilters = computed(() => {
    return props.filters.search || props.filters.school_type || props.filters.status || props.filters.board_affiliation;
});

function clearFilters() {
    emit('clear');
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Search and filter schools</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-5">
                <!-- Search -->
                <div class="relative md:col-span-2">
                    <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchModel" placeholder="Search by name or code..." class="pl-8" />
                </div>

                <!-- School Type Filter -->
                <select
                    v-model="schoolTypeModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Types</option>
                    <option v-for="type in props.schoolTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>

                <!-- Board Filter -->
                <select
                    v-model="boardAffiliationModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Boards</option>
                    <option v-for="board in props.boardAffiliations" :key="board.value" :value="board.value">
                        {{ board.label }}
                    </option>
                </select>

                <!-- Status Filter -->

                <select
                    v-model="statusModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Status</option>
                    <option v-for="status in props.schoolStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div v-if="hasFilters" class="mt-4">
                <Button variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
            </div>
        </CardContent>
    </Card>
</template>
