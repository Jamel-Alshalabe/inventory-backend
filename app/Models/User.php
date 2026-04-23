<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property Role $role
 * @property int|null $assigned_warehouse_id
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'assigned_warehouse_id',
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

    public function assignedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'assigned_warehouse_id');
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
}
