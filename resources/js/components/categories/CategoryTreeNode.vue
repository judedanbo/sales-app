<script setup lang="ts">
import { computed } from 'vue';
import type { Category } from '@/types';
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { show } from '@/routes/categories-simple';
import { Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    ChevronDown,
    Folder,
    FolderOpen,
    MoreHorizontal,
    Eye,
    Edit,
    Trash2,
    Move,
    Package,
} from 'lucide-vue-next';

interface Props {
    category: Category;
    expanded: boolean;
    depth: number;
    expandedNodes?: Set<number>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    toggle: [categoryId: number];
    delete: [category: Category];
    edit: [category: Category];
    move: [category: Category, newParentId: number | null];
}>();

// Computed properties
const hasChildren = computed(() => 
    props.category.children && props.category.children.length > 0
);

const indentationStyle = computed(() => ({
    paddingLeft: `${props.depth * 24 + 8}px`,
}));

const categoryIcon = computed(() => {
    if (!hasChildren.value) return Package;
    return props.expanded ? FolderOpen : Folder;
});

const toggleIcon = computed(() => 
    props.expanded ? ChevronDown : ChevronRight
);

// Event handlers
function handleToggle() {
    if (hasChildren.value) {
        emit('toggle', props.category.id);
    }
}

function handleDelete() {
    if (confirm(`Are you sure you want to delete "${props.category.name}"?`)) {
        emit('delete', props.category);
    }
}

function handleEdit() {
    emit('edit', props.category);
}

function handleMove() {
    // This would open a modal or dialog for selecting new parent
    // For now, we'll emit the event
    emit('move', props.category, null);
}

// Get connection lines for visual hierarchy
const connectionLines = computed(() => {
    const lines = [];
    for (let i = 0; i < props.depth; i++) {
        lines.push(i);
    }
    return lines;
});
</script>

<template>
    <div class="group">
        <!-- Tree Node -->
        <div 
            class="flex items-center gap-2 rounded-md py-2 hover:bg-muted/50 transition-colors"
            :style="indentationStyle"
        >
            <!-- Connection Lines -->
            <div 
                v-for="lineDepth in connectionLines"
                :key="lineDepth"
                class="absolute w-px h-8 bg-border"
                :style="{
                    left: `${lineDepth * 24 + 20}px`,
                    marginTop: '-16px'
                }"
            />
            
            <!-- Branch Line -->
            <div 
                v-if="depth > 0"
                class="absolute w-4 h-px bg-border"
                :style="{
                    left: `${(depth - 1) * 24 + 20}px`,
                    marginLeft: '1px'
                }"
            />
            
            <!-- Toggle Button -->
            <Button
                variant="ghost"
                size="sm"
                class="h-6 w-6 p-0 shrink-0"
                @click="handleToggle"
                :class="{ 
                    'invisible': !hasChildren,
                    'hover:bg-muted': hasChildren 
                }"
            >
                <component 
                    :is="toggleIcon"
                    class="h-3 w-3"
                    v-if="hasChildren"
                />
            </Button>
            
            <!-- Category Icon -->
            <component 
                :is="categoryIcon"
                class="h-4 w-4 shrink-0"
                :class="{
                    'text-blue-600': hasChildren && expanded,
                    'text-muted-foreground': !hasChildren || !expanded
                }"
            />
            
            <!-- Category Info -->
            <div class="flex-1 flex items-center gap-2 min-w-0">
                <Link 
                    :href="show(category.id).url"
                    class="font-medium truncate hover:underline"
                    :style="{ color: category.color }"
                >
                    {{ category.name }}
                </Link>
                
                <!-- Status Badge -->
                <Badge 
                    :variant="category.is_active ? 'default' : 'secondary'"
                    class="shrink-0"
                >
                    {{ category.is_active ? 'Active' : 'Inactive' }}
                </Badge>
                
                <!-- Children Count -->
                <Badge 
                    v-if="category.children_count && category.children_count > 0"
                    variant="outline"
                    class="shrink-0"
                >
                    {{ category.children_count }} child{{ category.children_count > 1 ? 'ren' : '' }}
                </Badge>
                
                <!-- Products Count -->
                <Badge 
                    v-if="category.products_count && category.products_count > 0"
                    variant="secondary"
                    class="shrink-0"
                >
                    {{ category.products_count }} product{{ category.products_count > 1 ? 's' : '' }}
                </Badge>
            </div>
            
            <!-- Actions Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button 
                        variant="ghost" 
                        size="sm" 
                        class="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <MoreHorizontal class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                    <DropdownMenuSeparator />

                    <Link :href="show(category.id).url">
                        <DropdownMenuItem>
                            <Eye class="mr-2 h-4 w-4" />
                            View
                        </DropdownMenuItem>
                    </Link>

                    <PermissionGuard permission="edit_categories">
                        <DropdownMenuItem @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </DropdownMenuItem>
                        
                        <DropdownMenuItem @click="handleMove">
                            <Move class="mr-2 h-4 w-4" />
                            Move
                        </DropdownMenuItem>
                    </PermissionGuard>

                    <PermissionGuard permission="delete_categories">
                        <DropdownMenuSeparator />
                        <DropdownMenuItem 
                            class="text-red-600 dark:text-red-400" 
                            @click="handleDelete"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </DropdownMenuItem>
                    </PermissionGuard>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
        
        <!-- Children -->
        <div v-if="expanded && hasChildren" class="space-y-1">
            <CategoryTreeNode
                v-for="child in category.children"
                :key="child.id"
                :category="child"
                :expanded="expandedNodes?.has(child.id) || false"
                :depth="depth + 1"
                :expandedNodes="expandedNodes"
                @toggle="(categoryId) => emit('toggle', categoryId)"
                @delete="(category) => emit('delete', category)"
                @edit="(category) => emit('edit', category)"
                @move="(category, newParentId) => emit('move', category, newParentId)"
            />
        </div>
    </div>
</template>

<style scoped>
/* Custom styles for tree lines */
.tree-line {
    position: relative;
}

.tree-line::before {
    content: '';
    position: absolute;
    left: -12px;
    top: 0;
    bottom: 50%;
    width: 1px;
    border-left: 1px dashed hsl(var(--border));
}

.tree-line::after {
    content: '';
    position: absolute;
    left: -12px;
    top: 50%;
    width: 12px;
    height: 1px;
    border-top: 1px dashed hsl(var(--border));
}
</style>