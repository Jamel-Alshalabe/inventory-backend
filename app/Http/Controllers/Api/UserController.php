<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::query()->orderBy('username')->get());
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $data = $request->validated();
        $user = User::create([
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'],
            'assigned_warehouse_id' => $data['assignedWarehouseId'] ?? null,
        ]);
        $this->logger->log('إضافة مستخدم', $user->username);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user = User::findOrFail($id);
        $data = $request->validated();
        $patch = array_filter([
            'username' => $data['username'] ?? null,
            'role' => $data['role'] ?? null,
            'assigned_warehouse_id' => array_key_exists('assignedWarehouseId', $data)
                ? $data['assignedWarehouseId']
                : null,
        ], static fn ($v) => $v !== null);

        if (! empty($data['password'])) {
            $patch['password'] = $data['password'];
        }
        $user->fill($patch)->save();
        $this->logger->log('تعديل مستخدم', $user->username);
        return new UserResource($user);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();
        $this->logger->log('حذف مستخدم', $user->username);
        return response()->json(['ok' => true]);
    }
}
