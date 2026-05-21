<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const ACTION_BULK_INCREASE = 'bulk_increase';

    public const ACTION_BULK_DECREASE = 'bulk_decrease';

    public const ACTION_BULK_SET = 'bulk_set';

    public const ACTION_MANUAL = 'manual_adjustment';

    public const ACTION_PRODUCT_CREATED = 'product_created';

    public const ACTION_PRODUCT_DELETED = 'product_deleted';

    public const ACTION_ORDER_DEDUCTION = 'order_deduction';

    public const ACTION_RESTOCK = 'restock';

    public const ACTION_IMPORT_ADJUSTMENT = 'import_adjustment';

    public const ACTION_OPERATOR_MANUAL = 'operator_manual_adjustment';

    public const ACTION_POS_SALE = 'pos_sale';

    public const ACTION_DISTRIBUTOR_ADJUSTMENT = 'distributor_inventory_adjustment';

    /** @var list<string> */
    public const ACTION_TYPES = [
        self::ACTION_BULK_INCREASE,
        self::ACTION_BULK_DECREASE,
        self::ACTION_BULK_SET,
        self::ACTION_MANUAL,
        self::ACTION_PRODUCT_CREATED,
        self::ACTION_PRODUCT_DELETED,
        self::ACTION_ORDER_DEDUCTION,
        self::ACTION_RESTOCK,
        self::ACTION_IMPORT_ADJUSTMENT,
        self::ACTION_OPERATOR_MANUAL,
        self::ACTION_DISTRIBUTOR_ADJUSTMENT,
    ];

    protected $fillable = [
        'product_id',
        'user_id',
        'action_type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action_type) {
            self::ACTION_BULK_INCREASE => 'Bulk increase',
            self::ACTION_BULK_DECREASE => 'Bulk decrease',
            self::ACTION_BULK_SET => 'Bulk set level',
            self::ACTION_MANUAL => 'Manual adjustment',
            self::ACTION_PRODUCT_CREATED => 'Product created',
            self::ACTION_PRODUCT_DELETED => 'Product deleted',
            self::ACTION_ORDER_DEDUCTION => 'Order deduction',
            self::ACTION_RESTOCK => 'Restock',
            self::ACTION_IMPORT_ADJUSTMENT => 'Import adjustment',
            self::ACTION_OPERATOR_MANUAL => 'Operator inventory adjustment',
            self::ACTION_POS_SALE => 'POS sale',
            self::ACTION_DISTRIBUTOR_ADJUSTMENT => 'Distributor inventory adjustment',
            default => ucfirst(str_replace('_', ' ', $this->action_type)),
        };
    }
}
