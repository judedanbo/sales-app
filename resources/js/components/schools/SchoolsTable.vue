<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Pagination from '@/components/ui/Pagination.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { index, show } from '@/routes/schools';
import type { PaginatedData, School, SchoolFilters } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronDown,
    ChevronUp,
    Download,
    Edit as EditIcon,
    Eye,
    GraduationCap,
    MapPin,
    MoreHorizontal,
    Phone,
    Plus,
    School as SchoolIcon,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SchoolAcademicYearModal from './SchoolAcademicYearModal.vue';
import SchoolClassModal from './SchoolClassModal.vue';
import SchoolEditModal from './SchoolEditModal.vue';

interface Props {
    schools: PaginatedData<School>;
    filters: SchoolFilters;
    selectedSchools: number[];
    isLoading?: boolean;
}

interface Emits {
    (e: 'sort', column: string): void;
    (e: 'delete', school: School): void;
    (e: 'select', schoolId: number): void;
    (e: 'select-all'): void;
    (e: 'clear-selection'): void;
    (e: 'page-change', page: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Edit modal state
const showEditModal = ref(false);
const schoolToEdit = ref<School | null>(null);

// Class modal state
const showClassModal = ref(false);
const schoolForClass = ref<School | null>(null);

// Academic year modal state
const showAcademicYearModal = ref(false);
const schoolForAcademicYear = ref<School | null>(null);

// Selection helpers
const isSelected = (schoolId: number) => props.selectedSchools.includes(schoolId);

const isAllSelected = computed(() => props.schools.data.length > 0 && props.selectedSchools.length === props.schools.data.length);

const isIndeterminate = computed(() => props.selectedSchools.length > 0 && props.selectedSchools.length < props.schools.data.length);

// Helper function to get filtered parameters (same as in Index.vue)
function getFilteredParameters(filters: SchoolFilters) {
    const defaults: SchoolFilters = {
        search: '',
        school_type: '',
        status: '',
        board_affiliation: '',
        sort_by: 'school_name',
        sort_direction: 'asc',
    };

    const filtered: Partial<SchoolFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof SchoolFilters;
        if (value !== '' && value !== defaults[typedKey]) {
            filtered[typedKey] = value;
        }
    });

    return filtered;
}

// Format date
function formatDate(date: string | undefined) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Get sort icon
function getSortIcon(column: string) {
    if (props.filters.sort_by !== column) {
        return null;
    }
    return props.filters.sort_direction === 'asc' ? ChevronUp : ChevronDown;
}

// Event handlers
function handleSort(column: string) {
    emit('sort', column);
}

function handleDelete(school: School) {
    emit('delete', school);
}

function toggleSelection(schoolId: number) {
    emit('select', schoolId);
}

function selectAll() {
    emit('select-all');
}

function clearSelection() {
    emit('clear-selection');
}

function handlePageChange(page: number) {
    emit('page-change', page);
}

function handleEdit(school: School) {
    schoolToEdit.value = school;
    showEditModal.value = true;
}

function handleSchoolUpdated(school: School) {
    // Refresh the schools data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        preserveScroll: true,
        only: ['schools'],
    });
}

function handleAddClass(school: School) {
    schoolForClass.value = school;
    showClassModal.value = true;
}

function handleClassCreated() {
    showClassModal.value = false;
    router.reload({
        data: getFilteredParameters(props.filters),
        preserveScroll: true,
        only: ['schools'],
    });
}

function handleAddAcademicYear(school: School) {
    schoolForAcademicYear.value = school;
    showAcademicYearModal.value = true;
}

function handleAcademicYearCreated() {
    showAcademicYearModal.value = false;
    router.reload({
        data: getFilteredParameters(props.filters),
        preserveScroll: true,
        only: ['schools'],
    });
}
</script>

<template>
    <!-- Bulk Actions Bar -->
    <div v-if="selectedSchools.length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
        <Card class="border-2 border-primary/20 shadow-lg">
            <CardContent class="p-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm font-medium">
                        {{ selectedSchools.length }} {{ selectedSchools.length === 1 ? 'school' : 'schools' }} selected
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="clearSelection"> Clear </Button>
                        <Button size="sm" class="gap-2">
                            <Download class="h-4 w-4" />
                            Export Selected
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Schools Table -->
    <Card>
        <CardHeader>
            <CardTitle>Schools List</CardTitle>
            <CardDescription> Showing {{ schools.from || 0 }} to {{ schools.to || 0 }} of {{ schools.total }} schools </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3">
                                <Checkbox
                                    :model-value="isAllSelected"
                                    :indeterminate="isIndeterminate"
                                    @update:model-value="selectAll"
                                    aria-label="Select all schools"
                                />
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('school_name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    School Name
                                    <component :is="getSortIcon('school_name')" v-if="getSortIcon('school_name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('school_code')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Code
                                    <component :is="getSortIcon('school_code')" v-if="getSortIcon('school_code')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('school_type')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Type
                                    <component :is="getSortIcon('school_type')" v-if="getSortIcon('school_type')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">Board</th>
                            <th scope="col" class="px-4 py-3">Students</th>
                            <th scope="col" class="px-4 py-3">Teachers</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">
                                <button
                                    @click="handleSort('established_date')"
                                    class="flex items-center gap-1 transition-colors hover:text-foreground"
                                >
                                    Established
                                    <component :is="getSortIcon('established_date')" v-if="getSortIcon('established_date')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="isLoading">
                            <td colspan="10" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-4 w-24" />
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="!schools.data.length">
                            <td colspan="10" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <SchoolIcon class="h-8 w-8 text-muted-foreground" />
                                    <p class="text-muted-foreground">No schools found</p>
                                    <Link :href="index().url">
                                        <Button size="sm">
                                            <Plus class="mr-2 h-4 w-4" />
                                            Add First School
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="school in schools.data" :key="school.id" class="border-b hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <Checkbox
                                    :model-value="isSelected(school.id)"
                                    @update:model-value="() => toggleSelection(school.id)"
                                    :aria-label="`Select ${school.school_name}`"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ school.school_name }}
                                <div v-if="school.principal_name" class="text-xs text-muted-foreground">Principal: {{ school.principal_name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-muted px-1 py-0.5 text-xs">{{ school.school_code }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <span class="capitalize">{{ school.school_type?.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="school.board_affiliation" class="text-xs font-medium uppercase">
                                    {{ school.board_affiliation }}
                                </span>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ school.total_students?.toLocaleString() || '-' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ school.total_teachers?.toLocaleString() || '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                        school.status === 'active'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    {{ school.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(school.established_date) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <Link :href="show(school).url">
                                            <DropdownMenuItem>
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuItem @click="handleEdit(school)">
                                            <EditIcon class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="() => console.log('Add Contact:', school.id)">
                                            <Phone class="mr-2 h-4 w-4" />
                                            Add Contact
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="() => console.log('Add Address:', school.id)">
                                            <MapPin class="mr-2 h-4 w-4" />
                                            Add Address
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="handleAddAcademicYear(school)">
                                            <Calendar class="mr-2 h-4 w-4" />
                                            Add Academic Year
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="handleAddClass(school)">
                                            <GraduationCap class="mr-2 h-4 w-4" />
                                            Add Class
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(school)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :current-page="schools.current_page" :last-page="schools.last_page" :disabled="isLoading" @page-change="handlePageChange" />
        </CardContent>
    </Card>

    <!-- Edit School Modal -->
    <SchoolEditModal :open="showEditModal" :school="schoolToEdit" @update:open="showEditModal = $event" @school-updated="handleSchoolUpdated" />

    <!-- Add Class Modal -->
    <SchoolClassModal :open="showClassModal" :school="schoolForClass" @update:open="showClassModal = $event" @class-created="handleClassCreated" />

    <!-- Add Academic Year Modal -->
    <SchoolAcademicYearModal
        :open="showAcademicYearModal"
        :school="schoolForAcademicYear"
        @update:open="showAcademicYearModal = $event"
        @academic-year-created="handleAcademicYearCreated"
    />
</template>
