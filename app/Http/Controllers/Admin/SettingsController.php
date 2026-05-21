<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'qualification_points' => SystemSetting::get('qualification_points', (string) config('frosty.qualification_points')),
                'peso_per_point' => SystemSetting::get('peso_per_point', (string) config('frosty.peso_per_point')),
                'override_level_1_percent' => SystemSetting::get('override_level_1_percent', (string) config('frosty.override_percentages.1')),
                'override_level_2_percent' => SystemSetting::get('override_level_2_percent', (string) config('frosty.override_percentages.2')),
                'override_level_3_percent' => SystemSetting::get('override_level_3_percent', (string) config('frosty.override_percentages.3')),
                'override_level_4_percent' => SystemSetting::get('override_level_4_percent', (string) config('frosty.override_percentages.4')),
            ],
        ]);
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'qualification_points' => ['required', 'integer', 'min:1'],
            'peso_per_point' => ['required', 'numeric', 'min:0'],
            'override_level_1_percent' => ['required', 'numeric', 'min:0'],
            'override_level_2_percent' => ['required', 'numeric', 'min:0'],
            'override_level_3_percent' => ['required', 'numeric', 'min:0'],
            'override_level_4_percent' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, (string) $value);
        }

        $logger->log($request->user(), 'settings.updated', $data);

        return back()->with('success', 'Settings saved.');
    }

    public function logs(): View
    {
        return view('admin.settings.logs', [
            'logs' => ActivityLog::query()->with('user')->latest()->paginate(40),
        ]);
    }
}
