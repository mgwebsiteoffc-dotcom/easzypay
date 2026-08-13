<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With, Authorization')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers to all responses
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With');
        }

        return $response;
    }
}