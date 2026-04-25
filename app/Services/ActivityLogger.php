<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public function log(
        string $action,
        string $detail = '',
        ?string $username = null,
        ?object $subject = null,
        array $properties = []
    ): void {
        // Check if activity_log table exists before logging
        if (!Schema::hasTable('activity_log')) {
            Log::warning('activity_log table does not exist, skipping activity logging');
            return;
        }

        $user = Auth::user();
        $username ??= $user?->username ?? 'system';

        // Get admin_id for multi-tenant support
        $adminId = null;
        if ($user) {
            if ($user->admin_id) {
                // Regular user - use their admin's ID
                $adminId = $user->admin_id;
            } elseif ($user->hasRole('admin')) {
                // Admin user - use their own ID
                $adminId = $user->id;
            }
            // Super admin - keep null for all logs
        }

        // Prepare subject data
        $subjectType = null;
        $subjectId = null;
        if ($subject) {
            $subjectType = get_class($subject);
            $subjectId = $subject->id ?? null;
        }

        // Prepare properties with additional context
        $propertiesData = array_merge([
            'username' => $username,
            'action' => $action,
            'detail' => $detail,
        ], $properties);

        try {
            ActivityLog::create([
                'admin_id' => $adminId,
                'log_name' => $action,
                'description' => $detail,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'causer_type' => $user ? 'App\Models\User' : null,
                'causer_id' => $user?->id,
                'properties' => !empty($propertiesData) ? json_encode($propertiesData) : null,
                'batch_uuid' => null,
            ]);
            
            Log::info('Activity logged', [
                'action' => $action,
                'detail' => $detail,
                'username' => $username,
                'admin_id' => $adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity', [
                'action' => $action,
                'detail' => $detail,
                'username' => $username,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
