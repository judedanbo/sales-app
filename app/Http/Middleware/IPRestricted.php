<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IPRestricted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'strict'): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();
        $clientIP = $this->getClientIP($request);

        // Check IP restrictions
        if (! $this->isIPAllowed($clientIP, $user, $mode)) {
            return $this->ipRestricted($request, $clientIP, $mode);
        }

        return $next($request);
    }

    /**
     * Get the real client IP address.
     */
    private function getClientIP(Request $request): string
    {
        // Check for IP from various headers (for load balancers, proxies, etc.)
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_FORWARDED',          // Alternative proxy header
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster load balancer
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'HTTP_CLIENT_IP',            // Rare but possible
            'REMOTE_ADDR',                // Standard CGI variable
        ];

        foreach ($ipHeaders as $header) {
            $value = $request->server($header);
            if (! empty($value)) {
                // Handle comma-separated values (multiple proxies)
                $ips = explode(',', $value);
                $ip = trim($ips[0]);

                if ($this->isValidIP($ip)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?? 'unknown';
    }

    /**
     * Validate IP address format.
     */
    private function isValidIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Check if IP is allowed based on restrictions.
     */
    private function isIPAllowed(string $clientIP, $user, string $mode): bool
    {
        // Super Admins can bypass IP restrictions in emergency mode
        if ($user->hasRole('Super Admin') && $this->hasEmergencyBypass($user)) {
            return true;
        }

        switch ($mode) {
            case 'strict':
                return $this->isIPInWhitelist($clientIP, $user);
            case 'office_only':
                return $this->isOfficeIP($clientIP);
            case 'country_restricted':
                return $this->isAllowedCountry($clientIP);
            case 'vpn_blocked':
                return ! $this->isVPNConnection($clientIP);
            case 'dynamic':
                return $this->isDynamicIPAllowed($clientIP, $user);
            default:
                return true;
        }
    }

    /**
     * Check if IP is in user's whitelist.
     */
    private function isIPInWhitelist(string $clientIP, $user): bool
    {
        // Get user-specific whitelist
        $userWhitelist = $this->getUserIPWhitelist($user);

        // Get global admin whitelist
        $globalWhitelist = $this->getGlobalIPWhitelist();

        $allWhitelisted = array_merge($userWhitelist, $globalWhitelist);

        foreach ($allWhitelisted as $allowedIP) {
            if ($this->matchesIPPattern($clientIP, $allowedIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is from an allowed office location.
     */
    private function isOfficeIP(string $clientIP): bool
    {
        $officeRanges = [
            '192.168.1.0/24',    // Office network
            '10.0.0.0/16',       // Internal network
            '172.16.0.0/12',     // Private network
            // Add your actual office IP ranges
        ];

        foreach ($officeRanges as $range) {
            if ($this->isIPInRange($clientIP, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is from an allowed country.
     */
    private function isAllowedCountry(string $clientIP): bool
    {
        $allowedCountries = ['US', 'CA', 'GB', 'AU']; // Configure as needed
        $country = $this->getIPCountry($clientIP);

        return in_array($country, $allowedCountries);
    }

    /**
     * Detect if connection is through VPN/Proxy.
     */
    private function isVPNConnection(string $clientIP): bool
    {
        // Cache VPN detection results
        $cacheKey = "vpn_check:{$clientIP}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $isVPN = $this->detectVPNProvider($clientIP) || $this->detectProxyHeaders();

        // Cache result for 1 hour
        Cache::put($cacheKey, $isVPN, 3600);

        return $isVPN;
    }

    /**
     * Check dynamic IP allowance based on user behavior.
     */
    private function isDynamicIPAllowed(string $clientIP, $user): bool
    {
        // Allow if user has used this IP recently
        $recentIPs = $this->getUserRecentIPs($user);
        if (in_array($clientIP, $recentIPs)) {
            return true;
        }

        // Allow if IP is in the same geographic region as user's previous IPs
        if ($this->isSameRegion($clientIP, $recentIPs)) {
            $this->addToUserRecentIPs($user, $clientIP);

            return true;
        }

        return false;
    }

    /**
     * Check if IP matches a pattern (supports CIDR notation).
     */
    private function matchesIPPattern(string $clientIP, string $pattern): bool
    {
        if (strpos($pattern, '/') !== false) {
            return $this->isIPInRange($clientIP, $pattern);
        }

        return $clientIP === $pattern;
    }

    /**
     * Check if IP is in CIDR range.
     */
    private function isIPInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Get user's IP whitelist.
     */
    private function getUserIPWhitelist($user): array
    {
        // This could be stored in user settings, database, etc.
        $cacheKey = "ip_whitelist:user:{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            // Get from user settings or database
            return $user->ip_whitelist ?? [];
        });
    }

    /**
     * Get global admin IP whitelist.
     */
    private function getGlobalIPWhitelist(): array
    {
        return Cache::remember('global_ip_whitelist', 3600, function () {
            return config('security.admin_ip_whitelist', []);
        });
    }

    /**
     * Get country code for IP address.
     */
    private function getIPCountry(string $ip): ?string
    {
        $cacheKey = "ip_country:{$ip}";

        return Cache::remember($cacheKey, 86400, function () {
            // This would use a GeoIP service
            // For now, return null (could integrate with MaxMind, IPinfo, etc.)
            return null;
        });
    }

    /**
     * Detect known VPN providers.
     */
    private function detectVPNProvider(string $ip): bool
    {
        // This would check against known VPN provider IP ranges
        // Could integrate with commercial VPN detection services
        return false;
    }

    /**
     * Detect proxy headers that indicate VPN/Proxy usage.
     */
    private function detectProxyHeaders(): bool
    {
        $request = request();

        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR_IP',
            'VIA',
            'X_FORWARDED_FOR',
            'FORWARDED_FOR',
            'X_FORWARDED',
            'FORWARDED',
            'CLIENT_IP',
            'FORWARDED_FOR_IP',
        ];

        foreach ($proxyHeaders as $header) {
            if ($request->server($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's recent IP addresses.
     */
    private function getUserRecentIPs($user): array
    {
        $cacheKey = "recent_ips:user:{$user->id}";

        return Cache::get($cacheKey, []);
    }

    /**
     * Add IP to user's recent IPs list.
     */
    private function addToUserRecentIPs($user, string $ip): void
    {
        $cacheKey = "recent_ips:user:{$user->id}";
        $recentIPs = Cache::get($cacheKey, []);

        // Add new IP and keep only last 10
        $recentIPs = array_slice(array_unique(array_merge([$ip], $recentIPs)), 0, 10);

        Cache::put($cacheKey, $recentIPs, 86400 * 7); // Keep for 7 days
    }

    /**
     * Check if IP is in same geographic region.
     */
    private function isSameRegion(string $newIP, array $recentIPs): bool
    {
        if (empty($recentIPs)) {
            return false;
        }

        $newCountry = $this->getIPCountry($newIP);

        foreach ($recentIPs as $recentIP) {
            $recentCountry = $this->getIPCountry($recentIP);
            if ($newCountry === $recentCountry) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has emergency bypass enabled.
     */
    private function hasEmergencyBypass($user): bool
    {
        $cacheKey = "ip_emergency_bypass:{$user->id}";

        return Cache::get($cacheKey, false);
    }

    /**
     * Grant emergency IP bypass.
     */
    public static function grantEmergencyBypass(int $userId, int $durationMinutes = 60): void
    {
        $cacheKey = "ip_emergency_bypass:{$userId}";
        Cache::put($cacheKey, true, $durationMinutes * 60);

        Log::channel('security')->warning('Emergency IP Bypass Granted', [
            'user_id' => $userId,
            'granted_by' => Auth::id(),
            'duration_minutes' => $durationMinutes,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Handle IP-restricted access.
     */
    private function ipRestricted(Request $request, string $clientIP, string $mode): Response
    {
        $message = "Access denied from IP address {$clientIP}. Contact your administrator to whitelist this IP.";

        // Log IP restriction attempt
        Log::channel('security')->warning('IP-Based Access Denied', [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email,
            'client_ip' => $clientIP,
            'mode' => $mode,
            'attempted_url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'IP Restricted',
                'code' => 'IP_RESTRICTED',
                'client_ip' => $clientIP,
            ], 403);
        }

        if ($request->inertia()) {
            return inertia('Error', [
                'status' => 403,
                'message' => $message,
                'code' => 'IP_RESTRICTED',
                'client_ip' => $clientIP,
            ], 403);
        }

        abort(403, $message);
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

        if ($request->inertia()) {
            return inertia('Error', [
                'status' => 401,
                'message' => $message,
            ], 401);
        }

        abort(401, $message);
    }
}
