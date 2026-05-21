<?php

namespace App\Services;

use App\Models\OperatorProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class OperatorProductsForSaleService
{
    public function __construct(
        private OperatorProductDefaultsService $defaults,
    ) {}

    /** @return array<string, mixed> */
    public function indexData(User $operator, Request $request): array
    {
        $this->defaults->ensureDefaults($operator);

        $query = OperatorProduct::query()
            ->where('operator_id', $operator->id)
            ->orderBy('product_name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->get()->map(fn (OperatorProduct $op) => [
            'model' => $op,
            'id' => $op->id,
            'name' => $op->product_name,
            'description' => $op->description,
            'selling_price' => (float) $op->price,
            'cost' => $op->cost !== null ? (float) $op->cost : null,
            'margin' => $op->cost !== null ? round((float) $op->price - (float) $op->cost, 2) : null,
            'status' => $op->status,
            'is_default' => $op->is_system_default,
            'image_url' => $op->imageUrl(),
        ]);

        return [
            'items' => $items,
            'filters' => $request->only(['status']),
        ];
    }

    public function store(
        User $operator,
        string $productName,
        float $price,
        string $status,
        ?string $description = null,
        ?float $cost = null,
        ?UploadedFile $image = null,
    ): OperatorProduct {
        $path = $image ? $this->storeImage($operator, $image) : null;

        return OperatorProduct::query()->create([
            'operator_id' => $operator->id,
            'product_name' => $productName,
            'description' => $description,
            'price' => $price,
            'cost' => $cost,
            'status' => $status,
            'image_path' => $path,
            'is_system_default' => false,
        ]);
    }

    public function update(
        OperatorProduct $op,
        string $productName,
        float $price,
        string $status,
        ?string $description = null,
        ?float $cost = null,
        ?UploadedFile $image = null,
        bool $removeImage = false,
    ): OperatorProduct {
        if ($image) {
            if ($op->image_path) {
                Storage::disk('public')->delete($op->image_path);
            }
            $op->image_path = $this->storeImage($op->operator, $image);
        } elseif ($removeImage && $op->image_path) {
            Storage::disk('public')->delete($op->image_path);
            $op->image_path = null;
        }

        $op->fill([
            'product_name' => $productName,
            'description' => $description,
            'price' => $price,
            'cost' => $cost,
            'status' => $status,
        ]);
        $op->save();

        return $op;
    }

    public function toggleStatus(OperatorProduct $op): OperatorProduct
    {
        $op->status = $op->status === 'active' ? 'inactive' : 'active';
        $op->save();

        return $op;
    }

    private function storeImage(User $operator, UploadedFile $image): string
    {
        return $image->store("operator-products/{$operator->id}", 'public');
    }

    /** @return list<array<string, mixed>> */
    public function publicMenu(User $operator): array
    {
        $this->defaults->ensureDefaults($operator);

        return OperatorProduct::query()
            ->where('operator_id', $operator->id)
            ->where('status', 'active')
            ->orderBy('product_name')
            ->get()
            ->map(fn (OperatorProduct $op) => [
                'name' => $op->product_name,
                'description' => $op->description,
                'price' => (float) $op->price,
                'image_url' => $op->imageUrl(),
            ])
            ->all();
    }
}
