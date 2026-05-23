<?php

namespace App\Models;

use App\Enums\AdminPageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Throwable;

class AdminPage extends Model
{
    /** @var array<string, array{label: string, function: string}> */
    public const BLOCK_TYPES = [
        'text' => ['label' => 'Text block', 'function' => 'Paragraph or heading content'],
        'card' => ['label' => 'Card block', 'function' => 'Titled card with body text'],
        'table' => ['label' => 'Table block', 'function' => 'Data table with headers and rows'],
        'button' => ['label' => 'Button block', 'function' => 'Call-to-action link button'],
        'chart' => ['label' => 'Chart block', 'function' => 'Chart placeholder / data summary'],
        'form' => ['label' => 'Form block', 'function' => 'Form section description'],
        'divider' => ['label' => 'Divider', 'function' => 'Horizontal rule separator'],
        'spacer' => ['label' => 'Spacer', 'function' => 'Vertical whitespace'],
        'html' => ['label' => 'HTML block', 'function' => 'Custom raw HTML markup'],
        'script' => ['label' => 'Script block', 'function' => 'Embedded client script'],
    ];

    protected $attributes = [
        'status' => 'published',
        'is_system' => false,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'slug',
        'title',
        'description',
        'status',
        'route_name',
        'path',
        'is_system',
        'layout_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'layout_json' => 'array',
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'status' => AdminPageStatus::class,
        ];
    }

    public static function findByRoute(?string $routeName): ?self
    {
        if (! $routeName) {
            return null;
        }

        return static::query()->where('route_name', $routeName)->first();
    }

    public function pageStatus(): AdminPageStatus
    {
        return $this->status ?? AdminPageStatus::Published;
    }

    public function isPublished(): bool
    {
        return $this->pageStatus() === AdminPageStatus::Published;
    }

    public function canOpenLive(): bool
    {
        return $this->resolveLiveUrl() !== null;
    }

    public function liveUrl(): string
    {
        return $this->resolveLiveUrl() ?? route('admin.page-builder.index');
    }

    public function liveUrlLabel(): string
    {
        if ($this->route_name || $this->path) {
            return $this->path ?? '/admin';
        }

        return '/p/'.$this->slug;
    }

    public function resolveLiveUrl(): ?string
    {
        try {
            if ($this->route_name && Route::has($this->route_name)) {
                $route = Route::getRoutes()->getByName($this->route_name);
                if ($route && count($route->parameterNames()) === 0) {
                    return route($this->route_name);
                }
            }

            if ($this->path) {
                return url($this->path);
            }

            if ($this->slug && ! $this->is_system) {
                return route('pages.show', $this->slug);
            }
        } catch (Throwable $e) {
            Log::warning('AdminPage live URL resolution failed', [
                'page_id' => $this->id,
                'slug' => $this->slug,
                'route_name' => $this->route_name,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function blocks(): array
    {
        return app(\App\Services\PageBuilderLayoutService::class)
            ->normalizeBlocks($this->layout_json ?? []);
    }

    public function isPublishedFlag(): bool
    {
        return $this->isPublished();
    }

    public function blockCount(): int
    {
        return count($this->blocks());
    }

    /** @return list<array{position: int, type: string, label: string, function: string, summary: string}> */
    public function layoutOutline(): array
    {
        $outline = [];

        foreach ($this->blocks() as $index => $block) {
            $type = (string) ($block['type'] ?? 'text');
            $meta = self::BLOCK_TYPES[$type] ?? ['label' => ucfirst($type), 'function' => 'Custom block'];
            $outline[] = [
                'position' => $index + 1,
                'type' => $type,
                'label' => $meta['label'],
                'function' => $meta['function'],
                'summary' => $this->blockSummary($block, $type),
            ];
        }

        return $outline;
    }

    /** @param  array<string, mixed>  $block */
    private function blockSummary(array $block, string $type): string
    {
        return match ($type) {
            'text' => \Illuminate\Support\Str::limit((string) ($block['content'] ?? ''), 40) ?: '—',
            'card' => \Illuminate\Support\Str::limit((string) ($block['title'] ?? 'Untitled card'), 40),
            'table' => count($block['headers'] ?? []).' columns',
            'button' => (string) ($block['label'] ?? 'Button'),
            'chart', 'form' => \Illuminate\Support\Str::limit((string) ($block['title'] ?? $type), 40),
            'html' => 'Custom HTML',
            'script' => 'Script',
            'divider', 'spacer' => '—',
            default => '—',
        };
    }
}
