<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'layout_json',
    ];

    protected function casts(): array
    {
        return [
            'layout_json' => 'array',
        ];
    }

    /** @return list<array<string, mixed>> */
    public function blocks(): array
    {
        $layout = $this->layout_json ?? [];

        return is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [];
    }
}
