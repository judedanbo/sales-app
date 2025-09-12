<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index as categoriesIndex, show } from '@/routes/categories-simple';
import { type BreadcrumbItem, type Category, type CategoryBreadcrumb } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Calendar,
    ChevronRight,
    Edit, 
    Eye,
    Folder, 
    FolderOpen,
    Home,
    Package,
    Trash2,
    TreePine,
    User,
    Users
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    category: Category;
    breadcrumb: CategoryBreadcrumb[];
}

const props = defineProps<Props>();

const isDeleting = ref(false);

// Build breadcrumbs from category breadcrumb
const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [
        {
            title: 'Categories',
            href: categoriesIndex().url,
        },
    ];
    
    // Add each breadcrumb item except the last one (current category)
    props.breadcrumb.slice(0, -1).forEach(item => {
        items.push({
            title: item.name,
            href: show(item.id).url,
        });
    });
    
    // Add current category
    if (props.breadcrumb.length > 0) {
        const current = props.breadcrumb[props.breadcrumb.length - 1];
        items.push({
            title: current.name,
            href: show(current.id).url,
        });
    }
    
    return items;
});

// Computed properties
const isActive = computed(() => props.category.is_active);
const hasChildren = computed(() => props.category.children && props.category.children.length > 0);

// Event handlers
const handleEdit = () => {
    // This would open an edit modal or navigate to edit page
    console.log('Edit category:', props.category.id);
};

const handleDelete = () => {
    if (confirm(`Are you sure you want to delete "${props.category.name}"?`)) {
        isDeleting.value = true;
        router.delete(destroy(props.category.id).url, {
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const toggleStatus = () => {
    router.post(`/categories/${props.category.id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

// Format date helper
const formatDate = (dateString?: string) => {
    if (!dateString) return 'Not set';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Get depth indicators
const getDepthIndicator = (depth: number) => {
    if (depth === 0) return 'Root Level';
    if (depth === 1) return '1st Level';
    if (depth === 2) return '2nd Level';
    if (depth === 3) return '3rd Level';
    return `${depth}th Level`;
};
</script>

<template>
    <AppLayout>
        <Head :title="category.name" />

        <PageHeader :title="category.name" :breadcrumbs="breadcrumbs">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button variant="outline" as="a" :href="categoriesIndex().url">
                        <Home class="mr-2 h-4 w-4" />
                        All Categories
                    </Button>

                    <Button variant="outline" as="a" href="/categories/tree">
                        <TreePine class="mr-2 h-4 w-4" />
                        Tree View
                    </Button>

                    <PermissionGuard permission="edit_categories">
                        <Button variant="outline" @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </PermissionGuard>

                    <PermissionGuard permission="edit_categories">
                        <Button 
                            variant="outline" 
                            @click="toggleStatus"
                            :class="isActive ? 'text-orange-600 hover:text-orange-700' : 'text-green-600 hover:text-green-700'"
                        >
                            {{ isActive ? 'Deactivate' : 'Activate' }}
                        </Button>
                    </PermissionGuard>

                    <PermissionGuard permission="delete_categories">
                        <Button variant="destructive" @click="handleDelete" :disabled="isDeleting">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ isDeleting ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </PermissionGuard>
                </div>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Breadcrumb Trail -->
            <Card v-if="breadcrumb.length > 1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <TreePine class="h-5 w-5" />
                        Category Path
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2 text-sm">
                        <Home class="h-4 w-4 text-muted-foreground" />
                        <template v-for="(item, index) in breadcrumb" :key="item.id">
                            <template v-if="index > 0">
                                <ChevronRight class="h-4 w-4 text-muted-foreground" />
                            </template>
                            <Link 
                                v-if="index < breadcrumb.length - 1"
                                :href="show(item.id).url"
                                class="hover:underline"
                            >
                                {{ item.name }}
                            </Link>
                            <span v-else class="font-medium">{{ item.name }}</span>
                        </template>
                    </div>
                </CardContent>
            </Card>

            <!-- Basic Information -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <component 
                                    :is="hasChildren ? FolderOpen : Folder"
                                    class="h-5 w-5"
                                />
                                {{ category.name }}
                            </CardTitle>
                            <CardDescription v-if="category.slug">
                                Slug: /{{ category.slug }}
                            </CardDescription>
                        </div>
                        <Badge :variant="isActive ? 'default' : 'secondary'">
                            {{ isActive ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Hierarchy Level</div>
                            <div class="flex items-center gap-2">
                                <TreePine class="h-4 w-4" />
                                {{ getDepthIndicator(category.depth || 0) }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Sort Order</div>
                            <div class="flex items-center gap-2">
                                <span class="tabular-nums">{{ category.sort_order }}</span>
                            </div>
                        </div>

                        <div v-if="category.color" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Color</div>
                            <div class="flex items-center gap-2">
                                <div 
                                    class="h-4 w-4 rounded border" 
                                    :style="{ backgroundColor: category.color }"
                                ></div>
                                {{ category.color }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Children Categories</div>
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                {{ category.children_count || 0 }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Products</div>
                            <div class="flex items-center gap-2">
                                <Package class="h-4 w-4" />
                                {{ category.products_count || 0 }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Type</div>
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">
                                    {{ category.is_root ? 'Root Category' : 'Sub Category' }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div v-if="category.description" class="space-y-2">
                        <Separator />
                        <div>
                            <div class="mb-2 text-sm font-medium text-muted-foreground">Description</div>
                            <p class="text-sm leading-relaxed">{{ category.description }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Parent Category -->
            <Card v-if="category.parent">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FolderOpen class="h-5 w-5" />
                        Parent Category
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="flex items-center gap-3">
                            <FolderOpen class="h-8 w-8 text-muted-foreground" />
                            <div>
                                <Link 
                                    :href="show(category.parent.id).url"
                                    class="font-medium hover:underline"
                                >
                                    {{ category.parent.name }}
                                </Link>
                                <div v-if="category.parent.description" class="text-sm text-muted-foreground">
                                    {{ category.parent.description }}
                                </div>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" as="a" :href="show(category.parent.id).url">
                            <Eye class="mr-2 h-4 w-4" />
                            View
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Child Categories -->
            <Card v-if="hasChildren">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center gap-2">
                            <Folder class="h-5 w-5" />
                            Child Categories
                        </CardTitle>
                        <Badge variant="secondary">{{ category.children!.length }}</Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b">
                            <tr class="border-b">
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Description</th>
                                <th class="px-4 py-3 text-left">Children</th>
                                <th class="px-4 py-3 text-left">Products</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Order</th>
                                <th class="w-24 px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="child in category.children" :key="child.id" class="border-b hover:bg-muted/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <component 
                                            :is="child.has_children ? FolderOpen : Folder"
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <div>
                                            <Link 
                                                :href="show(child.id).url"
                                                class="font-medium hover:underline"
                                            >
                                                {{ child.name }}
                                            </Link>
                                            <div v-if="child.slug" class="text-xs text-muted-foreground">
                                                /{{ child.slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="child.description" class="max-w-xs truncate text-sm">
                                        {{ child.description }}
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge v-if="child.children_count && child.children_count > 0" variant="secondary">
                                        {{ child.children_count }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge v-if="child.products_count && child.products_count > 0" variant="outline">
                                        {{ child.products_count }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="child.is_active ? 'default' : 'secondary'">
                                        {{ child.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm tabular-nums">{{ child.sort_order }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        as="a"
                                        :href="show(child.id).url"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Statistics -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Direct Children</CardDescription>
                        <CardTitle class="text-2xl">{{ category.children_count || 0 }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Categories directly under this category
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Active Children</CardDescription>
                        <CardTitle class="text-2xl text-green-600">{{ category.active_children_count || 0 }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Active child categories
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Products</CardDescription>
                        <CardTitle class="text-2xl text-blue-600">{{ category.products_count || 0 }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Products in this category
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Hierarchy Depth</CardDescription>
                        <CardTitle class="text-2xl text-purple-600">{{ category.depth || 0 }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">
                            Levels from root category
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Record Information
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-3">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Created</div>
                                <div class="text-sm">{{ formatDate(category.created_at) }}</div>
                            </div>
                            <div v-if="category.created_by_user">
                                <div class="text-sm font-medium text-muted-foreground mb-1">Created By</div>
                                <div class="flex items-center gap-2 text-sm">
                                    <User class="h-4 w-4" />
                                    {{ category.created_by_user.name }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="text-sm font-medium text-muted-foreground mb-1">Last Updated</div>
                                <div class="text-sm">{{ formatDate(category.updated_at) }}</div>
                            </div>
                            <div v-if="category.updated_by_user">
                                <div class="text-sm font-medium text-muted-foreground mb-1">Updated By</div>
                                <div class="flex items-center gap-2 text-sm">
                                    <User class="h-4 w-4" />
                                    {{ category.updated_by_user.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>