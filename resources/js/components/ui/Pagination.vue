<template>
    <div v-if="lastPage > 1" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
            Page {{ currentPage }} of {{ lastPage }}
        </div>
        <div class="flex gap-2">
            <Button 
                v-if="currentPage > 1" 
                variant="outline" 
                size="sm"
                @click="$emit('page-change', currentPage - 1)"
                :disabled="disabled"
                class="cursor-pointer"
            >
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Button>
            <Button v-else variant="outline" size="sm" class="cursor-pointer" disabled>
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Button>

            <Button 
                v-if="currentPage < lastPage" 
                variant="outline" 
                size="sm"
                @click="$emit('page-change', currentPage + 1)"
                :disabled="disabled"
                class="cursor-pointer"
            >
                Next
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button v-else variant="outline" size="sm" class="cursor-pointer" disabled>
                Next
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface Props {
    currentPage: number
    lastPage: number
    disabled?: boolean
}

defineProps<Props>()

defineEmits<{
    'page-change': [page: number]
}>()
</script>