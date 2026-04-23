<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class RecordLimitExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('تم الوصول للحد الأقصى من السجلات', 422);
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
