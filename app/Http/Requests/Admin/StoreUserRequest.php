<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(UserRole::creatableBySuperAdmin()))],
            'password' => ['required', 'string', Password::defaults()],
            'distributor_id' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::Operator->value),
                'nullable',
                'integer',
                'exists:distributors,id',
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'distributor_id' => 'distributor',
        ];
    }
}
