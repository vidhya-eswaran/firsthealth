<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActionLog;

class ActionLogger
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            ActionLog::create([
                'user_id' => auth()->id(),
                'action' => $request->method() . ' ' . $request->path(),
                'details' => json_encode($request->except(['password', 'password_confirmation'])),
                'ip_address' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}


