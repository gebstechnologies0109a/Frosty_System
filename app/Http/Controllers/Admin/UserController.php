<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\Distributor;
use App\Models\User;
use App\Services\AdminUserService;
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
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => UserRole::creatableBySuperAdmin(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['sponsor:id,name,first_name,last_name', 'assignedDistributor:id,name', 'distributorProfile', 'wallet']);

        $activityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15, ['*'], 'activity_page');

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
}
