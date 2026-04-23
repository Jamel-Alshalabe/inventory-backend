<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function log(string $action, string $detail = '', ?string $username = null): void
    {
        $username ??= Auth::user()?->username ?? 'system';
        ActivityLog::create([
            'action' => $action,
            'detail' => $detail,
            'username' => $username,
        ]);
    }
}
