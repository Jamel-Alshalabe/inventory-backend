<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productName)
    {
        parent::__construct("الكمية المتاحة من {$productName} غير كافية", 422);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
