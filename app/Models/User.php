<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Traits\HasRolesAndPermissions;

/**
 * @property int $id
 * @property int|null $admin_id
 * @property string $username
 * @property string $password
 * @property Role $role
 * @property int|null $assigned_warehouse_id
 * @property int $max_warehouses
 * @property string|null $company_name
 * @property string|null $company_phone
 * @property string|null $company_address
 * @property string $company_currency
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRolesAndPermissions;

    protected $fillable = [
        'admin_id',
        'username',
        'password',
        'role',
        'assigned_warehouse_id',
        'max_warehouses',
        'company_name',
        'company_phone',
        'company_address',
        'company_currency',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    public function assignedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'assigned_warehouse_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()->active()->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function canCreateMoreWarehouses(): bool
    {
        $currentWarehouses = Warehouse::where('created_by', $this->id)->count();
        return $currentWarehouses < $this->max_warehouses;
    }

    public function getRemainingWarehouses(): int
    {
        $currentWarehouses = Warehouse::where('created_by', $this->id)->count();
        return max(0, $this->max_warehouses - $currentWarehouses);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canMutate(): bool
    {
        return $this->role->canMutate();
    }

    public function isLockedToWarehouse(): bool
    {
        return $this->role === Role::User && $this->assigned_warehouse_id !== null;
    }

    public function getCompanyDisplayName(): string
    {
        return $this->company_name ?: 'شركة غير محددة';
    }

    public function getCompanyCurrency(): string
    {
        return $this->company_currency ?: 'ج.م';
    }

  

    public function canManageUsers(): bool
    {
        return $this->isAdmin() && $this->hasPermission('manage-users');
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin() && $this->hasPermission('manage-settings');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasRole('admin') || $this->hasPermission($permission);
    }
}
