<?php

use App\Http\Middleware\AuditAction;
use App\Http\Middleware\CascadeAuth;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HasPermission;
use App\Http\Middleware\HasRole;
use App\Http\Middleware\IPRestricted;
use App\Http\Middleware\OwnerOrPermission;
use App\Http\Middleware\SchoolUser;
use App\Http\Middleware\SystemUser;
use App\Http\Middleware\TimeBasedAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'permission' => HasPermission::class,
            'role' => HasRole::class,
            'system-user' => SystemUser::class,
            'school-user' => SchoolUser::class,
            'owner-or-permission' => OwnerOrPermission::class,
            'audit-action' => AuditAction::class,
            'cascade-auth' => CascadeAuth::class,
            'time-based-access' => TimeBasedAccess::class,
            'ip-restricted' => IPRestricted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
