<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('tenant')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('tenant.login')
                ->with('message', 'Please login to continue.');
        }

        $tenant = Auth::guard('tenant')->user();

        if (!$tenant->is_active) {
            Auth::guard('tenant')->logout();
            return redirect()->route('tenant.login')
                ->with('error', 'Your account is disabled.');
        }

        return $next($request);
    }
}