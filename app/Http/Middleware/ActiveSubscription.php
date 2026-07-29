<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = auth()->user()->activeSubscription;

        if(!$subscription){
            return response()->json([
                'message'=>'Active subscription required.'
            ],403);
        }

        return $next($request);    
    
    }
}
