<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;

final class StockMovementLogger
{
    public static function log(
        Product $product,
        ?User $user,
        string $actionType,
        int $stockBefore,
        int $stockAfter,
        ?string $description = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'quantity_change' => $stockAfter - $stockBefore,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'description' => $description,
        ]);
    }

    public static function logBulkAdjustment(
        Product $product,
        ?User $user,
        string $adjustmentType,
        int $amount,
        int $stockBefore,
        int $stockAfter,
    ): StockMovement {
        $actionType = match ($adjustmentType) {
            'increase' => StockMovement::ACTION_BULK_INCREASE,
            'decrease' => StockMovement::ACTION_BULK_DECREASE,
            'set' => StockMovement::ACTION_BULK_SET,
            default => StockMovement::ACTION_MANUAL,
        };

        return self::log(
            $product,
            $user,
            $actionType,
            $stockBefore,
            $stockAfter,
            sprintf('Bulk inventory %s by %d unit(s)', $adjustmentType, $amount),
        );
    }

    public static function logProductCreated(Product $product, ?User $user): StockMovement
    {
        ProductInventoryService::ensure($product);

        return self::log(
            $product,
            $user,
            StockMovement::ACTION_PRODUCT_CREATED,
            0,
            0,
            'Product created with initial stock of 0',
        );
    }

    public static function logProductDeleted(Product $product, ?User $user, int $stockBefore): StockMovement
    {
        return self::log(
            $product,
            $user,
            StockMovement::ACTION_PRODUCT_DELETED,
            $stockBefore,
            0,
            sprintf('Product deleted: %s (final stock %d)', $product->name, $stockBefore),
        );
    }

    public static function logOperatorAdjustment(
        Product $product,
        User $operator,
        int $stockBefore,
        int $stockAfter,
        string $note = '',
    ): StockMovement {
        $description = trim(sprintf(
            'Operator supplies inventory: %s (stock %d → %d)%s',
            $product->name,
            $stockBefore,
            $stockAfter,
            $note !== '' ? " — {$note}" : '',
        ));

        return self::log(
            $product,
            $operator,
            StockMovement::ACTION_OPERATOR_MANUAL,
            $stockBefore,
            $stockAfter,
            $description,
        );
    }
}
