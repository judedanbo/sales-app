<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PermissionStatistics } from '@/types';
import { Key, KeyRound, Layers, TrendingUp } from 'lucide-vue-next';

interface Props {
    statistics: PermissionStatistics;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Permissions</CardTitle>
                <Key class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ statistics.total_permissions.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">With Roles</CardTitle>
                <KeyRound class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ statistics.permissions_with_roles.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total_permissions > 0 ? Math.round((statistics.permissions_with_roles / statistics.total_permissions) * 100) : 0 }}%
                    assigned
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Without Roles</CardTitle>
                <Key class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-orange-600">{{ statistics.permissions_without_roles.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{
                        statistics.total_permissions > 0
                            ? Math.round((statistics.permissions_without_roles / statistics.total_permissions) * 100)
                            : 0
                    }}% unassigned
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Usage Rate</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-blue-600">{{ statistics.usage_percentage.toFixed(1) }}%</div>
                <p class="text-xs text-muted-foreground">Overall permission usage</p>
            </CardContent>
        </Card>
    </div>

    <!-- Categories Breakdown -->
    <div v-if="statistics.permissions_by_category?.length" class="mt-4">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Permissions by Category</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="category in statistics.permissions_by_category"
                        :key="category.category"
                        class="flex items-center justify-between rounded-lg bg-muted/30 p-3"
                    >
                        <div>
                            <div class="font-medium">{{ category.display_name }}</div>
                            <div class="text-sm text-muted-foreground">{{ category.count }} permissions</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium">{{ category.percentage?.toFixed(1) || 0 }}%</div>
                            <Layers class="ml-auto h-4 w-4 text-muted-foreground" />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Most Used Permissions -->
    <div v-if="statistics.most_used_permissions?.length" class="mt-4">
        <Card>
            <CardHeader>
                <CardTitle class="text-lg">Most Used Permissions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div
                        v-for="permission in statistics.most_used_permissions.slice(0, 8)"
                        :key="permission.name"
                        class="flex items-center justify-between rounded-lg bg-muted/30 p-2"
                    >
                        <div class="flex items-center gap-2">
                            <Key class="h-4 w-4 text-muted-foreground" />
                            <div>
                                <div class="text-sm font-medium">{{ permission.display_name || permission.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ permission.category }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium">{{ permission.roles_count }} roles</div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
