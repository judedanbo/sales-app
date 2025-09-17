<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { useCurrency } from '@/composables/useCurrency';
import { AlertTriangle, Building2, Database, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';

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
}

const props = defineProps<Props>();

const activePercentage = computed(() =>
    props.stats.total_schools > 0 ? Math.round((props.stats.active_schools / props.stats.total_schools) * 100) : 0,
);

const { formatNumber } = useCurrency();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <!-- Total Schools -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Schools</CardTitle>
                <Building2 class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatNumber(stats.total_schools) }}</div>
                <div class="flex items-center text-xs text-muted-foreground">
                    <TrendingUp class="mr-1 h-3 w-3" />
                    {{ stats.active_schools }} active ({{ activePercentage }}%)
                </div>
            </CardContent>
        </Card>

        <!-- Students & Teachers -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Students & Teachers</CardTitle>
                <Users class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatNumber(stats.total_students) }}</div>
                <div class="text-xs text-muted-foreground">
                    {{ formatNumber(stats.total_teachers) }} teachers • {{ stats.student_teacher_ratio }}:1 ratio
                </div>
            </CardContent>
        </Card>

        <!-- Data Completeness -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Data Completeness</CardTitle>
                <Database class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.data_completeness_percentage }}%</div>
                <Progress :value="stats.data_completeness_percentage" class="mt-2" />
                <div class="mt-2 text-xs text-muted-foreground">
                    {{ stats.schools_with_contacts }} with contacts • {{ stats.schools_with_addresses }} with addresses
                </div>
            </CardContent>
        </Card>

        <!-- Schools Needing Attention -->
        <Card v-if="stats.schools_needing_attention > 0" class="border-orange-200 dark:border-orange-800">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-orange-700 dark:text-orange-300"> Needs Attention </CardTitle>
                <AlertTriangle class="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ stats.schools_needing_attention }}
                </div>
                <div class="text-xs text-muted-foreground">Schools with missing data or inactive</div>
            </CardContent>
        </Card>
    </div>
</template>
