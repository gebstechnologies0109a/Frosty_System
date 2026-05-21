<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ListPage
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100, 200];

    public static function perPage(Request $request, int $default = 20): int
    {
        $value = (int) $request->input('per_page', $default);

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : $default;
    }
}
