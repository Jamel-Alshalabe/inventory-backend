<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = ActivityLog::with(['causer'])->orderByDesc('created_at');

        // Filter by admin_id for multi-tenant support
        if ($user && $user->admin_id) {
            // Regular user - see their admin's logs
            $query->where('admin_id', $user->admin_id);
        } elseif ($user && $user->hasRole('admin')) {
            // Admin users see logs for themselves and their users
            $query->where(function ($q) use ($user) {
                $q->where('admin_id', $user->id)
                  ->orWhereNull('admin_id'); // Include logs without admin_id for compatibility
            });
        } elseif ($user && $user->hasRole('super_admin')) {
            // Super admin can see all logs
            // For now, super admin sees all logs
        }

        return ActivityLogResource::collection(
            $query->limit($request->integer('limit') ?: 200)->get()
        );
    }

    public function destroy(): JsonResponse
    {
        // Log the activity before clearing
        $this->logger->log('مسح سجل العمليات', 'تم مسح جميع سجلات العمليات');
        
        ActivityLog::query()->delete();
        return response()->json(['ok' => true]);
    }
}
