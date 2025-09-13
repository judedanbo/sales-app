<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { AlertTriangle } from 'lucide-vue-next';

interface Props {
    open: boolean;
    title?: string;
    message?: string;
    itemName?: string;
    loading?: boolean;
    dangerText?: string;
}

interface Emits {
    'update:open': [open: boolean];
    'confirm': [];
    'cancel': [];
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Delete Confirmation',
    message: 'Are you sure you want to delete this item?',
    itemName: '',
    loading: false,
    dangerText: 'Delete',
});

const emit = defineEmits<Emits>();

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    emit('cancel');
    emit('update:open', false);
};

const handleOpenChange = (open: boolean) => {
    if (!props.loading) {
        emit('update:open', open);
        if (!open) {
            emit('cancel');
        }
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                        <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <DialogTitle>{{ title }}</DialogTitle>
                        <DialogDescription class="mt-1">
                            {{ message }}
                            <span v-if="itemName" class="font-medium">{{ itemName }}</span>
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>
            
            <div v-if="itemName" class="rounded-md bg-red-50 p-3 dark:bg-red-950">
                <p class="text-sm text-red-800 dark:text-red-200">
                    This action cannot be undone. This will permanently delete the selected item.
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    @click="handleCancel"
                    :disabled="loading"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    @click="handleConfirm"
                    :disabled="loading"
                    class="min-w-[80px]"
                >
                    <span v-if="loading" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path d="m12,2a10,10 0 0 1 10,10" stroke="currentColor" stroke-width="4" class="opacity-75"></path>
                        </svg>
                        Deleting...
                    </span>
                    <span v-else>{{ dangerText }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>