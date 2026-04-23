<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RecordLimitExceededException;
use App\Models\Invoice;
use App\Models\Movement;
use App\Models\Product;

class RecordLimiter
{
    public function ensureRoom(int $additional = 1): void
    {
        $limit = (int) config('inventory.record_limit', 999);
        $used = Product::count() + Movement::count() + Invoice::count();
        if ($used + $additional > $limit) {
            throw new RecordLimitExceededException;
        }
    }
}
