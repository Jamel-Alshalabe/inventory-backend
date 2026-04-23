<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionMiddleware
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
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();

        // SuperAdmin can bypass subscription check
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            return response()->json([
                'message' => 'Subscription expired or inactive',
                'error' => 'SUBSCRIPTION_EXPIRED',
                'subscription_required' => true
            ], 403);
        }

        return $next($request);
    }
}
