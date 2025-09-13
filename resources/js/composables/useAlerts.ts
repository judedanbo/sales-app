import type { AlertVariants } from '@/components/ui/alert';
import { ref } from 'vue';

export type AlertPriority = 'low' | 'normal' | 'high' | 'critical';
export type AlertPosition = 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';

export interface Alert {
    id: string;
    title?: string;
    message: string;
    variant: AlertVariants['variant'];
    dismissible?: boolean;
    persistent?: boolean;
    priority?: AlertPriority;
    position?: AlertPosition;
    createdAt: Date;
}

// Global alert state
const alerts = ref<Alert[]>([]);
let alertIdCounter = 0;

const generateAlertId = () => `alert-${++alertIdCounter}-${Date.now()}`;

export const useAlerts = () => {
    const addAlert = (
        message: string,
        variant: AlertVariants['variant'] = 'default',
        options: {
            title?: string;
            dismissible?: boolean;
            persistent?: boolean;
            priority?: AlertPriority;
            position?: AlertPosition;
            duration?: number;
        } = {},
    ): string => {
        const id = generateAlertId();
        const alert: Alert = {
            id,
            title: options.title,
            message,
            variant,
            dismissible: options.dismissible !== false,
            persistent: options.persistent || false,
            priority: options.priority || 'normal',
            position: options.position || 'top-right',
            createdAt: new Date(),
        };

        alerts.value.push(alert);

        // Auto-dismiss non-persistent alerts after duration
        if (!options.persistent && options.duration !== 0) {
            const duration = options.duration || 5000;
            setTimeout(() => {
                removeAlert(id);
            }, duration);
        }

        return id;
    };

    const removeAlert = (id: string) => {
        const index = alerts.value.findIndex((alert) => alert.id === id);
        if (index !== -1) {
            alerts.value.splice(index, 1);
        }
    };

    const clearAlerts = (variant?: AlertVariants['variant']) => {
        if (variant) {
            alerts.value = alerts.value.filter((alert) => alert.variant !== variant);
        } else {
            alerts.value = [];
        }
    };

    const hasAlerts = (variant?: AlertVariants['variant']) => {
        if (variant) {
            return alerts.value.some((alert) => alert.variant === variant);
        }
        return alerts.value.length > 0;
    };

    const getAlerts = (variant?: AlertVariants['variant']) => {
        if (variant) {
            return alerts.value.filter((alert) => alert.variant === variant);
        }
        return alerts.value;
    };

    // Convenience methods for different alert types
    const success = (
        message: string,
        options?: {
            title?: string;
            dismissible?: boolean;
            persistent?: boolean;
            priority?: AlertPriority;
            position?: AlertPosition;
            duration?: number;
        },
    ) => {
        return addAlert(message, 'success', options);
    };

    const error = (
        message: string,
        options?: {
            title?: string;
            dismissible?: boolean;
            persistent?: boolean;
            priority?: AlertPriority;
            position?: AlertPosition;
            duration?: number;
        },
    ) => {
        return addAlert(message, 'destructive', options);
    };

    const warning = (
        message: string,
        options?: {
            title?: string;
            dismissible?: boolean;
            persistent?: boolean;
            priority?: AlertPriority;
            position?: AlertPosition;
            duration?: number;
        },
    ) => {
        return addAlert(message, 'warning', options);
    };

    const info = (
        message: string,
        options?: {
            title?: string;
            dismissible?: boolean;
            persistent?: boolean;
            priority?: AlertPriority;
            position?: AlertPosition;
            duration?: number;
        },
    ) => {
        return addAlert(message, 'info', options);
    };

    // Convenience methods for priority-based alerts
    const critical = (
        message: string,
        options?: { title?: string; dismissible?: boolean; persistent?: boolean; position?: AlertPosition; duration?: number },
    ) => {
        return addAlert(message, 'destructive', { ...options, priority: 'critical' });
    };

    const urgent = (
        message: string,
        options?: { title?: string; dismissible?: boolean; persistent?: boolean; position?: AlertPosition; duration?: number },
    ) => {
        return addAlert(message, 'warning', { ...options, priority: 'high' });
    };

    // Handle Laravel flash messages from Inertia
    const handleFlashMessages = (page: any) => {
        if (page.props?.flash) {
            const flash = page.props.flash;

            if (flash.success) {
                success(flash.success);
            }
            if (flash.error) {
                error(flash.error);
            }
            if (flash.warning) {
                warning(flash.warning);
            }
            if (flash.info) {
                info(flash.info);
            }
            if (flash.message) {
                addAlert(flash.message);
            }
        }
    };

    return {
        alerts: alerts,
        addAlert,
        removeAlert,
        clearAlerts,
        hasAlerts,
        getAlerts,
        success,
        error,
        warning,
        info,
        critical,
        urgent,
        handleFlashMessages,
    };
};
