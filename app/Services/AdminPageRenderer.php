<?php

namespace App\Services;

use App\Models\AdminPage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

final class AdminPageRenderer
{
    public function __construct(
        private PageBuilderLayoutService $layouts,
    ) {}

    public function render(AdminPage $page, bool $safe = false): string
    {
        $html = '<div class="admin-built-page">';

        foreach ($page->blocks() as $block) {
            $html .= $this->renderBlock($block, $safe);
        }

        $html .= '</div>';

        return $html;
    }

    public function renderBlocks(array $blocks, bool $safe = false): string
    {
        $html = '';
        foreach ($this->layouts->normalizeBlocks(['blocks' => $blocks]) as $block) {
            $html .= $this->renderBlock($block, $safe);
        }

        return $html;
    }

    /** @param  array<string, mixed>  $block */
    private function renderBlock(array $block, bool $safe): string
    {
        $type = (string) ($block['type'] ?? 'text');
        $props = is_array($block['props'] ?? null) ? $block['props'] : $block;

        if ($this->isModernBlock($type)) {
            if ($safe && $type === 'chart_block') {
                return '<div class="alert alert-secondary small mb-3">[Chart preview in Page Builder]</div>';
            }

            try {
                return View::make('components.page-builder.render-blocks', [
                    'blocks' => [['type' => $type, 'props' => $props]],
                ])->render();
            } catch (\Throwable) {
                return '<div class="alert alert-warning small mb-3">Could not render '.$type.'</div>';
            }
        }

        $content = e((string) ($props['content'] ?? $block['content'] ?? ''));
        $title = e((string) ($props['title'] ?? $block['title'] ?? ''));
        $body = e((string) ($props['body'] ?? $block['body'] ?? ''));
        $url = e((string) ($props['url'] ?? $block['url'] ?? '#'));
        $label = e((string) ($props['label'] ?? $block['label'] ?? 'Button'));

        if ($safe && in_array($type, ['html', 'script'], true)) {
            return '<div class="alert alert-secondary small mb-3">['.e($type).' block hidden in live overlay — preview in Page Builder]</div>';
        }

        $rawHtml = (string) ($props['html'] ?? $block['html'] ?? '');
        $script = (string) ($props['script'] ?? $block['script'] ?? '');

        return match ($type) {
            'text', 'text_block' => $type === 'text_block'
                ? View::make('components.page-builder.text-block', [
                    'heading' => (string) ($props['heading'] ?? ''),
                    'body' => (string) ($props['body'] ?? $content),
                ])->render()
                : "<div class=\"mb-3\"><p class=\"mb-0\">".nl2br($content)."</p></div>",
            'card' => "<div class=\"card mb-3 shadow-sm\"><div class=\"card-body\"><h5 class=\"card-title\">{$title}</h5><p class=\"card-text mb-0\">".nl2br($body)."</p></div></div>",
            'table' => $this->renderTable($props ?: $block),
            'button' => "<div class=\"mb-3\"><a href=\"{$url}\" class=\"btn btn-primary\">{$label}</a></div>",
            'chart' => "<div class=\"card mb-3\"><div class=\"card-header fw-semibold\">".($title ?: 'Chart')."</div><div class=\"card-body text-muted\">Chart placeholder: {$content}</div></div>",
            'form' => "<div class=\"card mb-3\"><div class=\"card-header fw-semibold\">".($title ?: 'Form')."</div><div class=\"card-body\"><p class=\"text-muted mb-0\">".nl2br($content)."</p></div></div>",
            'divider' => '<hr class="my-4">',
            'spacer' => '<div style="height:2rem"></div>',
            'html' => "<div class=\"mb-3\">{$rawHtml}</div>",
            'script' => $script !== '' ? "<script>{$script}</script>" : '',
            default => '',
        };
    }

    private function isModernBlock(string $type): bool
    {
        return array_key_exists($type, config('page-builder.components', []));
    }

    /** @param  array<string, mixed>  $block */
    private function renderTable(array $block): string
    {
        $headers = $block['headers'] ?? ['Column 1', 'Column 2'];
        $rows = $block['rows'] ?? [['—', '—']];

        if (! is_array($headers)) {
            $headers = ['Column 1', 'Column 2'];
        }

        $html = '<div class="table-responsive mb-3"><table class="table table-sm table-bordered"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>'.e((string) $h).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ((array) $rows as $row) {
            $html .= '<tr>';
            foreach ((array) $row as $cell) {
                $html .= '<td>'.e((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    /** @return array<string, mixed> */
    public static function defaultLayout(): array
    {
        return [
            'blocks' => [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'hero_block',
                    'props' => config('page-builder.components.hero_block.defaults'),
                ],
            ],
        ];
    }
}
