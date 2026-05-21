<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class OrderAnalyticsFilter
{
    public static function apply(Builder $query, Request $request): Builder
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('region')) {
            $query->where('price_region', $request->input('region'));
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->input('distributor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    public static function baseQuery(Request $request): Builder
    {
        return self::apply(Order::query(), $request);
    }

    /** @return array<string, mixed> */
    public static function validated(Request $request): array
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'region' => ['nullable', 'in:luzon,davao,tacloban'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);

        return $request->only(['date_from', 'date_to', 'region', 'distributor_id', 'status']);
    }
}
