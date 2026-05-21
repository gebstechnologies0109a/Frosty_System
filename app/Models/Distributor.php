<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    protected $fillable = [
        'name',
        'is_main',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOperators(): HasMany
    {
        return $this->hasMany(User::class, 'distributor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    public static function mainId(): int
    {
        return (int) config('frosty.main_distributor_id', 1);
    }
}
