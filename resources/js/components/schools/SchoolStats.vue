<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { School } from '@/types';
import {
    Building2,
    GraduationCap,
    School as SchoolIcon,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    schools: School[];
    totalCount?: number;
}

const props = defineProps<Props>();

// Computed stats from the provided schools
const stats = computed(() => ({
    totalSchools: props.totalCount ?? props.schools.length,
    activeSchools: props.schools.filter(s => s.is_active).length,
    totalStudents: props.schools.reduce((sum, s) => sum + (s.total_students || 0), 0),
    totalTeachers: props.schools.reduce((sum, s) => sum + (s.total_teachers || 0), 0),
}));
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Schools</CardTitle>
                <Building2 class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.totalSchools }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Active Schools</CardTitle>
                <SchoolIcon class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.activeSchools }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                <Users class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.totalStudents.toLocaleString() }}</div>
            </CardContent>
        </Card>
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Teachers</CardTitle>
                <GraduationCap class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.totalTeachers.toLocaleString() }}</div>
            </CardContent>
        </Card>
    </div>
</template>