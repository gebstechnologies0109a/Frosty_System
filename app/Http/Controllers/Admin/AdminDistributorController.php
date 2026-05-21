<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DistributorPricingRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreDistributorRequest;
use App\Http\Requests\Admin\UpdateDistributorRequest;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminDistributorService;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AdminDistributorController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', UserRole::Distributor)
            ->with('distributorProfile')
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(fn ($b) => $b->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.distributors.index', [
            'distributors' => $query->paginate(ListPage::perPage($request, 20))->withQueryString(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function create(): View
    {
        try {
            return view('admin.distributors.create', [
                'pricingRegions' => DistributorPricingRegion::cases(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to load Add Distributor form', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function store(StoreDistributorRequest $request, AdminDistributorService $service): RedirectResponse
    {
        $user = $service->create($request->validated());

        return redirect()
            ->route('admin.distributors.show', $user)
            ->with('success', 'Distributor created.');
    }

    public function show(User $distributor): View
    {
        $this->ensureDistributor($distributor);
        $profile = $distributor->distributorProfile;

        return view('admin.distributors.show', [
            'distributor' => $distributor,
            'profile' => $profile,
            'operatorCount' => $profile ? $profile->assignedOperators()->count() : 0,
            'orderCount' => $profile ? Order::query()->where('distributor_id', $profile->id)->count() : 0,
        ]);
    }

    public function edit(User $distributor): View
    {
        $this->ensureDistributor($distributor);

        return view('admin.distributors.edit', [
            'distributor' => $distributor,
            'profile' => $distributor->distributorProfile,
            'statuses' => UserStatus::cases(),
            'pricingRegions' => DistributorPricingRegion::cases(),
        ]);
    }

    public function update(UpdateDistributorRequest $request, User $distributor, AdminDistributorService $service): RedirectResponse
    {
        $this->ensureDistributor($distributor);
        $profile = $distributor->distributorProfile;
        abort_unless($profile, 404);

        $service->update($distributor, $profile, $request->validated());

        return redirect()
            ->route('admin.distributors.show', $distributor)
            ->with('success', 'Distributor updated.');
    }

    public function destroy(User $distributor, AdminDistributorService $service): RedirectResponse
    {
        $this->ensureDistributor($distributor);
        $profile = $distributor->distributorProfile;
        abort_unless($profile, 404);

        try {
            $service->delete($distributor, $profile);
        } catch (RuntimeException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.distributors.index')
            ->with('success', 'Distributor deleted.');
    }

    public function toggleStatus(User $distributor, AdminDistributorService $service): RedirectResponse
    {
        $this->ensureDistributor($distributor);
        $service->toggleStatus($distributor);

        return back()->with('success', 'Distributor status updated.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $distributor, AdminDistributorService $service): RedirectResponse
    {
        $this->ensureDistributor($distributor);
        $service->resetPassword($distributor, $request->validated('password'));

        return back()->with('success', 'Password reset successfully.');
    }

    public function orders(Request $request, User $distributor): View
    {
        $this->ensureDistributor($distributor);
        $profile = $distributor->distributorProfile;
        abort_unless($profile, 404);

        $orders = Order::query()
            ->where('distributor_id', $profile->id)
            ->with(['user:id,name', 'items.product:id,name'])
            ->latest()
            ->paginate(ListPage::perPage($request, 20))
            ->withQueryString();

        return view('admin.distributors.orders', compact('distributor', 'profile', 'orders'));
    }

    private function ensureDistributor(User $user): void
    {
        abort_unless($user->role === UserRole::Distributor, 404);
    }
}
