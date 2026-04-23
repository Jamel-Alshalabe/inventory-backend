<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
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
        if (! Hash::check($request->validated('currentPassword'), $user->password)) {
            return response()->json(['error' => 'كلمة المرور الحالية غير صحيحة'], 422);
        }
        $user->username = $request->validated('username');
        $user->save();
        $this->logger->log('تغيير اسم المستخدم');
        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! Hash::check($request->validated('currentPassword'), $user->password)) {
            return response()->json(['error' => 'كلمة المرور الحالية غير صحيحة'], 422);
        }
        $user->password = $request->validated('newPassword');
        $user->save();
        $this->logger->log('تغيير كلمة المرور');
        return response()->json(['ok' => true]);
    }
}
