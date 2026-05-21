<?php

namespace App\Services;

final class SystemPagesRegistry
{
    /** @return list<array{title: string, slug: string, route_name: string, path: string}> */
    public static function pages(): array
    {
        return [
            ['title' => 'Admin Dashboard', 'slug' => 'admin-dashboard', 'route_name' => 'admin.dashboard', 'path' => '/admin'],
            ['title' => 'Super Admin Users', 'slug' => 'admin-users', 'route_name' => 'admin.users.index', 'path' => '/admin/users'],
            ['title' => 'Super Admin Operators', 'slug' => 'admin-operators', 'route_name' => 'admin.operators.index', 'path' => '/admin/operators'],
            ['title' => 'Super Admin Distributors', 'slug' => 'admin-distributors', 'route_name' => 'admin.distributors.index', 'path' => '/admin/distributors'],
            ['title' => 'Super Admin Products', 'slug' => 'admin-products', 'route_name' => 'admin.products.index', 'path' => '/admin/products'],
            ['title' => 'Super Admin POS Logs', 'slug' => 'admin-pos-logs', 'route_name' => 'admin.pos-sales-logs.index', 'path' => '/admin/pos-sales-logs'],
            ['title' => 'Super Admin POS Closings', 'slug' => 'admin-pos-closings', 'route_name' => 'admin.pos.daily-closings.index', 'path' => '/admin/pos/daily-closings'],
            ['title' => 'Super Admin Orders', 'slug' => 'admin-orders', 'route_name' => 'admin.orders.index', 'path' => '/admin/orders'],
            ['title' => 'Super Admin Order Analytics', 'slug' => 'admin-order-analytics', 'route_name' => 'admin.orders.analytics', 'path' => '/admin/orders/analytics'],
            ['title' => 'Super Admin Finance', 'slug' => 'admin-finance', 'route_name' => 'admin.finance.reports', 'path' => '/admin/finance/reports'],
            ['title' => 'Super Admin Settings', 'slug' => 'admin-settings', 'route_name' => 'admin.settings.index', 'path' => '/admin/settings'],
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultLayoutFor(string $title): array
    {
        return [
            'blocks' => [
                [
                    'id' => 'welcome-'.md5($title),
                    'type' => 'text',
                    'content' => 'Customize the '.$title.' page layout here. Published blocks appear above the standard page content.',
                ],
            ],
        ];
    }
}
