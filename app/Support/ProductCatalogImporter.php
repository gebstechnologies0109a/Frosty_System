<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class ProductCatalogImporter
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function importFromJsonFile(string $path): array
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("Catalog file not readable: {$path}");
        }

        $raw = json_decode(file_get_contents($path), true);

        if (! is_array($raw)) {
            throw new \RuntimeException('Catalog file must be a JSON array.');
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($raw, &$imported, &$skipped) {
            foreach ($raw as $row) {
                if (! is_array($row) || empty($row['name'])) {
                    $skipped++;
                    continue;
                }

                $documentCategory = $row['document_category'] ?? $row['sheet'] ?? $row['section'] ?? 'supplies';
                $category = $row['category'] ?? SupplyDocumentCategory::toProductCategory((string) $documentCategory);

                if (! in_array($category, ['supply', 'sparepart'], true)) {
                    $category = SupplyDocumentCategory::toProductCategory((string) $documentCategory);
                }

                $prices = $row['prices'] ?? [];
                $points = (int) ($row['points'] ?? 0);

                $product = Product::query()->updateOrCreate(
                    ['name' => trim((string) $row['name'])],
                    [
                        'category' => $category,
                        'points' => $points,
                        'status' => $row['status'] ?? 'active',
                    ],
                );

                ProductRegionalPricing::sync($product, [
                    'luzon' => (float) ($prices['luzon'] ?? 0),
                    'davao' => (float) ($prices['davao'] ?? 0),
                    'tacloban' => (float) ($prices['tacloban'] ?? 0),
                ]);

                ProductInventoryService::ensure($product);

                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /** Default path for supply/sparepart JSON catalog. */
    public static function defaultSupplyCatalogPath(): string
    {
        return database_path('data/supply_sparepart_catalog.json');
    }
}
