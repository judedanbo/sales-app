<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import Switch from '@/components/ui/switch.vue';
import { Textarea } from '@/components/ui/textarea';
import type { Role, School, User, UserTypeOption } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    open: boolean;
    user?: User;
    userTypes: UserTypeOption[];
    schools: School[];
    roles: Role[];
}

interface Emits {
    'update:open': [open: boolean];
    'user-created': [user: User];
    'user-updated': [user: User];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Initial form state
const initialForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    user_type: 'staff' as const,
    school_id: undefined as number | undefined,
    phone: '',
    department: '',
    bio: '',
    is_active: true,
    role_ids: [] as number[],
};

const form = ref({ ...initialForm });
const isEditing = ref(false);

// Watch for user prop to determine if editing
watch(
    () => props.user,
    (user) => {
        isEditing.value = !!user;
        if (user) {
            form.value = {
                name: user.name || '',
                email: user.email || '',
                password: '',
                password_confirmation: '',
                user_type: user.user_type || 'staff',
                school_id: user.school_id || undefined,
                phone: user.phone || '',
                department: user.department || '',
                bio: user.bio || '',
                is_active: user.is_active,
                role_ids: user.roles?.map((role) => role.id) || [],
            };
        }
    },
    { immediate: true },
);

// Reset form when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && !props.user) {
            // Reset form when opening for creation
            form.value = { ...initialForm };
            errors.value = {};
            isEditing.value = false;
        } else if (!isOpen) {
            // Clear errors when closing
            errors.value = {};
        }
    },
);

// Filter schools based on user type
const availableSchools = ref(props.schools);

watch(
    () => form.value.user_type,
    (userType) => {
        if (['school_admin', 'principal', 'teacher'].includes(userType)) {
            availableSchools.value = props.schools;
        } else {
            availableSchools.value = props.schools;
            if (!['school_admin', 'principal', 'teacher'].includes(userType)) {
                form.value.school_id = undefined;
            }
        }
    },
);

const handleSubmit = () => {
    processing.value = true;
    errors.value = {};

    const submitData = { ...form.value };

    // Remove password fields if editing and password is empty
    if (isEditing.value && !submitData.password) {
        delete submitData.password;
        delete submitData.password_confirmation;
    }

    const url = isEditing.value ? `/users/${props.user?.id}` : '/users';
    const method = isEditing.value ? 'patch' : 'post';

    router[method](url, submitData, {
        onSuccess: (page) => {
            const user = page.props.user || page.props.users?.data?.[0];
            if (isEditing.value) {
                emit('user-updated', user);
            } else {
                emit('user-created', user);
            }
            emit('update:open', false);
            if (!isEditing.value) {
                form.value = { ...initialForm };
            }
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleCancel = () => {
    emit('update:open', false);
    if (!isEditing.value) {
        form.value = { ...initialForm };
    }
    errors.value = {};
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
};

const toggleRole = (roleId: number) => {
    const index = form.value.role_ids.indexOf(roleId);
    if (index > -1) {
        form.value.role_ids.splice(index, 1);
    } else {
        form.value.role_ids.push(roleId);
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="handleOpenChange">
        <SheetContent class="w-[600px] overflow-y-auto sm:max-w-[600px]">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit User' : 'Add New User' }}</SheetTitle>
                <SheetDescription>
                    {{ isEditing ? 'Update user information and permissions' : 'Create a new user with appropriate roles and permissions' }}
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 px-8">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="name">Full Name *</Label>
                                <Input id="name" v-model="form.name" placeholder="Enter full name" :class="{ 'border-destructive': errors.name }" />
                                <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email Address *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    :class="{ 'border-destructive': errors.email }"
                                />
                                <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
                            </div>
                        </div>

                        <!-- Password fields (only shown when creating or if editing and password is being changed) -->
                        <div v-if="!isEditing" class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="password">Password *</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Enter password"
                                    :class="{ 'border-destructive': errors.password }"
                                />
                                <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation">Confirm Password *</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Confirm password"
                                    :class="{ 'border-destructive': errors.password_confirmation }"
                                />
                                <p v-if="errors.password_confirmation" class="text-sm text-destructive">{{ errors.password_confirmation }}</p>
                            </div>
                        </div>

                        <div v-if="isEditing" class="space-y-2">
                            <Label>Password</Label>
                            <p class="text-sm text-muted-foreground">Leave blank to keep current password</p>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Input
                                        v-model="form.password"
                                        type="password"
                                        placeholder="New password"
                                        :class="{ 'border-destructive': errors.password }"
                                    />
                                    <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirm new password"
                                        :class="{ 'border-destructive': errors.password_confirmation }"
                                    />
                                    <p v-if="errors.password_confirmation" class="text-sm text-destructive">{{ errors.password_confirmation }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- User Type and School -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="user_type">User Type *</Label>
                                <select
                                    id="user_type"
                                    v-model="form.user_type"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    :class="{ 'border-destructive': errors.user_type }"
                                >
                                    <option v-for="type in userTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p v-if="errors.user_type" class="text-sm text-destructive">{{ errors.user_type }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="school_id">School</Label>
                                <select
                                    id="school_id"
                                    v-model="form.school_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    :class="{ 'border-destructive': errors.school_id }"
                                >
                                    <option :value="undefined">Select a school</option>
                                    <option v-for="school in availableSchools" :key="school.id" :value="school.id">
                                        {{ school.school_name }}
                                    </option>
                                </select>
                                <p v-if="errors.school_id" class="text-sm text-destructive">{{ errors.school_id }}</p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="phone">Phone Number</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="Enter phone number"
                                    :class="{ 'border-destructive': errors.phone }"
                                />
                                <p v-if="errors.phone" class="text-sm text-destructive">{{ errors.phone }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="department">Department</Label>
                                <Input
                                    id="department"
                                    v-model="form.department"
                                    placeholder="Enter department"
                                    :class="{ 'border-destructive': errors.department }"
                                />
                                <p v-if="errors.department" class="text-sm text-destructive">{{ errors.department }}</p>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div class="space-y-2">
                            <Label for="bio">Bio</Label>
                            <Textarea id="bio" v-model="form.bio" placeholder="Enter user bio..." :class="{ 'border-destructive': errors.bio }" />
                            <p v-if="errors.bio" class="text-sm text-destructive">{{ errors.bio }}</p>
                        </div>

                        <!-- Roles -->
                        <div class="space-y-2">
                            <Label>Roles</Label>
                            <div class="grid max-h-40 gap-2 overflow-y-auto rounded-md border p-2">
                                <div v-for="role in roles" :key="role.id" class="flex items-center space-x-2">
                                    <input
                                        :id="`role-${role.id}`"
                                        type="checkbox"
                                        :checked="form.role_ids.includes(role.id)"
                                        @change="toggleRole(role.id)"
                                        class="rounded border-input"
                                    />
                                    <Label :for="`role-${role.id}`" class="text-sm font-normal">
                                        {{ role.display_name || role.name }}
                                        <span v-if="role.permissions_count" class="text-xs text-muted-foreground">
                                            ({{ role.permissions_count }} permissions)
                                        </span>
                                    </Label>
                                </div>
                            </div>
                            <p v-if="errors.role_ids" class="text-sm text-destructive">{{ errors.role_ids }}</p>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center space-x-2">
                            <Switch id="is_active" v-model="form.is_active" />
                            <Label for="is_active">Active</Label>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <Button type="button" variant="outline" @click="handleCancel" :disabled="processing"> Cancel </Button>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? (isEditing ? 'Updating...' : 'Creating...') : isEditing ? 'Update User' : 'Create User' }}
                        </Button>
                    </div>
                </form>
            </div>
        </SheetContent>
    </Sheet>
</template>
