<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Distributor;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => UserRole::creatableBySuperAdmin(),
            'distributors' => Distributor::query()
                ->where('is_main', false)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreUserRequest $request, AdminUserService $users): RedirectResponse
    {
        $user = $users->create($request->validated());

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "User {$user->name} ({$user->email}) was created successfully.");
    }
}
