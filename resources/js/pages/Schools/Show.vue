<script setup lang="ts">
import SchoolEditModal from '@/components/schools/SchoolEditModal.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as schoolsIndex, show } from '@/routes/schools';
import { index as classesIndex } from '@/routes/schools/classes';
import { type BreadcrumbItem, type School } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Building2, Calendar, Edit, ExternalLink, FileText, Globe, GraduationCap, Mail, MapPin, Phone, Trash2, User, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    school: School;
}

const props = defineProps<Props>();

const { warning, success, error } = useAlerts();

const isEditModalOpen = ref(false);
const isDeleting = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schools',
        href: schoolsIndex().url,
    },
    {
        title: props.school.school_name,
        href: show(props.school.id).url,
    },
];

// Computed properties
const isActive = computed(() => props.school.is_active);
const hasContacts = computed(() => props.school.contacts && props.school.contacts.length > 0);
const hasAddresses = computed(() => props.school.addresses && props.school.addresses.length > 0);
const hasManagement = computed(() => props.school.management && props.school.management.length > 0);
const hasOfficials = computed(() => props.school.officials && props.school.officials.length > 0);

// Event handlers
const handleEdit = () => {
    isEditModalOpen.value = true;
};

const handleSchoolUpdated = (updatedSchool: School) => {
    success(`School "${updatedSchool.school_name}" has been updated successfully!`, {
        position: 'top-center',
        duration: 4000,
    });
    // Refresh the page to get updated data
    router.get(show(props.school.id).url);
};

const handleDelete = () => {
    warning(
        `Are you sure you want to delete "${props.school.school_name}"? This action cannot be undone and will permanently remove all associated data including contacts, addresses, classes, and academic years.`,
        {
            title: 'Delete School Confirmation',
            persistent: true,
        },
    );

    // TODO: Implement proper delete confirmation modal with confirm/cancel buttons
    // For now, we show a warning but don't actually delete until modal is implemented
};

// Format date helper
const formatDate = (dateString?: string) => {
    if (!dateString) return 'Not set';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Format school type
const formatSchoolType = (type: string) => {
    const types: Record<string, string> = {
        primary: 'Primary',
        secondary: 'Secondary',
        higher_secondary: 'Higher Secondary',
        k12: 'K-12',
    };
    return types[type] || type;
};

// Format board affiliation
const formatBoardAffiliation = (board?: string) => {
    if (!board) return 'Not specified';
    const boards: Record<string, string> = {
        cbse: 'CBSE',
        icse: 'ICSE',
        state_board: 'State Board',
        ib: 'IB',
        cambridge: 'Cambridge',
    };
    return boards[board] || board;
};

// Format contact type
const formatContactType = (type: string) => {
    const types: Record<string, string> = {
        phone: 'Phone',
        email: 'Email',
        fax: 'Fax',
        mobile: 'Mobile',
    };
    return types[type] || type;
};

// Format address type
const formatAddressType = (type: string) => {
    const types: Record<string, string> = {
        main: 'Main Address',
        mailing: 'Mailing Address',
        billing: 'Billing Address',
    };
    return types[type] || type;
};
</script>

<template>
    <AppLayout>
        <Head :title="school.school_name" />

        <PageHeader :title="school.school_name" :breadcrumbs="breadcrumbs">
            <template #actions>
                <Button variant="outline" as="a" :href="classesIndex(school.id).url">
                    <GraduationCap class="mr-2 h-4 w-4" />
                    View Classes
                </Button>

                <Button variant="outline" @click="handleEdit">
                    <Edit class="mr-2 h-4 w-4" />
                    Edit
                </Button>
                <Button variant="destructive" @click="handleDelete" :disabled="isDeleting">
                    <Trash2 class="mr-2 h-4 w-4" />
                    {{ isDeleting ? 'Deleting...' : 'Delete' }}
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Basic Information -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Building2 class="h-5 w-5" />
                                {{ school.school_name }}
                            </CardTitle>
                            <CardDescription>School Code: {{ school.school_code }}</CardDescription>
                        </div>
                        <Badge :variant="isActive ? 'default' : 'secondary'">
                            {{ isActive ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">School Type</div>
                            <div class="flex items-center gap-2">
                                <GraduationCap class="h-4 w-4" />
                                {{ formatSchoolType(school.school_type) }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Board Affiliation</div>
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4" />
                                {{ formatBoardAffiliation(school.board_affiliation) }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Established</div>
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(school.established_date) }}
                            </div>
                        </div>

                        <div v-if="school.website" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Website</div>
                            <div class="flex items-center gap-2">
                                <Globe class="h-4 w-4" />
                                <a
                                    :href="school.website"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-1 text-primary hover:underline"
                                >
                                    Visit Website
                                    <ExternalLink class="h-3 w-3" />
                                </a>
                            </div>
                        </div>

                        <div v-if="school.total_students" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Total Students</div>
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                {{ school.total_students.toLocaleString() }}
                            </div>
                        </div>

                        <div v-if="school.total_teachers" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Total Teachers</div>
                            <div class="flex items-center gap-2">
                                <User class="h-4 w-4" />
                                {{ school.total_teachers.toLocaleString() }}
                            </div>
                        </div>
                    </div>

                    <div v-if="school.description" class="space-y-2">
                        <Separator />
                        <div>
                            <div class="mb-2 text-sm font-medium text-muted-foreground">Description</div>
                            <p class="text-sm leading-relaxed">{{ school.description }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Contact Information -->
            <Card v-if="hasContacts">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Phone class="h-5 w-5" />
                        Contact Information
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="contact in school.contacts" :key="contact.id" class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-3">
                                <Mail v-if="contact.contact_type === 'email'" class="h-4 w-4 text-muted-foreground" />
                                <Phone v-else class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">{{ contact.contact_value }}</div>
                                    <div class="text-sm text-muted-foreground">{{ formatContactType(contact.contact_type) }}</div>
                                </div>
                            </div>
                            <Badge v-if="contact.is_primary" variant="secondary" class="text-xs"> Primary </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Address Information -->
            <Card v-if="hasAddresses">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MapPin class="h-5 w-5" />
                        Addresses
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="address in school.addresses" :key="address.id" class="space-y-2 rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">{{ formatAddressType(address.address_type) }}</div>
                                <Badge v-if="address.is_primary" variant="secondary" class="text-xs"> Primary </Badge>
                            </div>
                            <div class="space-y-1 text-sm">
                                <div>{{ address.address_line1 }}</div>
                                <div v-if="address.address_line2">{{ address.address_line2 }}</div>
                                <div>{{ address.city }}, {{ address.state }}</div>
                                <div>{{ address.country }} - {{ address.postal_code }}</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Management Information -->
            <Card v-if="hasManagement">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Building2 class="h-5 w-5" />
                        Management
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="management in school.management" :key="management.id" class="rounded-lg border p-4">
                            <div class="mb-2 font-medium">{{ management.management_type }}</div>
                            <div v-if="management.organization_name" class="mb-1 text-sm text-muted-foreground">
                                Organization: {{ management.organization_name }}
                            </div>
                            <div v-if="management.registration_number" class="text-sm text-muted-foreground">
                                Registration: {{ management.registration_number }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Officials Information -->
            <Card v-if="hasOfficials">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        School Officials
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="official in school.officials" :key="official.id" class="space-y-2 rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">{{ official.name }}</div>
                                <Badge :variant="official.is_active ? 'default' : 'secondary'" class="text-xs">
                                    {{ official.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                            <div class="text-sm text-muted-foreground">{{ official.designation }}</div>
                            <div v-if="official.contact_number || official.email" class="space-y-1">
                                <div v-if="official.contact_number" class="flex items-center gap-2 text-sm">
                                    <Phone class="h-3 w-3" />
                                    {{ official.contact_number }}
                                </div>
                                <div v-if="official.email" class="flex items-center gap-2 text-sm">
                                    <Mail class="h-3 w-3" />
                                    {{ official.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm">Record Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <div>Created: {{ formatDate(school.created_at) }}</div>
                    <div>Last Updated: {{ formatDate(school.updated_at) }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Edit Modal -->
        <SchoolEditModal v-model:open="isEditModalOpen" :school="school" @school-updated="handleSchoolUpdated" />
    </AppLayout>
</template>
