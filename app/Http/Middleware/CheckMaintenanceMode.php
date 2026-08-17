<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenanceOn = SystemSetting::getValue('maintenance_mode', '0') === '1';

        if (! $isMaintenanceOn) {
            return $next($request);
        }

        // Admins can always get through (login page + everything after)
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        // Allow the admin-login routes so an admin can still sign in
        if ($request->routeIs('admin.login') || $request->routeIs('admin.login.submit') || $request->routeIs('logout')) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}