<?php

namespace App\Support;

/**
 * Maps price-document sheet/section names to Frosty product categories (supply | sparepart).
 */
final class SupplyDocumentCategory
{
    /** Document sections that map to sparepart. All others map to supply. */
    private const SPAREPART_SECTIONS = [
        'spareparts',
        'sparepart',
        'spare parts',
        'machine parts',
        'machine_parts',
        'machine part',
    ];

    /** @var list<string> */
    public const DOCUMENT_SECTIONS = [
        'supplies',
        'spareparts',
        'cones',
        'cups',
        'utensils',
        'machine parts',
        'ramen',
        'beverages (non-softserve)',
        'beverages',
        'canned drinks',
        'frozen toppings',
    ];

    public static function toProductCategory(?string $documentCategory): string
    {
        if ($documentCategory === null || $documentCategory === '') {
            return 'supply';
        }

        $normalized = strtolower(trim($documentCategory));

        foreach (self::SPAREPART_SECTIONS as $sparepart) {
            if ($normalized === $sparepart || str_contains($normalized, 'sparepart') || str_contains($normalized, 'machine part')) {
                return 'sparepart';
            }
        }

        return 'supply';
    }
}
