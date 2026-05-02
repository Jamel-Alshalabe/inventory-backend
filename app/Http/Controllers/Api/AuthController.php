<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        /** @var User|null $user */
        $user = User::query()
            ->with(['roles', 'assignedWarehouse'])
            ->where('username', $data['username'])
            ->first();

        if ($user) {
            // Laratrust's allPermissions() ensures we get both direct and role-based permissions
            $user->setRelation('permissions', $user->allPermissions());
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'بيانات الدخول غير صحيحة'], 401);
        }

        $token = $user->createToken('snk-cli', ['*'])->plainTextToken;
        $this->logger->log('تسجيل دخول', '', $user->username);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing(['roles', 'assignedWarehouse']);
            // Laratrust's allPermissions() ensures we get both direct and role-based permissions
            $user->setRelation('permissions', $user->allPermissions());
        }

        return new UserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }
        $this->logger->log('تسجيل خروج');
        return response()->json(['ok' => true]);
    }
}
