<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\ProductInventoryService;
use App\Support\ProductRegionalPricing;
use App\Support\StockMovementLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ProductCatalogImportService
{
    /** @return array<string, mixed> */
    public function import(UploadedFile $file, ?User $user): array
    {
        $rows = $this->parseFile($file);
        $report = [
            'total_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        if ($rows === []) {
            $report['errors'] = 1;
            $report['error_details'][] = ['row' => 0, 'message' => 'File is empty or has no data rows.'];

            return $report;
        }

        $header = array_shift($rows);
        $map = $this->columnMap($header);

        if (! isset($map['name']) || ! isset($map['category'])) {
            $report['errors'] = 1;
            $report['error_details'][] = ['row' => 1, 'message' => 'Header must include name and category columns.'];

            return $report;
        }

        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $report['total_rows']++;

            try {
                $result = $this->processRow($row, $map, $user);
                if ($result === 'created') {
                    $report['created']++;
                } else {
                    $report['updated']++;
                }
            } catch (\InvalidArgumentException $e) {
                $report['errors']++;
                $report['error_details'][] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            }
        }

        return $report;
    }

    /** @return 'created'|'updated' */
    private function processRow(array $row, array $map, ?User $user): string
    {
        $name = trim((string) $this->cell($row, $map, 'name'));
        $category = strtolower(trim((string) $this->cell($row, $map, 'category')));

        if ($name === '') {
            throw new \InvalidArgumentException('Name is required.');
        }

        if (! in_array($category, ProductCategory::values(), true)) {
            throw new \InvalidArgumentException("Invalid category: {$category}");
        }

        $status = strtolower(trim((string) ($this->cell($row, $map, 'status') ?? 'active')));
        if ($status === '') {
            $status = 'active';
        }
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('Status must be active or inactive.');
        }

        $points = $this->resolvePoints($category, $this->cell($row, $map, 'points'));

        $luzon = $this->optionalPrice($row, $map, 'luzon_price');
        $davao = $this->optionalPrice($row, $map, 'davao_price');
        $tacloban = $this->optionalPrice($row, $map, 'tacloban_price');
        $stock = $this->optionalStock($row, $map, 'stock');

        $productId = $this->cell($row, $map, 'product_id');
        $product = null;

        if ($productId !== null && $productId !== '' && is_numeric($productId)) {
            $product = Product::query()->find((int) $productId);
        }

        if (! $product) {
            $product = Product::query()->where('name', $name)->first();
        }

        $isNew = $product === null;

        return DB::transaction(function () use ($product, $name, $category, $points, $status, $luzon, $davao, $tacloban, $stock, $user, $isNew) {
            if ($isNew) {
                $product = Product::query()->create([
                    'name' => $name,
                    'category' => $category,
                    'points' => $points,
                    'status' => $status,
                ]);
                ProductInventoryService::ensure($product);
                StockMovementLogger::logProductCreated($product, $user);

                ProductRegionalPricing::sync($product, [
                    'luzon' => $luzon ?? 0,
                    'davao' => $davao ?? 0,
                    'tacloban' => $tacloban ?? 0,
                ]);

                if ($stock !== null && $stock > 0) {
                    $this->applyStockImport($product, 0, $stock, $user);
                }

                return 'created';
            }

            $product->update([
                'name' => $name,
                'category' => $category,
                'points' => $points,
                'status' => $status,
            ]);

            if ($luzon !== null || $davao !== null || $tacloban !== null) {
                $current = $product->regionalPrices();
                ProductRegionalPricing::sync($product, [
                    'luzon' => $luzon ?? $current['luzon'],
                    'davao' => $davao ?? $current['davao'],
                    'tacloban' => $tacloban ?? $current['tacloban'],
                ]);
            }

            if ($stock !== null) {
                $before = $product->stockLevel();
                if ($before !== $stock) {
                    $this->applyStockImport($product, $before, $stock, $user);
                }
            }

            return 'updated';
        });
    }

    private function applyStockImport(Product $product, int $before, int $after, ?User $user): void
    {
        ProductInventoryService::ensure($product)->update(['stock' => $after]);

        StockMovementLogger::log(
            $product,
            $user,
            StockMovement::ACTION_IMPORT_ADJUSTMENT,
            $before,
            $after,
            'Stock updated via import',
        );
    }

    private function resolvePoints(string $category, mixed $pointsInput): int
    {
        if ($category === ProductCategory::Softserve->value) {
            if ($pointsInput !== null && $pointsInput !== '') {
                $p = (int) $pointsInput;
                if (! in_array($p, [0, 2], true)) {
                    throw new \InvalidArgumentException('Points must be 0 or 2.');
                }

                return $p === 0 ? 2 : 2;
            }

            return 2;
        }

        if ($pointsInput !== null && $pointsInput !== '') {
            $p = (int) $pointsInput;
            if (! in_array($p, [0, 2], true)) {
                throw new \InvalidArgumentException('Points must be 0 or 2.');
            }
            if ($p !== 0) {
                throw new \InvalidArgumentException('Non-softserve products must have 0 points.');
            }
        }

        return 0;
    }

    /** @param  array<int, string|null>  $row
     * @param  array<string, int>  $map
     */
    private function optionalPrice(array $row, array $map, string $key): ?float
    {
        if (! isset($map[$key])) {
            return null;
        }

        $value = $this->cell($row, $map, $key);
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException("{$key} must be numeric.");
        }

        return max(0, (float) $value);
    }

    /** @param  array<int, string|null>  $row
     * @param  array<string, int>  $map
     */
    private function optionalStock(array $row, array $map, string $key): ?int
    {
        if (! isset($map[$key])) {
            return null;
        }

        $value = $this->cell($row, $map, $key);
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value) || (int) $value < 0) {
            throw new \InvalidArgumentException('Stock must be an integer ≥ 0.');
        }

        return (int) $value;
    }

    /** @param  array<int, string|null>  $row
     * @param  array<string, int>  $map
     */
    private function cell(array $row, array $map, string $key): mixed
    {
        if (! isset($map[$key])) {
            return null;
        }

        return $row[$map[$key]] ?? null;
    }

    /** @param  array<int, string|null>  $header
     * @return array<string, int>
     */
    private function columnMap(array $header): array
    {
        $map = [];
        $aliases = [
            'luzon_price' => ['luzon_price', 'price_luzon', 'luzon'],
            'davao_price' => ['davao_price', 'price_davao', 'davao'],
            'tacloban_price' => ['tacloban_price', 'price_tacloban', 'tacloban'],
        ];

        foreach ($header as $index => $label) {
            $normalized = strtolower(trim((string) $label));
            $normalized = str_replace([' ', '-'], '_', $normalized);

            if ($normalized === '') {
                continue;
            }

            $map[$normalized] = $index;

            foreach ($aliases as $canonical => $names) {
                if (in_array($normalized, $names, true)) {
                    $map[$canonical] = $index;
                }
            }
        }

        return $map;
    }

    /** @return list<array<int, string|null>> */
    private function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match ($ext) {
            'xlsx', 'xls' => $this->parseXlsx($file->getRealPath()),
            default => $this->parseCsv($file->getRealPath()),
        };
    }

    /** @return list<array<int, string|null>> */
    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }

        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }

    /** @return list<array<int, string|null>> */
    private function parseXlsx(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('Excel import requires phpoffice/phpspreadsheet. Run: composer require phpoffice/phpspreadsheet');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    /** @param  array<int, mixed>  $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
