<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Auth;

class ApiAuthMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token is missing or invalid.'
            ], 401);
        }
    
        $token = $request->bearerToken();
    
        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token is missing.'
            ], 401);
        }
    
        $accessToken = PersonalAccessToken::findToken($token);
    
        if (!$accessToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token.'
            ], 401);
        }
    
        // Extract user_id from token abilities
        $userId = $accessToken->abilities['user_id'] ?? null;
    
        if (!$userId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token is missing.'
            ], 401);
        }
    
        // Attach user_id to request
        $request->merge(['user_id' => $userId]);
    
        return $next($request);
    }
    
}
