<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(UserRole::creatableBySuperAdmin()))],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => ['required_without:generate_password', 'nullable', 'string', Password::defaults()],
            'generate_password' => ['sometimes', 'boolean'],
            'sponsor_id' => ['nullable', 'integer', 'exists:users,id'],
            'distributor_id' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::Operator->value),
                'nullable',
                'integer',
                'exists:distributors,id',
            ],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('generate_password') && ! $this->filled('password')) {
            $this->merge(['password' => bin2hex(random_bytes(8))]);
        }
    }
}
