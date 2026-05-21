<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeRoleRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\Distributor;
use App\Models\User;
use App\Services\AdminImpersonationService;
use App\Services\AdminUserService;
use App\Support\ListPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($b) use ($q) {
                $b->where('first_name', 'like', $q)
                    ->orWhere('last_name', 'like', $q)
                    ->orWhere('name', 'like', $q)
                    ->orWhere('email', 'like', $q);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.users.index', [
            'users' => $query->paginate(ListPage::perPage($request, 20))->withQueryString(),
            'roles' => UserRole::creatableBySuperAdmin(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $user->load(['sponsor:id,name,first_name,last_name', 'assignedDistributor:id,name', 'distributorProfile', 'wallet']);

        $activityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(ListPage::perPage($request, 20), ['*'], 'activity_page')
            ->withQueryString();

        $referredOperators = collect();
        if ($user->isDistributor() && $user->distributorProfile) {
            $referredOperators = User::query()
                ->where('role', UserRole::Operator)
                ->where('distributor_id', $user->distributorProfile->id)
                ->orderBy('name')
                ->get();
        }

        if ($user->isOperator()) {
            $referredOperators = $user->referrals()->orderBy('name')->get();
        }

        return view('admin.users.show', [
            'user' => $user,
            'activityLogs' => $activityLogs,
            'referredOperators' => $referredOperators,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => UserRole::creatableBySuperAdmin(),
            'statuses' => UserStatus::cases(),
            'distributors' => Distributor::query()->where('is_main', false)->orderBy('name')->get(),
            'sponsors' => User::query()
                ->whereIn('role', [UserRole::Operator, UserRole::Distributor])
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name', 'role']),
        ]);
    }

    public function store(StoreUserRequest $request, AdminUserService $users): RedirectResponse
    {
        $user = $users->create($request->validated(), $request->file('profile_photo'));

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
            'distributors' => Distributor::query()->where('is_main', false)->orderBy('name')->get(),
            'sponsors' => User::query()
                ->whereIn('role', [UserRole::Operator, UserRole::Distributor])
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name', 'role']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AdminUserService $users): RedirectResponse
    {
        $users->update($user, $request->validated(), $request->file('profile_photo'));

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user, AdminUserService $users): RedirectResponse
    {
        $request->validate(['confirm_delete' => ['required', 'in:DELETE']]);

        try {
            $users->delete($user);
        } catch (RuntimeException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $user, AdminUserService $users): RedirectResponse
    {
        $users->resetPassword($user, $request->validated('password'));

        return back()->with('success', 'Password reset successfully.');
    }

    public function changeRole(ChangeRoleRequest $request, User $user, AdminUserService $users): RedirectResponse
    {
        try {
            $users->changeRole($user, UserRole::from($request->validated('role')));
        } catch (RuntimeException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        return back()->with('success', 'Role updated.');
    }

    public function toggleStatus(User $user, AdminUserService $users): RedirectResponse
    {
        try {
            $users->toggleStatus($user);
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'User status updated.');
    }

    public function forceLogout(User $user, AdminImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->forceLogout($user);

        return back()->with('success', 'All sessions for this user have been cleared.');
    }

    public function impersonate(User $user, AdminImpersonationService $impersonation): RedirectResponse
    {
        $admin = request()->user();

        try {
            $impersonation->impersonate($admin, $user);
        } catch (RuntimeException $e) {
            return back()->withErrors(['impersonate' => $e->getMessage()]);
        }

        return redirect($this->dashboardRouteFor($user))
            ->with('success', 'You are now impersonating '.$user->displayName().'.');
    }

    public function stopImpersonate(AdminImpersonationService $impersonation): RedirectResponse
    {
        if (! $impersonation->isImpersonating()) {
            return redirect()->route('admin.dashboard');
        }

        $impersonation->stop();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Impersonation ended.');
    }

    public function relatedData(User $user): RedirectResponse
    {
        if ($user->isOperator()) {
            return redirect()->route('admin.users.show', $user)->withFragment('tab-related');
        }

        if ($user->isDistributor()) {
            return redirect()->route('admin.distributors.orders', $user);
        }

        return redirect()->route('admin.users.show', $user)->withFragment('tab-related');
    }

    private function dashboardRouteFor(User $user): string
    {
        return match ($user->role) {
            UserRole::SuperAdmin, UserRole::PurchasingAdmin, UserRole::FinanceAdmin, UserRole::ItAdmin => route('admin.dashboard'),
            UserRole::Distributor => route('distributor.dashboard'),
            UserRole::Operator => route('operator.dashboard'),
        };
    }
}
