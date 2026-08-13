<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('admin.login');
        }

        $user = Auth::guard('admin')->user();

        if (!$user->is_active) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'Your account is disabled.');
        }

        return $next($request);
    }
}