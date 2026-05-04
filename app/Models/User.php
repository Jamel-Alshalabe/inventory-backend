<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Traits\HasRolesAndPermissions;

/**
 * @property int $id
 * @property int|null $admin_id
 * @property int|null $created_by_id
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
        'created_by_id',
        'username',
        'email',
        'password',
        'assigned_warehouse_id',
        'max_warehouses',
        'company_name',
        'company_phone',
        'phone2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_id');
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
        $currentWarehouses = Warehouse::where('admin_id', $this->id)->count();
        return $currentWarehouses < $this->max_warehouses;
    }

    public function getRemainingWarehouses(): int
    {
        $currentWarehouses = Warehouse::where('admin_id', $this->id)->count();
        return max(0, $this->max_warehouses - $currentWarehouses);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function canMutate(): bool
    {
        // Users with admin or super_admin roles can mutate
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isLockedToWarehouse(): bool
    {
        return ($this->hasRole('user') || $this->hasRole('auditor')) && $this->assigned_warehouse_id !== null;
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
        return $this->isAdmin() && $this->isAbleTo('manage-users');
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin() && $this->isAbleTo('manage-settings');
    }

    public function hasPermission(string $permission): bool
    {
        // Don't override Laratrust logic for individual permission checks if we want to see direct permissions
        return $this->isAbleTo($permission);
    }

    public function canEditUsername(): bool
    {
        return true; // All users can edit their own username
    }

    public function canEditPassword(): bool
    {
        return true; // All users can edit their own password
    }

    public function canEditCompanyInfo(): bool
    {
        $userRole = $this->roles->first()?->name;
        return $userRole !== 'super_admin';
    }

    public function canEditInvoiceSettings(): bool
    {
        $userRole = $this->roles->first()?->name;
        return $userRole !== 'super_admin';
    }

    public function canEditPersonalInfo(): bool
    {
        $userRole = $this->roles->first()?->name;
        return $userRole !== 'super_admin';
    }

    public function getEditableFields(): array
    {
        $userRole = $this->roles->first()?->name;
        
        if ($userRole === 'super_admin') {
            return ['username', 'password', 'max_warehouses'];
        }
        
        if ($userRole === 'admin') {
            return [
                'username',
                'email',
                'password',
                'assignedWarehouseId',
                'maxWarehouses',
                'company_name',
                'company_phone',
                'phone2',
                'company_address',
                'company_currency'
            ];
        }
        
        // Other roles (User, Editor) - can only edit their own info
        return ['username', 'email', 'password'];
    }

    public function scopeSearch(Builder $query, $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where('username', 'like', "%{$search}%");
    }
}
