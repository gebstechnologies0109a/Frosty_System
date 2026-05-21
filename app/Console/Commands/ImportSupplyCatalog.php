<?php

namespace App\Console\Commands;

use App\Support\ProductCatalogImporter;
use Illuminate\Console\Command;

class ImportSupplyCatalog extends Command
{
    protected $signature = 'frosty:import-supply-catalog {file? : Path to JSON catalog}';

    protected $description = 'Import supply/sparepart products from JSON (0 points, regional prices)';

    public function handle(ProductCatalogImporter $importer): int
    {
        $path = $this->argument('file') ?? ProductCatalogImporter::defaultSupplyCatalogPath();

        $result = $importer->importFromJsonFile($path);

        $this->info("Imported {$result['imported']} products from {$path}");

        if ($result['skipped'] > 0) {
            $this->warn("Skipped {$result['skipped']} invalid rows.");
        }

        return self::SUCCESS;
    }
}
