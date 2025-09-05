<script setup lang="ts">
import SchoolAcademicYearModal from '@/components/schools/SchoolAcademicYearModal.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AcademicYear, School } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Calendar,
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Edit,
    MoreHorizontal,
    Plus,
    School as SchoolIcon,
    Star,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    school: School;
    academicYears: AcademicYear[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Schools', href: '/schools' },
    { label: props.school.school_name, href: `/schools/${props.school.id}` },
    { label: 'Academic Years' },
];

// Modal state
const showCreateModal = ref(false);

// Sort academic years by current status first, then by start date (newest first)
const sortedAcademicYears = computed(() => {
    return [...props.academicYears].sort((a, b) => {
        if (a.is_current !== b.is_current) {
            return b.is_current ? 1 : -1; // Current year first
        }
        return new Date(b.start_date).getTime() - new Date(a.start_date).getTime();
    });
});

const currentAcademicYear = computed(() => {
    return props.academicYears.find((year) => year.is_current);
});

const handleAcademicYearCreated = () => {
    showCreateModal.value = false;
    router.reload({ only: ['academicYears'] });
};

const handleEdit = (academicYear: AcademicYear) => {
    router.visit(`/schools/${props.school.id}/academic-years/${academicYear.id}/edit`);
};

const handleDelete = (academicYear: AcademicYear) => {
    if (confirm(`Are you sure you want to delete academic year "${academicYear.year_name}"?`)) {
        router.delete(`/schools/${props.school.id}/academic-years/${academicYear.id}`, {
            preserveScroll: true,
        });
    }
};

const handleSetCurrent = (academicYear: AcademicYear) => {
    if (confirm(`Set "${academicYear.year_name}" as the current academic year?`)) {
        router.post(
            `/schools/${props.school.id}/academic-years/${academicYear.id}/set-current`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getDateRange = (academicYear: AcademicYear): string => {
    return `${formatDate(academicYear.start_date)} - ${formatDate(academicYear.end_date)}`;
};

const getYearStatus = (academicYear: AcademicYear): { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' } => {
    if (academicYear.is_current) {
        return { label: 'Current', variant: 'default' };
    }

    const now = new Date();
    const startDate = new Date(academicYear.start_date);
    const endDate = new Date(academicYear.end_date);

    if (now < startDate) {
        return { label: 'Upcoming', variant: 'outline' };
    }
    if (now > endDate) {
        return { label: 'Completed', variant: 'secondary' };
    }

    return { label: 'Active', variant: 'outline' };
};
</script>

<template>
    <Head :title="`${school.school_name} - Academic Years`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader :title="`Academic Years - ${school.school_name}`" description="Manage academic years and set the current active year">
                <template #action>
                    <Button @click="showCreateModal = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Academic Year
                    </Button>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Years</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ academicYears.length }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Current Year</CardTitle>
                        <CalendarCheck class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-lg font-bold">
                            {{ currentAcademicYear?.year_name || 'None Set' }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">School</CardTitle>
                        <SchoolIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-medium">{{ school.school_name }}</div>
                        <div class="text-xs text-muted-foreground">{{ school.school_code }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">School Type</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-sm font-medium capitalize">
                            {{ school.school_type?.replace('_', ' ') || 'N/A' }}
                        </div>
                        <div class="text-xs text-muted-foreground uppercase">{{ school.board_affiliation || 'N/A' }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Academic Years List -->
            <div v-if="sortedAcademicYears.length > 0" class="space-y-4">
                <Card
                    v-for="academicYear in sortedAcademicYears"
                    :key="academicYear.id"
                    :class="['transition-shadow hover:shadow-md', academicYear.is_current ? 'ring-2 ring-primary' : '']"
                >
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <CardTitle class="text-xl">{{ academicYear.year_name }}</CardTitle>
                                <Star v-if="academicYear.is_current" class="h-5 w-5 fill-current text-yellow-500" />
                            </div>
                            <CardDescription class="text-base">
                                {{ getDateRange(academicYear) }}
                            </CardDescription>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge :variant="getYearStatus(academicYear).variant">
                                <CheckCircle2 v-if="academicYear.is_current" class="mr-1 h-3 w-3" />
                                {{ getYearStatus(academicYear).label }}
                            </Badge>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="sm">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="handleEdit(academicYear)">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="!academicYear.is_current" @click="handleSetCurrent(academicYear)">
                                        <CalendarCheck class="mr-2 h-4 w-4" />
                                        Set as Current
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(academicYear)">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>

                    <CardContent class="pt-0">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="flex items-center gap-2">
                                <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="text-sm font-medium">Start Date</div>
                                    <div class="text-xs text-muted-foreground">{{ formatDate(academicYear.start_date) }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="text-sm font-medium">End Date</div>
                                    <div class="text-xs text-muted-foreground">{{ formatDate(academicYear.end_date) }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="text-sm font-medium">Status</div>
                                    <div class="text-xs text-muted-foreground">{{ getYearStatus(academicYear).label }}</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="flex flex-col items-center justify-center py-16">
                <Calendar class="mb-4 h-16 w-16 text-muted-foreground" />
                <CardTitle class="mb-2 text-xl">No Academic Years Yet</CardTitle>
                <CardDescription class="mb-6 text-center">
                    Get started by adding the first academic year for {{ school.school_name }}.
                </CardDescription>
                <Button @click="showCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Add First Academic Year
                </Button>
            </Card>
        </div>

        <!-- Create Academic Year Modal -->
        <SchoolAcademicYearModal
            :open="showCreateModal"
            :school="school"
            @update:open="showCreateModal = $event"
            @academic-year-created="handleAcademicYearCreated"
        />
    </AppLayout>
</template>
