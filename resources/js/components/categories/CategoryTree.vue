<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Category } from '@/types';
import CategoryTreeNode from './CategoryTreeNode.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, ExpandIcon, Minimize2 } from 'lucide-vue-next';

interface Props {
    categories: Category[];
    expandAll?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    expandAll: false,
});

const emit = defineEmits<{
    delete: [category: Category];
    edit: [category: Category];
    move: [category: Category, newParentId: number | null];
}>();

// Search functionality
const searchQuery = ref('');
const expandedNodes = ref<Set<number>>(new Set());
const isAllExpanded = ref(props.expandAll);

// Filter categories based on search
const filteredCategories = computed(() => {
    if (!searchQuery.value) return props.categories;
    
    return filterCategoriesRecursive(props.categories, searchQuery.value.toLowerCase());
});

function filterCategoriesRecursive(categories: Category[], query: string): Category[] {
    const filtered: Category[] = [];
    
    for (const category of categories) {
        const matchesQuery = category.name.toLowerCase().includes(query) ||
                           category.description?.toLowerCase().includes(query);
        
        const filteredChildren = category.children ? 
            filterCategoriesRecursive(category.children, query) : [];
        
        if (matchesQuery || filteredChildren.length > 0) {
            const filteredCategory: Category = {
                ...category,
                children: filteredChildren,
            };
            filtered.push(filteredCategory);
            
            // Auto-expand nodes that have matches
            if (matchesQuery || filteredChildren.length > 0) {
                expandedNodes.value.add(category.id);
            }
        }
    }
    
    return filtered;
}

// Expand/Collapse functionality
function toggleExpandAll() {
    if (isAllExpanded.value) {
        expandedNodes.value.clear();
    } else {
        expandAllNodes(filteredCategories.value);
    }
    isAllExpanded.value = !isAllExpanded.value;
}

function expandAllNodes(categories: Category[]) {
    for (const category of categories) {
        if (category.children && category.children.length > 0) {
            expandedNodes.value.add(category.id);
            expandAllNodes(category.children);
        }
    }
}

function toggleNode(categoryId: number) {
    if (expandedNodes.value.has(categoryId)) {
        expandedNodes.value.delete(categoryId);
    } else {
        expandedNodes.value.add(categoryId);
    }
}

// Event handlers
function handleDelete(category: Category) {
    emit('delete', category);
}

function handleEdit(category: Category) {
    emit('edit', category);
}

function handleMove(category: Category, newParentId: number | null) {
    emit('move', category, newParentId);
}

// Initialize expanded state
if (props.expandAll) {
    expandAllNodes(props.categories);
    isAllExpanded.value = true;
}
</script>

<template>
    <div class="space-y-4">
        <!-- Controls -->
        <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="relative flex-1">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="searchQuery"
                    placeholder="Search categories..."
                    class="pl-9"
                />
            </div>
            
            <!-- Expand/Collapse All -->
            <Button
                variant="outline"
                size="sm"
                @click="toggleExpandAll"
            >
                <component :is="isAllExpanded ? Minimize2 : ExpandIcon" class="mr-2 h-4 w-4" />
                {{ isAllExpanded ? 'Collapse All' : 'Expand All' }}
            </Button>
        </div>
        
        <!-- Tree -->
        <div class="space-y-1">
            <div v-if="filteredCategories.length === 0" class="py-8 text-center text-muted-foreground">
                <div v-if="searchQuery">
                    No categories found matching "{{ searchQuery }}"
                </div>
                <div v-else>
                    No categories available
                </div>
            </div>
            
            <CategoryTreeNode
                v-for="category in filteredCategories"
                :key="category.id"
                :category="category"
                :expanded="expandedNodes.has(category.id)"
                :depth="0"
                :expandedNodes="expandedNodes"
                @toggle="toggleNode"
                @delete="handleDelete"
                @edit="handleEdit"
                @move="handleMove"
            />
        </div>
    </div>
</template>