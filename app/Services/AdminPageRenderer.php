<?php

namespace App\Services;

use App\Models\AdminPage;
use Illuminate\Support\Str;

final class AdminPageRenderer
{
    public function render(AdminPage $page, bool $safe = false): string
    {
        $html = '<div class="admin-built-page">';

        foreach ($page->blocks() as $block) {
            $html .= $this->renderBlock($block, $safe);
        }

        $html .= '</div>';

        return $html;
    }

    /** @param  array<string, mixed>  $block */
    private function renderBlock(array $block, bool $safe): string
    {
        $type = (string) ($block['type'] ?? 'text');
        $content = e((string) ($block['content'] ?? ''));
        $title = e((string) ($block['title'] ?? ''));
        $body = e((string) ($block['body'] ?? ''));
        $url = e((string) ($block['url'] ?? '#'));
        $label = e((string) ($block['label'] ?? 'Button'));

        if ($safe && in_array($type, ['html', 'script'], true)) {
            return '<div class="alert alert-secondary small mb-3">['.e($type).' block hidden in live overlay — preview in Page Builder]</div>';
        }

        $rawHtml = (string) ($block['html'] ?? '');
        $script = (string) ($block['script'] ?? '');

        return match ($type) {
            'text' => "<div class=\"mb-3\"><p class=\"mb-0\">".nl2br($content)."</p></div>",
            'card' => "<div class=\"card mb-3 shadow-sm\"><div class=\"card-body\"><h5 class=\"card-title\">{$title}</h5><p class=\"card-text mb-0\">".nl2br($body)."</p></div></div>",
            'table' => $this->renderTable($block),
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
                    'type' => 'text',
                    'content' => 'Welcome to your new page.',
                ],
            ],
        ];
    }
}
