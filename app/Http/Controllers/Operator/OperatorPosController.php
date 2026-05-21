<?php

namespace App\Http\Controllers\Operator;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Services\OperatorPosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OperatorPosController extends Controller
{
    public function index(OperatorPosService $pos): View
    {
        $data = $pos->posPageData(Auth::user());

        return view('operator.pos.index', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'pnl' => $data['pnl'],
            'today' => $data['today'],
            'dayLocked' => $data['day_locked'],
        ]);
    }

    public function checkout(Request $request, OperatorPosService $pos): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.operator_product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,ewallet'],
        ]);

        try {
            $order = $pos->checkout(
                Auth::user(),
                $data['items'],
                PaymentMethod::from($data['payment_method']),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Checkout failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale completed.',
            'totals' => [
                'revenue' => (float) $order->total_amount,
                'cogs' => (float) $order->cogs_total,
                'profit' => (float) $order->gross_profit,
            ],
        ]);
    }
}
