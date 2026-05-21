<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorInventory extends Model
{
    protected $table = 'operator_inventory';

    protected $fillable = [
        'operator_id',
        'product_id',
        'stock',
        'minimum_stock',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockStatus(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        }

        $min = $this->minimum_stock ?? 10;

        return $this->stock < $min ? 'low_stock' : 'in_stock';
    }

    public function stockStatusLabel(): string
    {
        return match ($this->stockStatus()) {
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            default => 'Unknown',
        };
    }
}
