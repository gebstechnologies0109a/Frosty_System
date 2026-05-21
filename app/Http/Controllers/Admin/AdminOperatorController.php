<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderType;
use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreOperatorRequest;
use App\Http\Requests\Admin\UpdateOperatorRequest;
use App\Models\Distributor;
use App\Models\OperatorInventory;
use App\Models\OperatorProduct;
use App\Models\Order;
use App\Models\PosDailyClosing;
use App\Models\User;
use App\Services\AdminOperatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AdminOperatorController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', UserRole::Operator)
            ->with('assignedDistributor:id,name')
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(fn ($b) => $b->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('distributor_id')) {
            $query->where('distributor_id', $request->integer('distributor_id'));
        }

        return view('admin.operators.index', [
            'operators' => $query->paginate(20)->withQueryString(),
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.operators.create', [
            'distributors' => Distributor::query()->where('is_main', false)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOperatorRequest $request, AdminOperatorService $service): RedirectResponse
    {
        $operator = $service->create($request->validated());

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('success', 'Operator created.');
    }

    public function show(User $operator): View
    {
        $this->ensureOperator($operator);
        $operator->load('assignedDistributor:id,name', 'wallet');

        return view('admin.operators.show', [
            'operator' => $operator,
            'orderCount' => Order::query()->where('operator_id', $operator->id)->count(),
            'posCount' => Order::query()->where('operator_id', $operator->id)->where('order_type', OrderType::Pos)->count(),
        ]);
    }

    public function edit(User $operator): View
    {
        $this->ensureOperator($operator);

        return view('admin.operators.edit', [
            'operator' => $operator,
            'distributors' => Distributor::query()->where('is_main', false)->orderBy('name')->get(),
            'statuses' => UserStatus::cases(),
            'regions' => PriceRegion::cases(),
        ]);
    }

    public function update(UpdateOperatorRequest $request, User $operator, AdminOperatorService $service): RedirectResponse
    {
        $this->ensureOperator($operator);
        $service->update($operator, $request->validated());

        return redirect()
            ->route('admin.operators.show', $operator)
            ->with('success', 'Operator updated.');
    }

    public function destroy(User $operator, AdminOperatorService $service): RedirectResponse
    {
        $this->ensureOperator($operator);

        try {
            $service->delete($operator);
        } catch (RuntimeException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Operator deleted.');
    }

    public function toggleStatus(User $operator, AdminOperatorService $service): RedirectResponse
    {
        $this->ensureOperator($operator);
        $service->toggleStatus($operator);

        return back()->with('success', 'Operator status updated.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $operator, AdminOperatorService $service): RedirectResponse
    {
        $this->ensureOperator($operator);
        $service->resetPassword($operator, $request->validated('password'));

        return back()->with('success', 'Password reset successfully.');
    }

    public function inventory(User $operator): View
    {
        $this->ensureOperator($operator);

        $items = OperatorInventory::query()
            ->where('operator_id', $operator->id)
            ->with('product:id,name,category')
            ->orderBy('product_id')
            ->paginate(30);

        return view('admin.operators.inventory', compact('operator', 'items'));
    }

    public function storeMenu(User $operator): View
    {
        $this->ensureOperator($operator);

        $products = OperatorProduct::query()
            ->where('operator_id', $operator->id)
            ->orderBy('product_name')
            ->paginate(30);

        return view('admin.operators.store-menu', compact('operator', 'products'));
    }

    public function posLogs(Request $request, User $operator): View
    {
        $this->ensureOperator($operator);

        $orders = Order::query()
            ->pos()
            ->where('operator_id', $operator->id)
            ->with(['items.operatorProduct'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.operators.pos-logs', compact('operator', 'orders'));
    }

    public function dailyClosings(User $operator): View
    {
        $this->ensureOperator($operator);

        $closings = PosDailyClosing::query()
            ->where('operator_id', $operator->id)
            ->with('approver:id,name')
            ->orderByDesc('closing_date')
            ->paginate(25);

        return view('admin.operators.daily-closings', compact('operator', 'closings'));
    }

    private function ensureOperator(User $user): void
    {
        abort_unless($user->role === UserRole::Operator, 404);
    }
}
