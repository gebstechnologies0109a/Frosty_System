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
        $updated = 0;
        $position = (int) AdminPage::query()->max('sort_order');

        foreach (SystemPagesRegistry::pages() as $definition) {
            $page = AdminPage::query()->where('slug', $definition['slug'])->first();

            if ($page) {
                $page->update([
                    'title' => $definition['title'],
                    'route_name' => $definition['route_name'],
                    'path' => $definition['path'],
                    'is_system' => true,
                    'status' => $page->status ?? AdminPageStatus::Published,
                    'layout_json' => $page->layout_json ?? SystemPagesRegistry::defaultLayoutFor($definition['title']),
                ]);
                $updated++;

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

        $this->info("System pages synced: {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }
}
