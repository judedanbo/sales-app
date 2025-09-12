<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import CategoryTree from '@/components/categories/CategoryTree.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/categories-simple';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    List, 
    Plus, 
    TreePine,
    FolderOpen,
    Package,
    Activity,
    Layers
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: index().url,
    },
    {
        title: 'Tree View',
    },
];

// Statistics computed from tree data
const stats = computed(() => {
    const flattenCategories = (categories: Category[]): Category[] => {
        const result: Category[] = [];
        for (const category of categories) {
            result.push(category);
            if (category.children) {
                result.push(...flattenCategories(category.children));
            }
        }
        return result;
    };

    const allCategories = flattenCategories(props.categories);
    
    return {
        total: allCategories.length,
        active: allCategories.filter(cat => cat.is_active).length,
        inactive: allCategories.filter(cat => !cat.is_active).length,
        root: props.categories.length,
        withChildren: allCategories.filter(cat => cat.children && cat.children.length > 0).length,
        products: allCategories.reduce((sum, cat) => sum + (cat.products_count || 0), 0),
    };
});

// Event handlers
function handleDelete(category: Category) {
    router.delete(`/categories/${category.id}`, {
        preserveScroll: true,
    });
}

function handleEdit(category: Category) {
    // Navigate to edit form or open modal
    // For now, we'll just navigate to the category show page
    router.visit(`/categories/${category.id}`);
}

function handleMove(category: Category, newParentId: number | null) {
    if (confirm(`Move "${category.name}" to a different parent?`)) {
        router.post(`/categories/${category.id}/move`, {
            parent_id: newParentId,
        }, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Categories Tree" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader 
                title="Category Tree" 
                description="Visualize and manage the hierarchical structure of your categories"
            >
                <template #action>
                    <div class="flex gap-2">
                        <Button variant="outline" as="a" :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to List
                        </Button>
                        <Button variant="outline" as="a" :href="index().url">
                            <List class="mr-2 h-4 w-4" />
                            Table View
                        </Button>
                        <PermissionGuard permission="create_categories">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Add Category
                            </Button>
                        </PermissionGuard>
                    </div>
                </template>
            </PageHeader>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <TreePine class="h-4 w-4" />
                            Total Categories
                        </CardDescription>
                        <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                    </CardHeader>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Activity class="h-4 w-4" />
                            Active
                        </CardDescription>
                        <CardTitle class="text-3xl text-green-600">{{ stats.active }}</CardTitle>
                    </CardHeader>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Activity class="h-4 w-4" />
                            Inactive
                        </CardDescription>
                        <CardTitle class="text-3xl text-orange-600">{{ stats.inactive }}</CardTitle>
                    </CardHeader>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Layers class="h-4 w-4" />
                            Root Categories
                        </CardDescription>
                        <CardTitle class="text-3xl text-blue-600">{{ stats.root }}</CardTitle>
                    </CardHeader>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <FolderOpen class="h-4 w-4" />
                            With Children
                        </CardDescription>
                        <CardTitle class="text-3xl text-purple-600">{{ stats.withChildren }}</CardTitle>
                    </CardHeader>
                </Card>
                
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center gap-2">
                            <Package class="h-4 w-4" />
                            Total Products
                        </CardDescription>
                        <CardTitle class="text-3xl text-indigo-600">{{ stats.products }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Category Tree -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <TreePine class="h-5 w-5" />
                                Category Hierarchy
                            </CardTitle>
                            <CardDescription>
                                Explore and manage the hierarchical structure of your categories
                            </CardDescription>
                        </div>
                        
                        <div class="text-sm text-muted-foreground">
                            {{ stats.total }} categories in {{ stats.root }} root {{ stats.root === 1 ? 'category' : 'categories' }}
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="categories.length === 0" class="py-12 text-center">
                        <TreePine class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-semibold">No categories found</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Get started by creating your first category
                        </p>
                        <PermissionGuard permission="create_categories">
                            <Button class="mt-4">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Category
                            </Button>
                        </PermissionGuard>
                    </div>
                    
                    <CategoryTree
                        v-else
                        :categories="categories"
                        @delete="handleDelete"
                        @edit="handleEdit"
                        @move="handleMove"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>