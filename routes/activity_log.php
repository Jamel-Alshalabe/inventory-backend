<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

// Activity Log Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Get paginated activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    
    // Get activity statistics
    Route::get('/activity-logs/statistics', [ActivityLogController::class, 'statistics']);
    
    // Get available log names for filtering
    Route::get('/activity-logs/log-names', [ActivityLogController::class, 'logNames']);
    
    // Export activities to CSV
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export']);
    
    // Clear activity logs (admin only)
    Route::delete('/activity-logs', [ActivityLogController::class, 'clear'])
        ->middleware(['permission:view-logs']);
});
