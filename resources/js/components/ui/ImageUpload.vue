<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useAlerts } from '@/composables/useAlerts';
import { router } from '@inertiajs/vue3';
import { Camera, Loader2, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    currentImage?: string;
    uploadUrl: string;
    deleteUrl?: string;
    alt?: string;
    maxSize?: number; // in MB
    acceptedTypes?: string[];
    disabled?: boolean;
    aspectRatio?: 'square' | 'wide' | 'tall';
}

interface Emits {
    uploaded: [imageUrl: string];
    deleted: [];
}

const props = withDefaults(defineProps<Props>(), {
    maxSize: 2,
    acceptedTypes: () => ['image/jpeg', 'image/png', 'image/webp'],
    aspectRatio: 'square',
});

const emit = defineEmits<Emits>();

const { success, error } = useAlerts();

const isDragOver = ref(false);
const isUploading = ref(false);
const isDeleting = ref(false);
const previewUrl = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Computed properties
const aspectRatioClass = computed(() => {
    switch (props.aspectRatio) {
        case 'wide':
            return 'aspect-video';
        case 'tall':
            return 'aspect-[3/4]';
        default:
            return 'aspect-square';
    }
});

const acceptedTypesString = computed(() => props.acceptedTypes.join(','));

const hasCurrentImage = computed(() => !!props.currentImage);

const displayImage = computed(() => previewUrl.value || props.currentImage);

const maxSizeBytes = computed(() => props.maxSize * 1024 * 1024);

// Event handlers
const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isDragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isDragOver.value = false;
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isDragOver.value = false;

    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        handleFileSelect(files[0]);
    }
};

const handleFileInputChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        handleFileSelect(target.files[0]);
    }
};

const handleFileSelect = (file: File) => {
    // Validate file type
    if (!props.acceptedTypes.includes(file.type)) {
        error(`Invalid file type. Please select a ${props.acceptedTypes.join(', ')} file.`);
        return;
    }

    // Validate file size
    if (file.size > maxSizeBytes.value) {
        error(`File size too large. Please select a file smaller than ${props.maxSize}MB.`);
        return;
    }

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    // Upload file
    uploadFile(file);
};

const uploadFile = (file: File) => {
    if (props.disabled || isUploading.value) return;

    isUploading.value = true;

    const formData = new FormData();
    formData.append('image', file);

    router.post(props.uploadUrl, formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Extract image URL from response
            const responseData = page.props as any;
            const imageUrl = responseData?.imageUrl || responseData?.product?.image_url;

            if (imageUrl) {
                success('Image uploaded successfully!');
                emit('uploaded', imageUrl);
            } else {
                success('Image uploaded successfully!');
            }
        },
        onError: (errors) => {
            previewUrl.value = null;
            const errorMessage = Object.values(errors).flat().join(', ');
            error(errorMessage || 'Failed to upload image. Please try again.');
        },
        onFinish: () => {
            isUploading.value = false;
            // Clear file input
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
    });
};

const handleDeleteImage = () => {
    if (!props.deleteUrl || props.disabled || isDeleting.value) return;

    isDeleting.value = true;

    router.delete(props.deleteUrl, {
        preserveScroll: true,
        onSuccess: () => {
            success('Image deleted successfully!');
            previewUrl.value = null;
            emit('deleted');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            error(errorMessage || 'Failed to delete image. Please try again.');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const openFileDialog = () => {
    if (props.disabled || isUploading.value) return;
    fileInputRef.value?.click();
};

const clearPreview = () => {
    previewUrl.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    console.error('Image failed to load:', img.src);
    error('Failed to load image. Please check the image URL.');
};

const handleImageLoad = () => {
    // Image loaded successfully - no action needed
};
</script>

<template>
    <div class="space-y-4">
        <!-- Upload Area -->
        <div
            class="relative overflow-hidden rounded-lg border-2 border-dashed transition-colors"
            :class="[
                aspectRatioClass,
                isDragOver
                    ? 'border-primary bg-primary/5'
                    : displayImage
                      ? 'border-muted'
                      : 'border-muted-foreground/25 hover:border-muted-foreground/50',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
            ]"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            @click="!displayImage && openFileDialog()"
        >
            <!-- Current/Preview Image -->
            <div v-if="displayImage" class="relative h-full w-full">
                <img
                    :src="displayImage"
                    :alt="alt || 'Product image'"
                    class="h-full w-full object-cover"
                    @error="handleImageError"
                    @load="handleImageLoad"
                />

                <!-- Loading Overlay -->
                <div
                    v-if="isUploading"
                    class="absolute inset-0 flex items-center justify-center bg-black/50"
                >
                    <div class="flex flex-col items-center gap-2 text-white">
                        <Loader2 class="h-6 w-6 animate-spin" />
                        <span class="text-sm">Uploading...</span>
                    </div>
                </div>

                <!-- Image Actions -->
                <div
                    v-else
                    class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity hover:opacity-100"
                >
                    <Button size="sm" variant="secondary" @click.stop="openFileDialog" :disabled="disabled">
                        <Camera class="h-4 w-4" />
                    </Button>

                    <Button
                        v-if="previewUrl"
                        size="sm"
                        variant="secondary"
                        @click.stop="clearPreview"
                        :disabled="disabled"
                    >
                        <X class="h-4 w-4" />
                    </Button>

                    <Button
                        v-if="hasCurrentImage && deleteUrl"
                        size="sm"
                        variant="destructive"
                        @click.stop="handleDeleteImage"
                        :disabled="disabled || isDeleting"
                    >
                        <Loader2 v-if="isDeleting" class="h-4 w-4 animate-spin" />
                        <Trash2 v-else class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Upload Prompt -->
            <div v-else class="flex h-full items-center justify-center p-6">
                <div class="text-center">
                    <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ isDragOver ? 'Drop image here' : 'Click to upload or drag and drop' }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        PNG, JPG, WebP up to {{ maxSize }}MB
                    </p>
                </div>
            </div>

            <!-- Loading Overlay for Upload Area -->
            <div
                v-if="isUploading && !displayImage"
                class="absolute inset-0 flex items-center justify-center bg-muted/50"
            >
                <div class="flex flex-col items-center gap-2">
                    <Loader2 class="h-6 w-6 animate-spin" />
                    <span class="text-sm text-muted-foreground">Uploading...</span>
                </div>
            </div>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInputRef"
            type="file"
            :accept="acceptedTypesString"
            class="hidden"
            @change="handleFileInputChange"
            :disabled="disabled || isUploading"
        />

        <!-- Upload Button (when no image) -->
        <Button
            v-if="!displayImage"
            variant="outline"
            class="w-full"
            @click="openFileDialog"
            :disabled="disabled || isUploading"
        >
            <Upload class="mr-2 h-4 w-4" />
            Choose Image
        </Button>
    </div>
</template>