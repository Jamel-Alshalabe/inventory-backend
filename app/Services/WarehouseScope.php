<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Resolves the "effective" warehouse a request operates on, enforcing
 * the rule that a regular user with an assigned warehouse can never
 * read or write outside of it — regardless of what the client sends.
 */
class WarehouseScope
{
    public function effective(User $user, ?int $requested): ?int
    {
        if ($user->isLockedToWarehouse()) {
            return $user->assigned_warehouse_id;
        }
        return $requested;
    }
}
