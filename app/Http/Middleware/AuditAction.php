<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $actionType = 'general'): Response
    {
        $startTime = microtime(true);
        $user = Auth::user();

        // Pre-action logging
        $this->logPreAction($request, $user, $actionType);

        $response = $next($request);

        // Post-action logging
        $this->logPostAction($request, $response, $user, $actionType, $startTime);

        return $response;
    }

    /**
     * Log action before execution.
     */
    private function logPreAction(Request $request, $user, string $actionType): void
    {
        $logData = [
            'action_type' => $actionType,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route()?->getName(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->getRoleNames(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
        ];

        // Add request data for sensitive operations
        if ($this->isSensitiveAction($actionType)) {
            $logData['request_data'] = $this->filterSensitiveData($request->all());
            $logData['route_parameters'] = $request->route()?->parameters();
        }

        Log::channel('audit')->info('Action Initiated', $logData);

        // Real-time alerting for critical actions
        if ($this->isCriticalAction($actionType)) {
            $this->sendRealTimeAlert($logData);
        }
    }

    /**
     * Log action after execution.
     */
    private function logPostAction(Request $request, Response $response, $user, string $actionType, float $startTime): void
    {
        $executionTime = microtime(true) - $startTime;

        $logData = [
            'action_type' => $actionType,
            'user_id' => $user?->id,
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'success' => $response->isSuccessful(),
            'timestamp' => now()->toISOString(),
        ];

        // Add response data for failed operations
        if (! $response->isSuccessful()) {
            $logData['error_details'] = $this->extractErrorDetails($response);
        }

        // Track bulk operation details
        if ($this->isBulkOperation($actionType)) {
            $logData['bulk_details'] = $this->extractBulkOperationDetails($request, $response);
        }

        Log::channel('audit')->info('Action Completed', $logData);

        // Alert on suspicious activity
        if ($this->isSuspiciousActivity($logData)) {
            $this->alertSuspiciousActivity($logData);
        }
    }

    /**
     * Determine if action is sensitive and requires detailed logging.
     */
    private function isSensitiveAction(string $actionType): bool
    {
        $sensitiveActions = [
            'user_management', 'role_assignment', 'permission_change',
            'bulk_operation', 'data_export', 'system_config',
            'profile_update', 'password_change',
        ];

        return in_array($actionType, $sensitiveActions);
    }

    /**
     * Determine if action is critical and requires real-time alerts.
     */
    private function isCriticalAction(string $actionType): bool
    {
        $criticalActions = [
            'critical', 'super_admin_action', 'bulk_delete',
            'permission_escalation', 'system_shutdown',
        ];

        return in_array($actionType, $criticalActions);
    }

    /**
     * Check if this is a bulk operation.
     */
    private function isBulkOperation(string $actionType): bool
    {
        return str_contains($actionType, 'bulk') || str_contains($actionType, 'batch');
    }

    /**
     * Filter sensitive data from request.
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'key'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * Extract error details from response.
     */
    private function extractErrorDetails(Response $response): array
    {
        $content = $response->getContent();

        if ($response->headers->get('content-type') === 'application/json') {
            $decoded = json_decode($content, true);

            return [
                'message' => $decoded['message'] ?? 'Unknown error',
                'errors' => $decoded['errors'] ?? [],
            ];
        }

        return ['raw_content' => substr($content, 0, 500)];
    }

    /**
     * Extract bulk operation details.
     */
    private function extractBulkOperationDetails(Request $request, Response $response): array
    {
        $requestData = $request->all();

        return [
            'items_count' => count($requestData['ids'] ?? $requestData['items'] ?? []),
            'operation_type' => $requestData['operation'] ?? 'unknown',
            'affected_resources' => $requestData['resource_type'] ?? 'unknown',
        ];
    }

    /**
     * Detect suspicious activity patterns.
     */
    private function isSuspiciousActivity(array $logData): bool
    {
        // Multiple failed attempts
        if (! $logData['success'] && $logData['status_code'] === 403) {
            return true;
        }

        // Unusually fast operations (possible automation)
        if ($logData['execution_time_ms'] < 10 && $this->isSensitiveAction($logData['action_type'])) {
            return true;
        }

        return false;
    }

    /**
     * Send real-time alert for critical actions.
     */
    private function sendRealTimeAlert(array $logData): void
    {
        // This would integrate with your alerting system
        Log::channel('security')->critical('Critical Action Detected', $logData);

        // Could send to Slack, email, SMS, etc.
        // event(new CriticalActionEvent($logData));
    }

    /**
     * Alert about suspicious activity.
     */
    private function alertSuspiciousActivity(array $logData): void
    {
        Log::channel('security')->warning('Suspicious Activity Detected', $logData);

        // Could trigger additional security measures
        // event(new SuspiciousActivityEvent($logData));
    }
}
