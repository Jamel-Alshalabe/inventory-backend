<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $subscriptions = UserSubscription::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'subscription_cost' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 1. Check if user is an admin
        $user = User::find($request->user_id);
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'خطأ في التحقق',
                'errors' => ['user_id' => ['يمكن إضافة اشتراكات لمدراء النظام (Admins) فقط.']]
            ], 422);
        }

        // 2. Check for existing active subscription
        $activeSubscription = UserSubscription::where('user_id', $request->user_id)
            ->where('is_active', true)
            ->where('end_date', '>', now())
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'خطأ في التحقق',
                'errors' => ['user_id' => ['هذا المستخدم لديه اشتراك فعال بالفعل ينتهي في ' . $activeSubscription->end_date]]
            ], 422);
        }

        try {
            $subscription = UserSubscription::create([
                'user_id' => $request->user_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'subscription_cost' => $request->subscription_cost,
                'is_active' => $request->get('is_active', true),
                'notes' => $request->notes,
            ]);

            // Load user relationship for response
            $subscription->load('user');

            // Log the activity
            $this->logger->log('إنشاء اشتراك', "اشتراك جديد للمستخدم {$subscription->user->username}");

            Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'end_date' => $subscription->end_date
            ]);

            return response()->json($subscription, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserSubscription $subscription): JsonResponse
    {
        $subscription->load('user');
        return response()->json($subscription);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserSubscription $subscription): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'subscription_cost' => 'numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscription->update($request->only([
                'start_date',
                'end_date', 
                'subscription_cost',
                'is_active',
                'notes'
            ]));

            $subscription->load('user');

            // Log the activity
            $this->logger->log('تعديل اشتراك', "تعديل اشتراك المستخدم {$subscription->user->username}");

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'new_end_date' => $subscription->end_date,
                'is_active' => $subscription->is_active
            ]);

            return response()->json($subscription);
        } catch (\Exception $e) {
            Log::error('Failed to update subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserSubscription $subscription): JsonResponse
    {
        try {
            $subscriptionId = $subscription->id;
            $userId = $subscription->user_id;
            $username = $subscription->user->username ?? 'مستخدم غير معروف';
            
            // Log the activity before deletion
            $this->logger->log('حذف اشتراك', "حذف اشتراك المستخدم {$username}");
            
            $subscription->delete();

            Log::info('Subscription deleted', [
                'subscription_id' => $subscriptionId,
                'user_id' => $userId
            ]);

            return response()->json(['message' => 'Subscription deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'message' => 'Failed to delete subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subscriptions for a specific user
     */
    public function getUserSubscriptions(User $user): JsonResponse
    {
        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    /**
     * Get all active subscriptions
     */
    public function getActiveSubscriptions(): JsonResponse
    {
        $subscriptions = UserSubscription::with('user')
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('end_date', 'asc')
            ->get();

        return response()->json($subscriptions);
    }

    /**
     * Get all expired subscriptions
     */
    public function getExpiredSubscriptions(): JsonResponse
    {
        $subscriptions = UserSubscription::with('user')
            ->where(function($query) {
                $query->where('is_active', false)
                      ->orWhere('end_date', '<', now());
            })
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    /**
     * Check subscription status for a user
     */
    public function checkSubscriptionStatus(User $user): JsonResponse
    {
        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'has_subscription' => false,
                'status' => 'no_subscription',
                'message' => 'لا يوجد اشتراك'
            ]);
        }

        $endDate = \Carbon\Carbon::parse($subscription->end_date);
        $now = \Carbon\Carbon::now();

        if ($endDate->lessThan($now)) {
            return response()->json([
                'has_subscription' => false,
                'status' => 'expired',
                'message' => 'انتهت صلاحية الاشتراك',
                'subscription' => $subscription
            ]);
        }

        $daysRemaining = $now->diffInDays($endDate);

        return response()->json([
            'has_subscription' => true,
            'status' => 'active',
            'message' => 'الاشتراك نشط',
            'days_remaining' => $daysRemaining,
            'subscription' => $subscription
        ]);
    }
}
