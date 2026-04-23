<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ActivityLogResource::collection(
            ActivityLog::query()
                ->orderByDesc('created_at')
                ->limit($request->integer('limit') ?: 200)
                ->get(),
        );
    }

    public function destroy(): JsonResponse
    {
        ActivityLog::query()->delete();
        return response()->json(['ok' => true]);
    }
}
