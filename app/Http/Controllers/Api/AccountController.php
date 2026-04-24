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
}
