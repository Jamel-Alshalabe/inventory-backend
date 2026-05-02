<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case User = 'user';
    case Auditor = 'auditor';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير النظام الرئيسي',
            self::Admin => 'مدير',
            self::User => 'مستخدم',
            self::Auditor => 'مراجع',
            self::Editor => 'محرر',
        };
    }

    public function canMutate(): bool
    {
        return $this !== self::Editor;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
