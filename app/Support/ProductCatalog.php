<?php

namespace App\Support;

final class ProductCatalog
{
    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function softserveProducts(): array
    {
        return [
            [
                'name' => 'Vanilla Softserve Powder 1kg',
                'category' => 'softserve',
                'points' => 2,
                'prices' => ['luzon' => 226.0, 'davao' => 236.0, 'tacloban' => 236.0],
            ],
            [
                'name' => 'Chocolate Softserve Powder 1kg',
                'category' => 'softserve',
                'points' => 2,
                'prices' => ['luzon' => 241.0, 'davao' => 251.0, 'tacloban' => 251.0],
            ],
            [
                'name' => 'Mango Softserve Powder 1kg',
                'category' => 'softserve',
                'points' => 2,
                'prices' => ['luzon' => 228.0, 'davao' => 238.0, 'tacloban' => 238.0],
            ],
            [
                'name' => 'Ube Softserve Powder 1kg',
                'category' => 'softserve',
                'points' => 2,
                'prices' => ['luzon' => 228.0, 'davao' => 238.0, 'tacloban' => 238.0],
            ],
            [
                'name' => 'Strawberry Softserve Powder 1kg',
                'category' => 'softserve',
                'points' => 2,
                'prices' => ['luzon' => 228.0, 'davao' => 238.0, 'tacloban' => 238.0],
            ],
        ];
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function yogurtProducts(): array
    {
        return [
            [
                'name' => 'Supreme Yogurt Powder 1kg',
                'category' => 'yogurt',
                'points' => 0,
                'prices' => ['luzon' => 278.0, 'davao' => 288.0, 'tacloban' => 288.0],
            ],
            [
                'name' => 'Regular Yogurt Powder 1kg',
                'category' => 'yogurt',
                'points' => 0,
                'prices' => ['luzon' => 253.0, 'davao' => 263.0, 'tacloban' => 263.0],
            ],
        ];
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function syrupProducts(): array
    {
        return [
            [
                'name' => 'Chocolate Syrup 1kg',
                'category' => 'syrup',
                'points' => 0,
                'prices' => ['luzon' => 248.0, 'davao' => 258.0, 'tacloban' => 258.0],
            ],
            [
                'name' => 'Strawberry Syrup 1kg',
                'category' => 'syrup',
                'points' => 0,
                'prices' => ['luzon' => 203.0, 'davao' => 213.0, 'tacloban' => 213.0],
            ],
            [
                'name' => 'Caramel Syrup 1kg',
                'category' => 'syrup',
                'points' => 0,
                'prices' => ['luzon' => 243.0, 'davao' => 253.0, 'tacloban' => 253.0],
            ],
        ];
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function dipProducts(): array
    {
        return [
            [
                'name' => 'Chocolate Dip 1kg',
                'category' => 'dip',
                'points' => 0,
                'prices' => ['luzon' => 263.0, 'davao' => 273.0, 'tacloban' => 273.0],
            ],
        ];
    }

    /** Rebate-eligible and formula products only (not supply/sparepart — those come from JSON). */
    public static function formulaProducts(): array
    {
        return array_merge(
            self::softserveProducts(),
            self::yogurtProducts(),
            self::syrupProducts(),
            self::dipProducts(),
            self::beverageProducts(),
            self::coffeeProducts(),
        );
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function beverageProducts(): array
    {
        return [
            ['name' => 'Blue Lemonade 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 263.0, 'davao' => 273.0, 'tacloban' => 273.0]],
            ['name' => 'Cucumber Lemonade 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 263.0, 'davao' => 273.0, 'tacloban' => 273.0]],
            ['name' => 'Mango Slush 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 265.0, 'davao' => 275.0, 'tacloban' => 275.0]],
            ['name' => 'Iced Tea Lemon 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 263.0, 'davao' => 273.0, 'tacloban' => 273.0]],
            ['name' => 'Four Season 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 264.0, 'davao' => 273.0, 'tacloban' => 273.0]],
            ['name' => 'Red Iced Tea 200g', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 263.0, 'davao' => 273.0, 'tacloban' => 273.0]],
            ['name' => 'Slush Base 1kg', 'category' => 'beverage', 'points' => 0, 'prices' => ['luzon' => 176.0, 'davao' => 187.0, 'tacloban' => 187.0]],
        ];
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function coffeeProducts(): array
    {
        return [
            ['name' => '3in1 Coffee Powder 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 365.0, 'davao' => 375.0, 'tacloban' => 375.0]],
            ['name' => 'Caramel Macchiato 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 365.0, 'davao' => 375.0, 'tacloban' => 375.0]],
            ['name' => 'White Coffee 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 365.0, 'davao' => 375.0, 'tacloban' => 375.0]],
            ['name' => 'Café Latte 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 365.0, 'davao' => 375.0, 'tacloban' => 375.0]],
            ['name' => 'Cappuccino 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 365.0, 'davao' => 375.0, 'tacloban' => 375.0]],
            ['name' => 'Choco Vendo 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 313.0, 'davao' => 323.0, 'tacloban' => 323.0]],
            ['name' => 'Coffee Bean Dark Roast Barako 1kg', 'category' => 'coffee', 'points' => 0, 'prices' => ['luzon' => 690.0, 'davao' => 700.0, 'tacloban' => 700.0]],
        ];
    }

    /** @return list<array{name: string, category: string, points: int, prices: array{luzon: float, davao: float, tacloban: float}}> */
    public static function allCatalogProducts(): array
    {
        return self::formulaProducts();
    }
}
