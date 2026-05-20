<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyMemberSummary extends Model
{
    protected $fillable = [
        'member_id',
        'year',
        'month',
        'total_kilos',
        'total_direct_points',
        'total_override_points',
        'override_qualified',
    ];

    protected function casts(): array
    {
        return [
            'total_kilos' => 'decimal:2',
            'total_direct_points' => 'decimal:2',
            'total_override_points' => 'decimal:2',
            'override_qualified' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
