<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $qty
 * @property float $price
 * @property int $points
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'operator_product_id',
        'product_id',
        'qty',
        'price',
        'cost_price',
        'line_total',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'points' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<OrderItem, Order> */
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function operatorProduct(): BelongsTo
    {
        return $this->belongsTo(OperatorProduct::class);
    }
}
