<?php

namespace App\Models;

use App\Enums\PriceRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'region',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'region' => PriceRegion::class,
            'price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
