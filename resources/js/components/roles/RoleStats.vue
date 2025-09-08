<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { RoleStatistics } from '@/types';
import { Key, Shield, ShieldCheck, ShieldX } from 'lucide-vue-next';

interface Props {
    statistics: RoleStatistics;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Roles</CardTitle>
                <Shield class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ statistics.total.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">With Users</CardTitle>
                <ShieldCheck class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ statistics.with_users.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total > 0 ? Math.round((statistics.with_users / statistics.total) * 100) : 0 }}% have users
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Without Users</CardTitle>
                <ShieldX class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-orange-600">{{ statistics.without_users.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total > 0 ? Math.round((statistics.without_users / statistics.total) * 100) : 0 }}% unused
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">With Permissions</CardTitle>
                <Key class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-blue-600">{{ statistics.with_permissions.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total > 0 ? Math.round((statistics.with_permissions / statistics.total) * 100) : 0 }}% have permissions
                </p>
            </CardContent>
        </Card>
    </div>

    <!-- Popular Roles -->
    <div v-if="statistics.popular_roles?.length" class="mt-4">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Most Used Roles</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="role in statistics.popular_roles.slice(0, 6)"
                        :key="role.name"
                        class="flex items-center justify-between rounded-lg bg-muted/30 p-3"
                    >
                        <div>
                            <div class="font-medium">{{ role.display_name || role.name }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ role.users_count }} users • {{ role.guard_name }}
                            </div>
                        </div>
                        <Shield class="h-4 w-4 text-muted-foreground" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Roles by Guard -->
    <div v-if="Object.keys(statistics.by_guard).length > 1" class="mt-4">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Roles by Guard</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-4">
                    <div v-for="(count, guardName) in statistics.by_guard" :key="guardName" class="rounded-lg bg-muted/30 p-3 text-center">
                        <div class="text-2xl font-bold">{{ count }}</div>
                        <div class="text-sm text-muted-foreground capitalize">{{ guardName }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
