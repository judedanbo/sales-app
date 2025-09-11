<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { AuditFilters } from '@/types';
import { Filter, Search } from 'lucide-vue-next';
import { computed, defineEmits, defineModel } from 'vue';

interface Props {
    filters: AuditFilters;
    filterOptions: {
        models: Array<{ value: string; label: string }>;
        events: string[];
    };
    totalResults?: number;
}

defineProps<Props>();

const emit = defineEmits<{
    clearFilters: [];
}>();

const localFilters = defineModel<AuditFilters>('filters', { required: true });

const hasActiveFilters = computed(() => {
    return Object.values(localFilters.value).some(
        (value) =>
            value !== '' &&
            value !== null &&
            value !== undefined &&
            !(value === 'created_at' && localFilters.value.sort_by === value) &&
            !(value === 'desc' && localFilters.value.sort_direction === value),
    );
});

function clearFilters() {
    emit('clearFilters');
}
</script>

<template>
    <Card data-testid="audit-filters">
        <CardHeader>
            <CardTitle class="text-base">Filters</CardTitle>
            <CardDescription>Filter audit records by various criteria</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <!-- Search -->
                <div class="relative">
                    <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="localFilters.search" placeholder="Search audits..." class="pl-8" data-testid="search-input" />
                </div>

                <!-- Model Type -->
                <Select v-model="localFilters.auditable_type">
                    <SelectTrigger>
                        <SelectValue placeholder="All Models" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Models</SelectItem>
                        <SelectItem v-for="model in filterOptions.models" :key="model.value" :value="model.value">
                            {{ model.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Event Type -->
                <Select v-model="localFilters.event" data-testid="event-filter">
                    <SelectTrigger>
                        <SelectValue placeholder="All Events" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Events</SelectItem>
                        <SelectItem v-for="event in filterOptions.events" :key="event" :value="event">
                            {{ event.charAt(0).toUpperCase() + event.slice(1) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- From Date -->
                <Input v-model="localFilters.from_date" type="date" placeholder="From Date" />

                <!-- To Date -->
                <Input v-model="localFilters.to_date" type="date" placeholder="To Date" />
            </div>

            <div v-if="hasActiveFilters" class="mt-4 flex items-center gap-2">
                <Button variant="outline" size="sm" @click="clearFilters">
                    <Filter class="mr-2 h-4 w-4" />
                    Clear Filters
                </Button>
                <span v-if="totalResults !== undefined" class="text-sm text-muted-foreground">
                    {{ totalResults }} result{{ totalResults !== 1 ? 's' : '' }} found
                </span>
            </div>
        </CardContent>
    </Card>
</template>
