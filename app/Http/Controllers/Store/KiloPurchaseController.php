<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Store;
use App\Services\FrostyRewardEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KiloPurchaseController extends Controller
{
    public function create(Request $request): View
    {
        return view('store.kilo-form', [
            'stores' => Store::query()->where('is_active', true)->orderBy('name')->get(),
            'members' => Member::query()->orderBy('name')->get(),
            'selectedStoreId' => $request->integer('store_id') ?: null,
        ]);
    }

    public function store(Request $request, FrostyRewardEngine $engine): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'member_id' => ['required', 'exists:members,id'],
            'kilos' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $store = Store::query()->findOrFail($validated['store_id']);
        $member = Member::query()->findOrFail($validated['member_id']);

        $result = $engine->recordPurchase(
            $store,
            $member,
            (float) $validated['kilos'],
            notes: $validated['notes'] ?? null,
        );

        $purchase = $result['purchase'];

        return redirect()
            ->route('store.kilos.create', ['store_id' => $store->id])
            ->with('success', sprintf(
                'Recorded %.2f kg for %s — %.2f direct points awarded.',
                $purchase->kilos,
                $purchase->member->name,
                $purchase->direct_points,
            ));
    }
}
