<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TazbeebexAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $token = $request->header('Authorization');
        // Check if the token exists and matches the expected value
        if (!$token || $token !== env('TAZBEEBEX_API_TOKEN')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        return $next($request);
    }
}