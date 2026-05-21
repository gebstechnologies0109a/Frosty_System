<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Ewallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Ewallet => 'E-Wallet',
        };
    }
}
