<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;

class CheckDeviceToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->user()->token();
    
        if ($token && $token->revoked) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Your token has been revoked. Please log in again.'
            ], 401);
        }
    
        return $next($request);
    }


}

