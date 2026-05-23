<?php

namespace App\Services;

use Illuminate\Support\Str;

final class PageBuilderLayoutService
{
    /** @return list<array<string, mixed>> */
    public function normalizeBlocks(mixed $layout): array
    {
        if (is_string($layout)) {
            $layout = json_decode($layout, true);
        }

        if (! is_array($layout)) {
            return [];
        }

        if (isset($layout['blocks']) && is_array($layout['blocks'])) {
            $blocks = $layout['blocks'];
        } elseif (array_is_list($layout)) {
            $blocks = $layout;
        } else {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($block) => $this->normalizeBlock(is_array($block) ? $block : []),
            $blocks,
        )));
    }

    /** @param  array<string, mixed>  $block */
    public function normalizeBlock(array $block): ?array
    {
        $type = (string) ($block['type'] ?? '');
        if ($type === '') {
            return null;
        }

        $id = (string) ($block['id'] ?? Str::uuid());
        $props = $block['props'] ?? null;

        if (! is_array($props)) {
            $props = $block;
            unset($props['id'], $props['type']);
        }

        return [
            'id' => $id,
            'type' => $type,
            'props' => $props,
        ];
    }

    /** @return array{blocks: list<array<string, mixed>>} */
    public function wrapBlocks(array $blocks): array
    {
        return ['blocks' => array_values($blocks)];
    }

    /** @return list<array<string, mixed>> */
    public function templateBlocks(string $templateKey): array
    {
        $template = config("page-builder.templates.{$templateKey}");
        if (! is_array($template) || ! isset($template['blocks'])) {
            return [];
        }

        $blocks = [];
        foreach ($template['blocks'] as $row) {
            $type = (string) ($row['type'] ?? '');
            $props = is_array($row['props'] ?? null) ? $row['props'] : [];
            $defaults = config("page-builder.components.{$type}.defaults", []);
            $blocks[] = [
                'id' => (string) Str::uuid(),
                'type' => $type,
                'props' => array_merge($defaults, $props),
            ];
        }

        return $blocks;
    }

    public function defaultComponentProps(string $type): array
    {
        return config("page-builder.components.{$type}.defaults", []);
    }

    public function newBlock(string $type): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'props' => $this->defaultComponentProps($type),
        ];
    }
}
