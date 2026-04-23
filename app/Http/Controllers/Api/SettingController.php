<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            Setting::all()->pluck('value', 'key'),
        );
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        $this->logger->log('تحديث الإعدادات');
        return $this->index();
    }
}
