<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\ActionLog;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, DispatchesJobs;
    
    // protected $request;
    
    // public function __construct(Request $request)
    // {
    //     $this->middleware(function ($req, $next) use ($request) {
    //         if (auth()->check()) {
    //             ActionLog::create([
    //                 'user_id' => auth()->id(),
    //                 'action' => $request->method() . ' ' . $request->path(),
    //                 'details' => json_encode($request->except(['password', 'password_confirmation'])),
    //                 'ip_address' => $request->ip(),
    //             ]);
    //         }
    //         return $next($req);
    //     });
    // }
}
