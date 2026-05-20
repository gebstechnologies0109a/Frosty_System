<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KiloPurchase extends Model
{
    protected $fillable = [
        'store_id',
        'member_id',
        'kilos',
        'direct_points',
        'purchased_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'kilos' => 'decimal:2',
            'direct_points' => 'decimal:2',
            'purchased_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
