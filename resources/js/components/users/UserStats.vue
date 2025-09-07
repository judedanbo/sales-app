<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { UserStatistics } from '@/types';
import { Shield, UserCheck, UserMinus, Users } from 'lucide-vue-next';

interface Props {
    statistics: UserStatistics;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                <Users class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ statistics.total.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Active Users</CardTitle>
                <UserCheck class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ statistics.active.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total > 0 ? Math.round((statistics.active / statistics.total) * 100) : 0 }}% of total
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Inactive Users</CardTitle>
                <UserMinus class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <!-- <div class="text-2xl font-bold text-orange-600">{{ statistics.inactive.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ statistics.total_users > 0 ? Math.round((statistics.inactive / statistics.total) * 100) : 0 }}% of total
                </p> -->
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Admin Users</CardTitle>
                <Shield class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-blue-600">
                    {{
                        (statistics.by_type.admin?.count || 0) +
                        (statistics.by_type.system_admin?.count || 0) +
                        (statistics.by_type.school_admin?.count || 0)
                    }}
                </div>
                <p class="text-xs text-muted-foreground">System & school admins</p>
            </CardContent>
        </Card>
    </div>

    <!-- Additional Stats Row -->
    <div class="mt-4 grid gap-4 md:grid-cols-3 lg:grid-cols-5">
        <Card v-for="(typeData, userType) in statistics.by_type" :key="userType" class="col-span-1">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">{{ typeData.label }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-xl font-bold">{{ typeData.count }}</div>
                <p class="text-xs text-muted-foreground">{{ statistics.total > 0 ? Math.round((typeData.count / statistics.total) * 100) : 0 }}%</p>
            </CardContent>
        </Card>
    </div>
</template>
