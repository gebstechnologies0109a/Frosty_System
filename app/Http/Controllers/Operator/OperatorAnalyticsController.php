<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\OperatorAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OperatorAnalyticsController extends Controller
{
    public function index(OperatorAnalyticsService $analytics): View
    {
        $operator = Auth::user();
        $data = $analytics->build($operator);

        return view('operator.analytics.index', [
            'operator' => $operator,
            'analytics' => $data,
            'level1to4Report' => $data['level1to4Report'],
            'level0to4Report' => $data['level0to4Report'],
        ]);
    }
}
