<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { PaginatedData, School } from '@/types';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface Props {
    schools: PaginatedData<School>;
    isLoading?: boolean;
}

interface Emits {
    (e: 'page-change', page: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function goToPage(page: number) {
    emit('page-change', page);
}
</script>

<template>
    <div v-if="schools.last_page > 1" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-muted-foreground">Page {{ schools.current_page }} of {{ schools.last_page }}</div>
        <div class="flex gap-2">
            <Button v-if="schools.current_page > 1" variant="outline" size="sm" @click="goToPage(schools.current_page - 1)" :disabled="isLoading">
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <ChevronLeft class="h-4 w-4" />
                Previous
            </Button>

            <Button
                v-if="schools.current_page < schools.last_page"
                variant="outline"
                size="sm"
                @click="goToPage(schools.current_page + 1)"
                :disabled="isLoading"
            >
                Next
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                Next
                <ChevronRight class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
