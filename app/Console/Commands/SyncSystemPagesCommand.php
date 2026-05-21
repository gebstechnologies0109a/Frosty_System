<?php

namespace App\Console\Commands;

use App\Enums\AdminPageStatus;
use App\Models\AdminPage;
use App\Services\SystemPagesRegistry;
use Illuminate\Console\Command;

class SyncSystemPagesCommand extends Command
{
    protected $signature = 'frosty:sync-pages';

    protected $description = 'Register all system admin pages into admin_pages for the Page Builder';

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;
        $position = (int) AdminPage::query()->max('sort_order');

        foreach (SystemPagesRegistry::pages() as $definition) {
            if (AdminPage::query()->where('slug', $definition['slug'])->exists()) {
                $skipped++;
                continue;
            }

            AdminPage::query()->create([
                'title' => $definition['title'],
                'slug' => $definition['slug'],
                'status' => AdminPageStatus::Published,
                'route_name' => $definition['route_name'],
                'path' => $definition['path'],
                'is_system' => true,
                'layout_json' => SystemPagesRegistry::defaultLayoutFor($definition['title']),
                'sort_order' => ++$position,
            ]);

            $created++;
        }

        $this->info("System pages synced: {$created} created, {$skipped} already existed.");

        return self::SUCCESS;
    }
}
