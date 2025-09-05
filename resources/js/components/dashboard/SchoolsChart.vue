<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/schools';
import { Link } from '@inertiajs/vue3';
import { BarChart3, ExternalLink, PieChart } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    charts: {
        schools_by_type: Record<string, number>;
        schools_by_board: Record<string, number>;
    };
}

const props = defineProps<Props>();

const schoolTypeLabels: Record<string, string> = {
    primary: 'Primary',
    secondary: 'Secondary',
    higher_secondary: 'Higher Secondary',
    k12: 'K-12',
};

const boardLabels: Record<string, string> = {
    cbse: 'CBSE',
    icse: 'ICSE',
    state_board: 'State Board',
    ib: 'International Baccalaureate',
    cambridge: 'Cambridge',
};

const schoolTypeData = computed(() =>
    Object.entries(props.charts.schools_by_type).map(([key, value]) => ({
        label: schoolTypeLabels[key] || key,
        value,
        percentage: Math.round((value / Object.values(props.charts.schools_by_type).reduce((a, b) => a + b, 0)) * 100),
    })),
);

const boardData = computed(() =>
    Object.entries(props.charts.schools_by_board).map(([key, value]) => ({
        label: boardLabels[key] || key,
        value,
        percentage: Math.round((value / Object.values(props.charts.schools_by_board).reduce((a, b) => a + b, 0)) * 100),
    })),
);

const getBarWidth = (percentage: number) => `${percentage}%`;

const colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500'];
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <!-- Schools by Type -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="h-4 w-4" />
                        Schools by Type
                    </CardTitle>
                    <CardDescription>Distribution across education levels</CardDescription>
                </div>
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ExternalLink class="mr-1 h-3 w-3" />
                        View All
                    </Button>
                </Link>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-for="(item, index) in schoolTypeData" :key="item.label" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ item.label }}</span>
                        <span class="text-muted-foreground">{{ item.value }} ({{ item.percentage }}%)</span>
                    </div>
                    <div class="relative h-2 w-full overflow-hidden rounded-full bg-secondary">
                        <div
                            :class="`h-full transition-all duration-500 ease-out ${colors[index % colors.length]}`"
                            :style="{ width: getBarWidth(item.percentage) }"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Schools by Board -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <PieChart class="h-4 w-4" />
                        Schools by Board
                    </CardTitle>
                    <CardDescription>Educational board affiliations</CardDescription>
                </div>
                <Link :href="index().url + '?board_affiliation=cbse'">
                    <Button variant="ghost" size="sm">
                        <ExternalLink class="mr-1 h-3 w-3" />
                        Filter
                    </Button>
                </Link>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-for="(item, index) in boardData" :key="item.label" class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ item.label }}</span>
                        <span class="text-muted-foreground">{{ item.value }} ({{ item.percentage }}%)</span>
                    </div>
                    <div class="relative h-2 w-full overflow-hidden rounded-full bg-secondary">
                        <div
                            :class="`h-full transition-all duration-500 ease-out ${colors[index % colors.length]}`"
                            :style="{ width: getBarWidth(item.percentage) }"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
