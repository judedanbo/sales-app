<script setup lang="ts">
import SchoolClassModal from '@/components/schools/SchoolClassModal.vue';
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
import type { School, SchoolClass } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BookOpen, Edit, GraduationCap, MoreHorizontal, Plus, School as SchoolIcon, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    school: School;
    classes: SchoolClass[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Schools', href: '/schools' },
    { label: props.school.school_name, href: `/schools/${props.school.id}` },
    { label: 'Classes' },
];

// Modal state
const showCreateModal = ref(false);

// Sort classes by order sequence and grade level
const sortedClasses = computed(() => {
    return [...props.classes].sort((a, b) => {
        if (a.order_sequence !== b.order_sequence) {
            return a.order_sequence - b.order_sequence;
        }
        return a.grade_level - b.grade_level;
    });
});

const handleClassCreated = () => {
    showCreateModal.value = false;
    router.reload({ only: ['classes'] });
};

const handleEdit = (schoolClass: SchoolClass) => {
    router.visit(`/schools/${props.school.id}/classes/${schoolClass.id}/edit`);
};

const handleDelete = (schoolClass: SchoolClass) => {
    if (confirm(`Are you sure you want to delete class "${schoolClass.class_name}"?`)) {
        router.delete(`/schools/${props.school.id}/classes/${schoolClass.id}`, {
            preserveScroll: true,
        });
    }
};

const getAgeRange = (schoolClass: SchoolClass): string => {
    if (!schoolClass.min_age && !schoolClass.max_age) {
        return 'No age limit';
    }
    if (schoolClass.min_age && schoolClass.max_age) {
        return `${schoolClass.min_age}-${schoolClass.max_age} years`;
    }
    if (schoolClass.min_age) {
        return `${schoolClass.min_age}+ years`;
    }
    if (schoolClass.max_age) {
        return `Up to ${schoolClass.max_age} years`;
    }
    return 'No age limit';
};
</script>

<template>
    <Head :title="`${school.school_name} - Classes`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader :title="`Classes - ${school.school_name}`" description="Manage classes for this school">
                <template #action>
                    <Button @click="showCreateModal = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Class
                    </Button>
                </template>
            </PageHeader>

            <!-- Stats Card -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
                        <GraduationCap class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ classes.length }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Grade Range</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{
                                classes.length > 0
                                    ? `${Math.min(...classes.map((c) => c.grade_level))}-${Math.max(...classes.map((c) => c.grade_level))}`
                                    : 'N/A'
                            }}
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

            <!-- Classes Grid -->
            <div v-if="sortedClasses.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="schoolClass in sortedClasses" :key="schoolClass.id" class="transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
                        <div class="space-y-1">
                            <CardTitle class="text-lg">{{ schoolClass.class_name }}</CardTitle>
                            <CardDescription>
                                <code class="rounded bg-muted px-1 py-0.5 text-xs">{{ schoolClass.class_code }}</code>
                            </CardDescription>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm">
                                    <MoreHorizontal class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="handleEdit(schoolClass)">
                                    <Edit class="mr-2 h-4 w-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(schoolClass)">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Grade Level</span>
                            <Badge variant="secondary">Grade {{ schoolClass.grade_level }}</Badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Age Range</span>
                            <span class="text-sm">{{ getAgeRange(schoolClass) }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Order</span>
                            <span class="text-sm">{{ schoolClass.order_sequence }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="flex flex-col items-center justify-center py-16">
                <GraduationCap class="mb-4 h-16 w-16 text-muted-foreground" />
                <CardTitle class="mb-2 text-xl">No Classes Yet</CardTitle>
                <CardDescription class="mb-6 text-center"> Get started by adding the first class for {{ school.school_name }}. </CardDescription>
                <Button @click="showCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Add First Class
                </Button>
            </Card>
        </div>

        <!-- Create Class Modal -->
        <SchoolClassModal :open="showCreateModal" :school="school" @update:open="showCreateModal = $event" @class-created="handleClassCreated" />
    </AppLayout>
</template>
