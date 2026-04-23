<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير',
            self::User => 'مستخدم',
            self::Auditor => 'مراجع',
        };
    }

    public function canMutate(): bool
    {
        return $this !== self::Auditor;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
