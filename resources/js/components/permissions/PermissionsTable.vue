<script setup lang="ts">
import Badge from '@/components/ui/badge.vue';
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
import { show } from '@/routes/permissions';
import type { PaginatedData, Permission, PermissionFilters } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Download, Eye, Key, Layers, MoreHorizontal, Shield } from 'lucide-vue-next';
import { computed } from 'vue';
import { useAlerts } from '@/composables/useAlerts';

interface Props {
    permissions: PaginatedData<Permission>;
    filters: PermissionFilters;
    selectedPermissions: number[];
    isLoading?: boolean;
}

interface Emits {
    (e: 'sort', column: string): void;
    (e: 'select', permissionId: number): void;
    (e: 'select-all'): void;
    (e: 'clear-selection'): void;
    (e: 'page-change', page: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { info } = useAlerts();

// Selection helpers
const isSelected = (permissionId: number) => props.selectedPermissions.includes(permissionId);

const isAllSelected = computed(() => props.permissions.data.length > 0 && props.selectedPermissions.length === props.permissions.data.length);

const isIndeterminate = computed(() => props.selectedPermissions.length > 0 && props.selectedPermissions.length < props.permissions.data.length);

// Format date
function formatDate(date: string | undefined) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Handle view roles action
function handleViewRoles(permission: Permission) {
    info(`Viewing roles for permission: ${permission.display_name || permission.name}`, {
        position: 'top-right',
        duration: 5000
    });
    
    // Navigate to permission show page which has role management
    window.location.href = show(permission.id).url;
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

function toggleSelection(permissionId: number) {
    emit('select', permissionId);
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

// Get category badge color
function getCategoryColor(category: string | undefined) {
    if (!category) return 'default';

    const colors = {
        user: 'blue',
        role: 'green',
        permission: 'purple',
        school: 'orange',
        system: 'red',
        audit: 'yellow',
    };

    const key = category.toLowerCase();
    return colors[key as keyof typeof colors] || 'default';
}
</script>

<template>
    <!-- Bulk Actions Bar -->
    <div v-if="selectedPermissions.length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
        <Card class="border-2 border-primary/20 shadow-lg">
            <CardContent class="p-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm font-medium">
                        {{ selectedPermissions.length }} {{ selectedPermissions.length === 1 ? 'permission' : 'permissions' }} selected
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

    <!-- Permissions Table -->
    <Card>
        <CardHeader>
            <CardTitle>Permissions List</CardTitle>
            <CardDescription>
                Showing {{ permissions.from || 0 }} to {{ permissions.to || 0 }} of {{ permissions.total }} permissions
            </CardDescription>
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
                                    aria-label="Select all permissions"
                                />
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Permission
                                    <component :is="getSortIcon('name')" v-if="getSortIcon('name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('category')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Category
                                    <component :is="getSortIcon('category')" v-if="getSortIcon('category')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('guard_name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Guard
                                    <component :is="getSortIcon('guard_name')" v-if="getSortIcon('guard_name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">Roles</th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('created_at')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Created
                                    <component :is="getSortIcon('created_at')" v-if="getSortIcon('created_at')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-4 w-24" />
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="!permissions.data.length">
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Key class="h-8 w-8 text-muted-foreground" />
                                    <p class="text-muted-foreground">No permissions found</p>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="permission in permissions.data" :key="permission.id" class="border-b hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <Checkbox
                                    :model-value="isSelected(permission.id)"
                                    @update:model-value="() => toggleSelection(permission.id)"
                                    :aria-label="`Select ${permission.name}`"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Key class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">{{ permission.display_name || permission.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ permission.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="permission.category" class="flex items-center gap-2">
                                    <Layers class="h-3 w-3 text-muted-foreground" />
                                    <Badge :variant="getCategoryColor(permission.category)" class="text-xs">
                                        {{ permission.category_display || permission.category }}
                                    </Badge>
                                </div>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary" class="text-xs">
                                    {{ permission.guard_name }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Shield class="h-3 w-3 text-muted-foreground" />
                                    <span>{{ permission.roles_count || 0 }}</span>
                                </div>
                                <div v-if="permission.sample_roles?.length" class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        permission.sample_roles
                                            .slice(0, 2)
                                            .map((r) => r.display_name || r.name)
                                            .join(', ')
                                    }}
                                    <span v-if="(permission.roles_count || 0) > 2">+{{ (permission.roles_count || 0) - 2 }} more</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(permission.created_at) }}
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

                                        <Link :href="show(permission).url">
                                            <DropdownMenuItem>
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="() => handleViewRoles(permission)">
                                            <Shield class="mr-2 h-4 w-4" />
                                            View Roles
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :current-page="permissions.current_page"
                :last-page="permissions.last_page"
                :disabled="isLoading"
                @page-change="handlePageChange"
            />
        </CardContent>
    </Card>
</template>
