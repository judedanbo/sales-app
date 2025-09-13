<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Home, Lock, Search, ServerCrash, Shield } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    status: number;
    message: string;
}

const props = defineProps<Props>();

const errorConfig = computed(() => {
    switch (props.status) {
        case 403:
            return {
                title: 'Access Forbidden',
                description: "You don't have permission to access this resource.",
                icon: Lock,
                color: 'text-red-500',
                suggestion: 'Contact your administrator if you believe this is an error.',
                showContactAdmin: true,
            };
        case 404:
            return {
                title: 'Page Not Found',
                description: "The page you're looking for doesn't exist.",
                icon: Search,
                color: 'text-blue-500',
                suggestion: 'Check the URL or navigate back to a known page.',
                showContactAdmin: false,
            };
        case 500:
            return {
                title: 'Server Error',
                description: 'Something went wrong on our end.',
                icon: ServerCrash,
                color: 'text-orange-500',
                suggestion: 'Please try again later or contact support if the problem persists.',
                showContactAdmin: true,
            };
        case 503:
            return {
                title: 'Service Unavailable',
                description: 'The service is temporarily unavailable.',
                icon: AlertTriangle,
                color: 'text-yellow-500',
                suggestion: 'Please try again in a few moments.',
                showContactAdmin: false,
            };
        default:
            return {
                title: 'An Error Occurred',
                description: 'Something unexpected happened.',
                icon: Shield,
                color: 'text-gray-500',
                suggestion: 'Please try again or contact support if the problem persists.',
                showContactAdmin: true,
            };
    }
});

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/dashboard');
    }
};

const goHome = () => {
    router.visit('/dashboard');
};
</script>

<template>
    <AppLayout>
        <div class="flex min-h-[calc(100vh-200px)] items-center justify-center p-4">
            <div class="w-full max-w-md">
                <Card>
                    <CardHeader class="text-center">
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-muted">
                            <component :is="errorConfig.icon" :class="['h-10 w-10', errorConfig.color]" />
                        </div>
                        <CardTitle class="text-2xl font-bold">
                            {{ errorConfig.title }}
                        </CardTitle>
                        <CardDescription class="text-lg">
                            {{ errorConfig.description }}
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <!-- Error Details -->
                        <Alert variant="destructive">
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription>
                                <strong>Error {{ status }}:</strong> {{ message }}
                            </AlertDescription>
                        </Alert>

                        <!-- Suggestion -->
                        <div class="text-center text-sm text-muted-foreground">
                            {{ errorConfig.suggestion }}
                        </div>

                        <!-- Contact Admin Notice (for permission errors) -->
                        <Alert v-if="errorConfig.showContactAdmin && status === 403">
                            <Shield class="h-4 w-4" />
                            <AlertDescription>
                                If you believe you should have access to this resource, please contact your system administrator.
                            </AlertDescription>
                        </Alert>

                        <!-- Action Buttons -->
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Button variant="outline" @click="goBack" class="flex-1">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Go Back
                            </Button>

                            <Button @click="goHome" class="flex-1">
                                <Home class="mr-2 h-4 w-4" />
                                Dashboard
                            </Button>
                        </div>

                        <!-- Additional Help -->
                        <div class="pt-4 text-center">
                            <p class="text-xs text-muted-foreground">
                                Need help? Visit our
                                <Button variant="link" class="h-auto p-0 text-xs" @click="router.visit('/docs')"> documentation </Button>
                                or contact support.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
