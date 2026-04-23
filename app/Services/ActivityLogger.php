<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public function log(string $action, string $detail = '', ?string $username = null): void
    {
        // Check if activity_logs table exists before logging
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        $username ??= Auth::user()?->username ?? 'system';
        
        try {
            ActivityLog::create([
                'action' => $action,
                'detail' => $detail,
                'username' => $username,
            ]);
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist or other database issues
            // This prevents authentication from failing due to logging issues
        }
    }
}
