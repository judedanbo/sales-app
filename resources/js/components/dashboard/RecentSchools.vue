<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge.vue';
import { Link } from '@inertiajs/vue3';
import { create, show } from '@/routes/schools';
import { 
    Clock, 
    Plus, 
    School as SchoolIcon, 
    MapPin, 
    Users,
    Phone,
    ExternalLink 
} from 'lucide-vue-next';
import type { School } from '@/types';

interface Props {
    recentSchools: School[];
}

defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const getSchoolTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        'primary': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'secondary': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'higher_secondary': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        'k12': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
    };
    return colors[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
};

const getSchoolTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        'primary': 'Primary',
        'secondary': 'Secondary',
        'higher_secondary': 'Higher Secondary',
        'k12': 'K-12'
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
            <Link :href="create().url">
                <Button size="sm">
                    <Plus class="h-3 w-3 mr-1" />
                    Add School
                </Button>
            </Link>
        </CardHeader>
        <CardContent>
            <div v-if="recentSchools.length === 0" class="text-center py-8 text-muted-foreground">
                <SchoolIcon class="mx-auto h-8 w-8 mb-2 opacity-50" />
                <p class="text-sm">No schools added recently</p>
                <Link :href="create().url">
                    <Button variant="ghost" size="sm" class="mt-2">
                        Add your first school
                    </Button>
                </Link>
            </div>
            
            <div v-else class="space-y-4">
                <div 
                    v-for="school in recentSchools" 
                    :key="school.id"
                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                >
                    <div class="flex items-start space-x-3 flex-1">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                <SchoolIcon class="h-4 w-4 text-primary" />
                            </div>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-sm font-medium truncate">{{ school.school_name }}</h4>
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