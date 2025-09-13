<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AlertsContainer from '@/components/ui/AlertsContainer.vue';
import { useAlerts } from '@/composables/useAlerts';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Handle flash messages from Laravel/Inertia
const { handleFlashMessages, alerts } = useAlerts();
const page = usePage();

// Check if we have critical alerts that need backdrop
const hasCriticalAlerts = computed(() => alerts.value.some((alert) => alert.priority === 'critical'));

watch(
    () => page,
    (newPage) => {
        handleFlashMessages(newPage);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>

        <!-- Global Alerts Container -->
        <AlertsContainer :backdrop="hasCriticalAlerts" />
    </AppShell>
</template>
