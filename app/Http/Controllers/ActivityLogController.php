<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * Get paginated activity logs for the current admin.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $logName = $request->get('log_name');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Activity::query();

        // Filter by admin_id
        $adminId = $this->getAdminId();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        // Filter by log name
        if ($logName) {
            $query->forLog($logName);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $activities = $query->with(['causer', 'subject', 'admin'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'activities' => $activities->items(),
            'pagination' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ],
        ]);
    }

    /**
     * Get activity statistics for the current admin.
     */
    public function statistics(): JsonResponse
    {
        $adminId = $this->getAdminId();

        $query = Activity::query();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        // Total activities
        $totalActivities = $query->count();

        // Activities by log name
        $activitiesByLog = $query->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->get();

        // Activities by description (actions)
        $activitiesByAction = $query->selectRaw('description, COUNT(*) as count')
            ->groupBy('description')
            ->orderByDesc('count')
            ->get();

        // Recent activities (last 7 days)
        $recentActivities = $query->where('created_at', '>=', Carbon::now()->subDays(7))
            ->with(['causer', 'subject'])
            ->latest()
            ->limit(10)
            ->get();

        // Today's activities
        $todayActivities = $query->whereDate('created_at', Carbon::today())->count();

        return response()->json([
            'total_activities' => $totalActivities,
            'today_activities' => $todayActivities,
            'activities_by_log' => $activitiesByLog,
            'activities_by_action' => $activitiesByAction,
            'recent_activities' => $recentActivities,
        ]);
    }

    /**
     * Get available log names for filtering.
     */
    public function logNames(): JsonResponse
    {
        $adminId = $this->getAdminId();

        $query = Activity::query();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        $logNames = $query->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->orderBy('log_name')
            ->get();

        return response()->json($logNames);
    }

    /**
     * Export activities to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $logName = $request->get('log_name');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Activity::query();

        // Filter by admin_id
        $adminId = $this->getAdminId();
        if ($adminId) {
            $query->forAdmin($adminId);
        }

        // Apply filters
        if ($logName) {
            $query->forLog($logName);
        }
        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $activities = $query->with(['causer', 'subject', 'admin'])
            ->latest()
            ->get();

        // Create CSV data
        $csvData = [];
        $csvData[] = ['التاريخ', 'المستخدم', 'الإجراء', 'النوع', 'التفاصيل'];

        foreach ($activities as $activity) {
            $csvData[] = [
                $activity->created_at->format('Y-m-d H:i:s'),
                $activity->causer?->username ?? 'N/A',
                $activity->getArabicDescription(),
                $activity->log_name,
                json_encode($activity->properties, JSON_UNESCAPED_UNICODE),
            ];
        }

        // Generate CSV filename
        $filename = 'activity_log_' . now()->format('Y_m_d_H_i_s') . '.csv';

        // In a real application, you would return the CSV file
        // For now, return the data for frontend processing
        return response()->json([
            'filename' => $filename,
            'data' => $csvData,
        ]);
    }

    /**
     * Clear activity logs for the current admin.
     */
    public function clear(): JsonResponse
    {
        $adminId = $this->getAdminId();

        if (!$adminId) {
            return response()->json([
                'message' => 'Cannot clear logs for super admin',
            ], 403);
        }

        $deleted = Activity::forAdmin($adminId)->delete();

        ActivityLogService::log(
            'cleared',
            null,
            'system',
            [
                'cleared_by' => auth()->user()->username,
                'deleted_count' => $deleted,
            ]
        );

        return response()->json([
            'message' => 'Activity logs cleared successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Get admin_id for the current user.
     */
    private function getAdminId(): ?int
    {
        $user = auth()->user();

        // If user is admin themselves, return their own ID
        if ($user->hasRole('admin')) {
            return $user->id;
        }

        // If user has admin_id (employee), return their admin's ID
        if ($user->admin_id) {
            return $user->admin_id;
        }

        // If user is super_admin, return null (no admin filtering)
        if ($user->hasRole('super_admin')) {
            return null;
        }

        return null;
    }
}
