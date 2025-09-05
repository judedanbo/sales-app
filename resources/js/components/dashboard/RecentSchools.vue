<script setup lang="ts">
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { show } from '@/routes/schools';
import type { School } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Clock, ExternalLink, MapPin, Phone, Plus, School as SchoolIcon, Users } from 'lucide-vue-next';

interface Props {
    recentSchools: School[];
}

defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getSchoolTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        primary: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        secondary: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        higher_secondary: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        k12: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    };
    return colors[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
};

const getSchoolTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        primary: 'Primary',
        secondary: 'Secondary',
        higher_secondary: 'Higher Secondary',
        k12: 'K-12',
    };
    return labels[type] || type;
};
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <div>
                <CardTitle class="flex items-center gap-2">
                    <Clock class="h-4 w-4" />
                    Recent Schools
                </CardTitle>
                <CardDescription>Schools added in the last 30 days</CardDescription>
            </div>
            <Button size="sm" disabled>
                <Plus class="mr-1 h-3 w-3" />
                Add School
            </Button>
        </CardHeader>
        <CardContent>
            <div v-if="recentSchools.length === 0" class="py-8 text-center text-muted-foreground">
                <SchoolIcon class="mx-auto mb-2 h-8 w-8 opacity-50" />
                <p class="text-sm">No schools added recently</p>
                <Button variant="ghost" size="sm" class="mt-2" disabled> Add your first school </Button>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="school in recentSchools"
                    :key="school.id"
                    class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-accent/50"
                >
                    <div class="flex flex-1 items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                <SchoolIcon class="h-4 w-4 text-primary" />
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <h4 class="truncate text-sm font-medium">{{ school.school_name }}</h4>
                                <Badge :class="getSchoolTypeColor(school.school_type)" variant="secondary">
                                    {{ getSchoolTypeLabel(school.school_type) }}
                                </Badge>
                            </div>

                            <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Clock class="h-3 w-3" />
                                    {{ formatDate(school.created_at) }}
                                </span>

                                <span v-if="school.total_students" class="flex items-center gap-1">
                                    <Users class="h-3 w-3" />
                                    {{ school.total_students.toLocaleString() }} students
                                </span>

                                <span v-if="school.addresses && school.addresses.length > 0" class="flex items-center gap-1">
                                    <MapPin class="h-3 w-3" />
                                    {{ school.addresses[0].city }}
                                </span>

                                <span v-if="school.contacts && school.contacts.length > 0" class="flex items-center gap-1">
                                    <Phone class="h-3 w-3" />
                                    Contact available
                                </span>
                            </div>
                        </div>
                    </div>

                    <Link :href="show(school.id).url">
                        <Button variant="ghost" size="sm">
                            <ExternalLink class="h-3 w-3" />
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
