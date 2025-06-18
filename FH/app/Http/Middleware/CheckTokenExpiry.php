<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckTokenExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if the user has a token and if it's expired
        if ($user && $user->token() && $user->token()->expires_at) {
            $expiresAt = $user->token()->expires_at;
            
            // Check if the token has expired
            if ($expiresAt < now()) {
                // Optionally, you can log out the user or revoke the token here
                $user->token()->revoke(); // Revoke the expired token
                return response()->json(['error' => 'Token expired'], 401);
            }
        }

        return $next($request);
    }
}
