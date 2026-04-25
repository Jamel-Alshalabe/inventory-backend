<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Requests\Account\UpdateUsernameRequest;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function updateUsername(UpdateUsernameRequest $request): UserResource|JsonResponse
    {
        $user = $request->user();
        $user->username = $request->validated('username');
        $user->save();
        $this->logger->log('تغيير اسم المستخدم');
        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->validated('newPassword'));
        $user->save();
        $this->logger->log('تغيير كلمة المرور');
        return response()->json(['ok' => true]);
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource|JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        // Verify current password if trying to change sensitive data
        if (isset($data['username']) || isset($data['newPassword'])) {
            if (!Hash::check($data['currentPassword'], $user->password)) {
                return response()->json(['error' => 'كلمة المرور الحالية غير صحيحة'], 422);
            }
        }
        
        // Get editable fields based on user role
        $editableFields = $user->getEditableFields();
        
        // Filter data to only include editable fields
        $filteredData = array_intersect_key($data, array_flip($editableFields));
        
        // Handle password update separately
        if (isset($filteredData['newPassword'])) {
            $user->password = Hash::make($filteredData['newPassword']);
            unset($filteredData['newPassword']);
        }
        
        // Update only the allowed fields
        foreach ($filteredData as $field => $value) {
            if ($field !== 'currentPassword') { // Don't update current password field
                $user->$field = $value;
            }
        }
        
        $user->save();
        $this->logger->log('تحديث الملف الشخصي');
        return new UserResource($user);
    }

    public function getUserSettings(): JsonResponse
    {
        $user = request()->user();
        
        // Get user-specific settings from user table
        $settings = [
            'companyName' => $user->company_name ?? '',
            'companyPhone' => $user->company_phone ?? '',
            'companyAddress' => $user->company_address ?? '',
            'currency' => $user->company_currency ?? 'ج.م',
        ];
        
        return response()->json($settings);
    }

    public function updateUserSettings(): JsonResponse
    {
        $user = request()->user();
        $data = request()->validate([
            'companyName' => 'nullable|string|max:255',
            'companyPhone' => 'nullable|string|max:20',
            'companyAddress' => 'nullable|string|max:500',
            'currency' => 'nullable|string|max:10',
        ]);
        
        // Update user settings in user table
        if (isset($data['companyName'])) {
            $user->company_name = $data['companyName'];
        }
        
        if (isset($data['companyPhone'])) {
            $user->company_phone = $data['companyPhone'];
        }
        
        if (isset($data['companyAddress'])) {
            $user->company_address = $data['companyAddress'];
        }
        
        if (isset($data['currency'])) {
            $user->company_currency = $data['currency'];
        }
        
        $user->save();
        $this->logger->log('تحديث إعدادات المستخدم');
        
        // Return updated settings
        $settings = [
            'companyName' => $user->company_name ?? '',
            'companyPhone' => $user->company_phone ?? '',
            'companyAddress' => $user->company_address ?? '',
            'currency' => $user->company_currency ?? 'ج.م',
        ];
        
        return response()->json($settings);
    }
}
