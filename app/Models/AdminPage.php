<?php

namespace App\Models;

use App\Enums\AdminPageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

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

    protected $fillable = [
        'slug',
        'title',
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

    public function isPublished(): bool
    {
        return $this->status === AdminPageStatus::Published;
    }

    public function liveUrl(): string
    {
        if ($this->route_name && Route::has($this->route_name)) {
            return route($this->route_name);
        }

        if ($this->path) {
            return url($this->path);
        }

        return route('pages.show', $this->slug);
    }

    public function liveUrlLabel(): string
    {
        if ($this->route_name || $this->path) {
            return $this->path ?? '/admin';
        }

        return '/p/'.$this->slug;
    }

    /** @return list<array<string, mixed>> */
    public function blocks(): array
    {
        $layout = $this->layout_json ?? [];

        return is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [];
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
