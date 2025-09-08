<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/routes/roles';
import type { Role } from '@/types';
import { router } from '@inertiajs/vue3';
import { AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    open: boolean;
    role?: Role & { description?: string };
    guardNames?: string[];
}

interface Emits {
    'update:open': [open: boolean];
    'role-updated': [role: Role];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Initial form state
const initialForm = {
    name: '',
    display_name: '',
    guard_name: 'web',
    description: '',
};

const form = ref({ ...initialForm });

// Watch for role prop to populate form
watch(
    () => props.role,
    (role) => {
        if (role) {
            form.value = {
                name: role.name || '',
                display_name: role.display_name || role.name || '',
                guard_name: role.guard_name || 'web',
                description: role.description || '',
            };
        }
    },
    { immediate: true },
);

// Reset form when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            // Clear errors when closing
            errors.value = {};
        }
    },
);

// Handle form submission
const handleSubmit = () => {
    if (!props.role) return;

    processing.value = true;
    errors.value = {};

    router.put(update(props.role.id).url, form.value, {
        onSuccess: (page) => {
            processing.value = false;
            emit('update:open', false);
            emit('role-updated', page.props.role as Role);
        },
        onError: (pageErrors) => {
            processing.value = false;
            errors.value = pageErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Handle cancel
const handleCancel = () => {
    emit('update:open', false);
    errors.value = {};
};

// Format guard name for display
const formatGuardName = (guard: string) => {
    return guard.charAt(0).toUpperCase() + guard.slice(1);
};

// Generate display name from role name
const generateDisplayName = () => {
    if (form.value.name && !form.value.display_name) {
        form.value.display_name = form.value.name
            .split('_')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full overflow-y-auto sm:max-w-md">
            <SheetHeader>
                <SheetTitle>Edit Role</SheetTitle>
                <SheetDescription> Update the role details below. Changes will be saved immediately. </SheetDescription>
            </SheetHeader>

            <form @submit.prevent="handleSubmit" class="mt-6 space-y-6 px-8">
                <!-- Role Name -->
                <div class="space-y-2">
                    <Label for="name" class="required">Role Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Enter role name (e.g., admin, manager)"
                        :disabled="processing"
                        @blur="generateDisplayName"
                        class="font-mono text-sm"
                    />
                    <p class="text-xs text-muted-foreground">System identifier for this role. Use lowercase with underscores.</p>
                    <div v-if="errors.name" class="flex items-center gap-2 text-sm text-destructive">
                        <AlertCircle class="h-4 w-4" />
                        {{ errors.name }}
                    </div>
                </div>

                <!-- Display Name -->
                <div class="space-y-2">
                    <Label for="display_name">Display Name</Label>
                    <Input id="display_name" v-model="form.display_name" placeholder="Enter user-friendly name" :disabled="processing" />
                    <p class="text-xs text-muted-foreground">Human-readable name shown in the interface. Auto-generated from role name if empty.</p>
                    <div v-if="errors.display_name" class="flex items-center gap-2 text-sm text-destructive">
                        <AlertCircle class="h-4 w-4" />
                        {{ errors.display_name }}
                    </div>
                </div>

                <!-- Guard Name -->
                <div class="space-y-2">
                    <Label for="guard_name" class="required">Authentication Guard</Label>
                    <Select v-model="form.guard_name" :disabled="processing">
                        <SelectTrigger>
                            <SelectValue placeholder="Select guard" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="guard in guardNames || ['web', 'api']" :key="guard" :value="guard">
                                {{ formatGuardName(guard) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Authentication context for this role. 'Web' for browser sessions, 'API' for token-based access.
                    </p>
                    <div v-if="errors.guard_name" class="flex items-center gap-2 text-sm text-destructive">
                        <AlertCircle class="h-4 w-4" />
                        {{ errors.guard_name }}
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Enter role description (optional)"
                        :disabled="processing"
                        :rows="3"
                    />
                    <p class="text-xs text-muted-foreground">Optional description explaining the purpose and scope of this role.</p>
                    <div v-if="errors.description" class="flex items-center gap-2 text-sm text-destructive">
                        <AlertCircle class="h-4 w-4" />
                        {{ errors.description }}
                    </div>
                </div>

                <!-- General Errors -->
                <div v-if="errors.general" class="flex items-center gap-2 rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                    <AlertCircle class="h-4 w-4" />
                    {{ errors.general }}
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 border-t pt-6">
                    <Button type="button" variant="outline" @click="handleCancel" :disabled="processing" class="flex-1"> Cancel </Button>
                    <Button type="submit" :disabled="processing" class="flex-1">
                        <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>

<style scoped>
.required::after {
    content: ' *';
    color: rgb(239 68 68); /* text-red-500 */
}
</style>
