<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserSubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user from the login attempt
        $user = $this->getUserFromLoginRequest($request);
        
        if (!$user) {
            // If we can't determine the user, let the login process continue
            // The authentication will fail normally if credentials are wrong
            return $next($request);
        }

        // SuperAdmin can always login without subscription check
        if ($user->hasRole('super_admin')) {
            Log::info('SuperAdmin login - bypassing subscription check', [
                'user_id' => $user->id,
                'username' => $user->username
            ]);
            return $next($request);
        }

        // Check if user has an active subscription
        $subscription = $this->getActiveSubscriptionForUser($user);
        
        if (!$subscription) {
            Log::warning('Login denied - no active subscription', [
                'user_id' => $user->id,
                'username' => $user->username,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'admin_id' => $user->admin_id
            ]);
            
            return response()->json([
                'message' => 'لا يمكن تسجيل الدخول. لا يوجد اشتراك نشط.',
                'error_type' => 'subscription_expired',
                'requires_subscription' => true
            ], 403);
        }

        // Check if subscription is still valid
        if (!$this->isSubscriptionValid($subscription)) {
            Log::warning('Login denied - subscription expired', [
                'user_id' => $user->id,
                'username' => $user->username,
                'subscription_id' => $subscription->id,
                'end_date' => $subscription->end_date,
                'is_active' => $subscription->is_active
            ]);
            
            return response()->json([
                'message' => 'لا يمكن تسجيل الدخول. انتهت صلاحية الاشتراك.',
                'error_type' => 'subscription_expired',
                'requires_subscription' => true,
                'subscription_end_date' => $subscription->end_date
            ], 403);
        }

        Log::info('Subscription check passed', [
            'user_id' => $user->id,
            'username' => $user->username,
            'subscription_id' => $subscription->id,
            'subscription_end_date' => $subscription->end_date
        ]);

        return $next($request);
    }

    /**
     * Get user from login request
     */
    private function getUserFromLoginRequest(Request $request): ?User
    {
        $username = $request->input('username');
        
        if (!$username) {
            return null;
        }

        return User::where('username', $username)->first();
    }

    /**
     * Get active subscription for user
     * For regular users, check their own subscription
     * For admin users, check their admin's subscription
     */
    private function getActiveSubscriptionForUser(User $user): ?UserSubscription
    {
        // If user has admin_id, check the admin's subscription
        if ($user->admin_id) {
            $admin = User::find($user->admin_id);
            if (!$admin) {
                Log::error('Admin not found for user', [
                    'user_id' => $user->id,
                    'admin_id' => $user->admin_id
                ]);
                return null;
            }

            return UserSubscription::where('user_id', $admin->id)
                ->where('is_active', true)
                ->orderBy('end_date', 'desc')
                ->first();
        }

        // For users without admin_id (direct admins), check their own subscription
        return UserSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('end_date', 'desc')
            ->first();
    }

    /**
     * Check if subscription is still valid
     */
    private function isSubscriptionValid(UserSubscription $subscription): bool
    {
        if (!$subscription->is_active) {
            return false;
        }

        $endDate = \Carbon\Carbon::parse($subscription->end_date);
        $now = \Carbon\Carbon::now();

        return $endDate->greaterThan($now);
    }
}
