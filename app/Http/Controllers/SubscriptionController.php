<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with('user')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->plan_type, function ($query, $plan) {
                $query->where('plan_type', $plan);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'subscription_cost' => 'required|numeric|min:0',
                'is_active' => 'boolean',
                'notes' => 'nullable|string|max:500',
            ]);

            $subscription = Subscription::create($validated);
            $subscription->load('user');

            return response()->json($subscription, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load('user');
        return response()->json($subscription);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'subscription_cost' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean',
                'notes' => 'nullable|string|max:500',
            ]);

            $subscription->update($validated);
            $subscription->load('user');

            return response()->json($subscription);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $subscription->delete();
        return response()->json(null, 204);
    }

    public function getUserSubscriptions(User $user): JsonResponse
    {
        $subscriptions = $user->subscriptions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    public function getActiveSubscriptions(): JsonResponse
    {
        $subscriptions = Subscription::with('user')
            ->active()
            ->orderBy('end_date', 'asc')
            ->get();

        return response()->json($subscriptions);
    }

    public function getExpiredSubscriptions(): JsonResponse
    {
        $subscriptions = Subscription::with('user')
            ->expired()
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json($subscriptions);
    }
}
