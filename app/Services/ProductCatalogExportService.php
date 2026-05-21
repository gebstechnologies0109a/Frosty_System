<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductCatalogExportService
{
    /** @var list<string> */
    public const COLUMNS = [
        'product_id',
        'name',
        'category',
        'points',
        'status',
        'luzon_price',
        'davao_price',
        'tacloban_price',
        'stock',
        'created_at',
        'updated_at',
    ];

    public function download(Request $request, string $format, bool $filtered): StreamedResponse|\Illuminate\Http\Response
    {
        $products = ProductCatalogFilter::productsQuery($request, $filtered)->get();
        $rows = $this->buildRows($products);

        return match ($format) {
            'xlsx' => $this->xlsxResponse($rows),
            default => $this->csvResponse($rows),
        };
    }

    public function templateDownload(string $format): StreamedResponse|\Illuminate\Http\Response
    {
        $sample = [[
            '',
            'Sample Product',
            'supply',
            '0',
            'active',
            '100.00',
            '110.00',
            '110.00',
            '0',
            '',
            '',
        ]];

        return match ($format) {
            'xlsx' => $this->xlsxResponse($sample, 'frosty_product_import_template.xlsx'),
            default => $this->csvResponse($sample, 'frosty_product_import_template.csv'),
        };
    }

    /** @param  Collection<int, Product>  $products
     * @return list<list<string|int|float>>
     */
    private function buildRows(Collection $products): array
    {
        $rows = [];

        foreach ($products as $product) {
            $prices = $product->regionalPrices();
            $rows[] = [
                $product->id,
                $product->name,
                $product->category->value,
                $product->points,
                $product->status,
                $prices['luzon'] ?? 0,
                $prices['davao'] ?? 0,
                $prices['tacloban'] ?? 0,
                $product->stockLevel(),
                $product->created_at?->toDateTimeString() ?? '',
                $product->updated_at?->toDateTimeString() ?? '',
            ];
        }

        return $rows;
    }

    /** @param  list<list<string|int|float>>  $rows */
    private function csvResponse(array $rows, string $filename = 'frosty_products.csv'): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /** @param  list<list<string|int|float>>  $rows */
    private function xlsxResponse(array $rows, string $filename = 'frosty_products.xlsx'): \Illuminate\Http\Response
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            abort(500, 'Excel export requires phpoffice/phpspreadsheet. Run: composer require phpoffice/phpspreadsheet');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::COLUMNS, null, 'A1');

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowIndex);
            $rowIndex++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
