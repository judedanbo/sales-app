<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TimeBasedAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $schedule = 'business_hours', string $timezone = 'UTC'): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();

        // Check if access is allowed based on time restrictions
        if (! $this->isAccessAllowed($user, $schedule, $timezone)) {
            return $this->timeRestricted($request, $schedule, $timezone);
        }

        return $next($request);
    }

    /**
     * Check if access is allowed based on time restrictions.
     */
    private function isAccessAllowed($user, string $schedule, string $timezone): bool
    {
        // Super Admins can always override time restrictions
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check for emergency override
        if ($this->hasEmergencyOverride($user)) {
            return true;
        }

        // Check schedule-based access
        return $this->checkScheduleAccess($schedule, $timezone);
    }

    /**
     * Check schedule-based access restrictions.
     */
    private function checkScheduleAccess(string $schedule, string $timezone): bool
    {
        $now = Carbon::now($timezone);

        switch ($schedule) {
            case 'business_hours':
                return $this->isBusinessHours($now);
            case 'extended_hours':
                return $this->isExtendedHours($now);
            case 'maintenance_window':
                return ! $this->isMaintenanceWindow($now);
            case 'emergency_only':
                return false; // Only emergency overrides allowed
            case 'weekdays_only':
                return $this->isWeekday($now);
            case 'school_hours':
                return $this->isSchoolHours($now);
            default:
                return true; // Default to allowing access
        }
    }

    /**
     * Check if current time is within business hours (9 AM - 6 PM, Mon-Fri).
     */
    private function isBusinessHours(Carbon $now): bool
    {
        return $now->isWeekday() &&
               $now->hour >= 9 &&
               $now->hour < 18;
    }

    /**
     * Check if current time is within extended hours (7 AM - 10 PM, Mon-Sat).
     */
    private function isExtendedHours(Carbon $now): bool
    {
        return ! $now->isSunday() &&
               $now->hour >= 7 &&
               $now->hour < 22;
    }

    /**
     * Check if current time is within maintenance window (2 AM - 4 AM daily).
     */
    private function isMaintenanceWindow(Carbon $now): bool
    {
        return $now->hour >= 2 && $now->hour < 4;
    }

    /**
     * Check if current day is a weekday.
     */
    private function isWeekday(Carbon $now): bool
    {
        return $now->isWeekday();
    }

    /**
     * Check if current time is within school hours (8 AM - 4 PM, Mon-Fri).
     */
    private function isSchoolHours(Carbon $now): bool
    {
        return $now->isWeekday() &&
               $now->hour >= 8 &&
               $now->hour < 16;
    }

    /**
     * Check if user has an active emergency override.
     */
    private function hasEmergencyOverride($user): bool
    {
        $cacheKey = "emergency_override:{$user->id}";
        $override = Cache::get($cacheKey);

        if (! $override) {
            return false;
        }

        // Check if override is still valid
        $expiresAt = Carbon::parse($override['expires_at']);
        if ($expiresAt->isPast()) {
            Cache::forget($cacheKey);

            return false;
        }

        // Log override usage
        Log::channel('audit')->info('Emergency Override Used', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'override_reason' => $override['reason'],
            'granted_by' => $override['granted_by'],
            'expires_at' => $override['expires_at'],
            'timestamp' => now()->toISOString(),
        ]);

        return true;
    }

    /**
     * Grant emergency override to a user.
     */
    public static function grantEmergencyOverride(int $userId, string $reason, int $durationHours = 2, ?int $grantedBy = null): void
    {
        $cacheKey = "emergency_override:{$userId}";
        $expiresAt = now()->addHours($durationHours);

        $override = [
            'reason' => $reason,
            'granted_by' => $grantedBy ?: Auth::id(),
            'granted_at' => now()->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
        ];

        Cache::put($cacheKey, $override, $expiresAt);

        Log::channel('audit')->warning('Emergency Override Granted', [
            'target_user_id' => $userId,
            'granted_by' => $grantedBy ?: Auth::id(),
            'reason' => $reason,
            'duration_hours' => $durationHours,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    /**
     * Revoke emergency override for a user.
     */
    public static function revokeEmergencyOverride(int $userId, string $reason = 'Manual revocation'): void
    {
        $cacheKey = "emergency_override:{$userId}";
        Cache::forget($cacheKey);

        Log::channel('audit')->info('Emergency Override Revoked', [
            'target_user_id' => $userId,
            'revoked_by' => Auth::id(),
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if system is in maintenance mode.
     */
    private function isMaintenanceMode(): bool
    {
        return app()->isDownForMaintenance();
    }

    /**
     * Get user-friendly time restriction message.
     */
    private function getTimeRestrictionMessage(string $schedule, string $timezone): string
    {
        $now = Carbon::now($timezone);

        switch ($schedule) {
            case 'business_hours':
                return "Access restricted to business hours (9 AM - 6 PM, Monday-Friday, {$timezone}). Current time: {$now->format('l, F j, Y g:i A T')}.";
            case 'extended_hours':
                return "Access restricted to extended hours (7 AM - 10 PM, Monday-Saturday, {$timezone}). Current time: {$now->format('l, F j, Y g:i A T')}.";
            case 'maintenance_window':
                return "System is currently in maintenance mode (2 AM - 4 AM daily, {$timezone}). Please try again later.";
            case 'emergency_only':
                return 'System is in emergency-only mode. Contact your administrator for emergency access.';
            case 'weekdays_only':
                return "Access restricted to weekdays only ({$timezone}). Current day: {$now->format('l')}.";
            case 'school_hours':
                return "Access restricted to school hours (8 AM - 4 PM, Monday-Friday, {$timezone}). Current time: {$now->format('l, F j, Y g:i A T')}.";
            default:
                return 'Access restricted due to time-based policy.';
        }
    }

    /**
     * Handle time-restricted access.
     */
    private function timeRestricted(Request $request, string $schedule, string $timezone): Response
    {
        $message = $this->getTimeRestrictionMessage($schedule, $timezone);

        // Log time restriction attempt
        Log::channel('audit')->info('Time-Based Access Denied', [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email,
            'schedule' => $schedule,
            'timezone' => $timezone,
            'attempted_url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Time Restriction',
                'code' => 'TIME_RESTRICTED',
                'retry_after' => $this->getRetryAfter($schedule, $timezone),
            ], 423); // 423 Locked
        }

        if ($request->header('X-Inertia')) {
            return inertia('Error', [
                'status' => 423,
                'message' => $message,
                'code' => 'TIME_RESTRICTED',
                'retry_after' => $this->getRetryAfter($schedule, $timezone),
            ], 423)->toResponse($request);
        }

        abort(423, $message);
    }

    /**
     * Calculate when access will be available again.
     */
    private function getRetryAfter(string $schedule, string $timezone): ?string
    {
        $now = Carbon::now($timezone);

        switch ($schedule) {
            case 'business_hours':
                if ($now->isWeekend()) {
                    return $now->next(Carbon::MONDAY)->setTime(9, 0)->toISOString();
                }
                if ($now->hour < 9) {
                    return $now->setTime(9, 0)->toISOString();
                }

                return $now->addDay()->setTime(9, 0)->toISOString();

            case 'weekdays_only':
                return $now->next(Carbon::MONDAY)->toISOString();

            case 'maintenance_window':
                return $now->setTime(4, 0)->toISOString();

            default:
                return null;
        }
    }

    /**
     * Handle unauthorized access.
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Unauthorized',
            ], 401);
        }

        if ($request->header('X-Inertia')) {
            return inertia('Error', [
                'status' => 401,
                'message' => $message,
            ], 401)->toResponse($request);
        }

        abort(401, $message);
    }
}
