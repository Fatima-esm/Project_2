<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
    
        // التحقق من وجود اشتراك سارٍ
        $activeSubscription = \App\Models\Subscription::where('user_id', $user->id)
            ->where('status', 'paid')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$activeSubscription) {
            return response()->json(['message' => 'انتهى اشتراكك، يرجى التجديد.'], 403);
        }

        return $next($request);
        }
}
