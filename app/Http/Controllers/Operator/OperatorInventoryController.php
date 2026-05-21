<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\OperatorInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OperatorInventoryController extends Controller
{
    public function index(Request $request, OperatorInventoryService $inventory): View
    {
        $operator = Auth::user();
        $data = $inventory->indexData($operator, $request);

        return view('operator.inventory.index', [
            'operator' => $operator,
            'grouped' => $data['grouped'],
            'categories' => $data['categories'],
            'filters' => $data['filters'],
        ]);
    }

    public function adjust(Request $request, OperatorInventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'mode' => ['required', 'in:increase,decrease,set'],
            'amount' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $inventory->adjust(
            Auth::user(),
            (int) $data['product_id'],
            $data['mode'],
            (int) $data['amount'],
            isset($data['minimum_stock']) ? (int) $data['minimum_stock'] : null,
        );

        return back()->with('success', 'Inventory updated.');
    }
}
