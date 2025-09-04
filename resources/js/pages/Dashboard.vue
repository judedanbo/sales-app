<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DashboardStats from '@/components/dashboard/DashboardStats.vue';
import SchoolsChart from '@/components/dashboard/SchoolsChart.vue';
import RecentSchools from '@/components/dashboard/RecentSchools.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type School } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    stats: {
        total_schools: number;
        active_schools: number;
        inactive_schools: number;
        total_students: number;
        total_teachers: number;
        student_teacher_ratio: number;
        schools_needing_attention: number;
        schools_with_contacts: number;
        schools_with_addresses: number;
        data_completeness_percentage: number;
    };
    charts: {
        schools_by_type: Record<string, number>;
        schools_by_board: Record<string, number>;
    };
    recent_schools: School[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <!-- Stats Overview -->
            <DashboardStats :stats="stats" />

            <!-- Charts Section -->
            <SchoolsChart :charts="charts" />

            <!-- Recent Schools -->
            <RecentSchools :recent-schools="recent_schools" />
        </div>
    </AppLayout>
</template>
