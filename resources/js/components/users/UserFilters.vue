<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { Role, School, UserFilters, UserTypeOption } from '@/types';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    filters: UserFilters;
    userTypes: UserTypeOption[];
    schools: School[];
    roles: Role[];
}

interface Emits {
    (e: 'update:filters', filters: UserFilters): void;
    (e: 'clear'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties for v-model
const searchModel = computed({
    get: () => props.filters.search || '',
    set: (value: string) => emit('update:filters', { ...props.filters, search: value }),
});

const userTypeModel = computed({
    get: () => props.filters.user_type || '',
    set: (value: string) => emit('update:filters', { ...props.filters, user_type: value || undefined }),
});

const schoolIdModel = computed({
    get: () => props.filters.school_id?.toString() || '',
    set: (value: string) => emit('update:filters', { ...props.filters, school_id: value ? parseInt(value) : undefined }),
});

const isActiveModel = computed({
    get: () => props.filters.is_active || '',
    set: (value: string) => emit('update:filters', { ...props.filters, is_active: value }),
});

const roleModel = computed({
    get: () => props.filters.role || '',
    set: (value: string) => emit('update:filters', { ...props.filters, role: value }),
});

// Check if any filters are active
const hasFilters = computed(() => {
    return props.filters.search || props.filters.user_type || props.filters.school_id || props.filters.is_active || props.filters.role;
});

function clearFilters() {
    emit('clear');
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Search and filter users</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-6">
                <!-- Search -->
                <div class="relative md:col-span-2">
                    <Search class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchModel" placeholder="Search by name or email..." class="pl-8" />
                </div>

                <!-- User Type Filter -->
                <select
                    v-model="userTypeModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Types</option>
                    <option v-for="type in props.userTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>

                <!-- School Filter -->
                <select
                    v-model="schoolIdModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Schools</option>
                    <option v-for="school in props.schools" :key="school.id" :value="school.id.toString()">
                        {{ school.school_name }}
                    </option>
                </select>

                <!-- Role Filter -->
                <select
                    v-model="roleModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Roles</option>
                    <option v-for="role in props.roles" :key="role.id" :value="role.name">
                        {{ role.display_name || role.name }}
                    </option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="isActiveModel"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <!-- Clear Filters -->
            <div v-if="hasFilters" class="mt-4">
                <Button variant="outline" size="sm" @click="clearFilters"> Clear Filters </Button>
            </div>
        </CardContent>
    </Card>
</template>
