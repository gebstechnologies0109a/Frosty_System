<?php

namespace App\Enums;

enum AdminPageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Published => 'success',
        };
    }
}
