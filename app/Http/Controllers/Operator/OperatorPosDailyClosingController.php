<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\PosDailyClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OperatorPosDailyClosingController extends Controller
{
    public function store(Request $request, PosDailyClosingService $closing): JsonResponse
    {
        $data = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $record = $closing->submit(
                Auth::user(),
                (float) $data['actual_cash'],
                $data['notes'] ?? null,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daily closing submitted for Super Admin approval.',
            'closing' => [
                'status' => $record->status->value,
                'variance' => (float) $record->variance,
            ],
        ]);
    }
}
