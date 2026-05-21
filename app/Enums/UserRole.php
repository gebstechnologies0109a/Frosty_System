<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case PurchasingAdmin = 'purchasing_admin';
    case FinanceAdmin = 'finance_admin';
    case ItAdmin = 'it_admin';
    case Distributor = 'distributor';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::PurchasingAdmin => 'Purchasing Admin',
            self::FinanceAdmin => 'Finance Admin',
            self::ItAdmin => 'IT Admin',
            self::Distributor => 'Distributor',
            self::Operator => 'Operator',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [
            self::SuperAdmin,
            self::PurchasingAdmin,
            self::FinanceAdmin,
            self::ItAdmin,
        ], true);
    }

    /** @return list<string> */
    public static function adminRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::PurchasingAdmin->value,
            self::FinanceAdmin->value,
            self::ItAdmin->value,
        ];
    }
}
