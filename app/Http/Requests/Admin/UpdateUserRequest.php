<?php

namespace App\Http\Requests\Admin;

use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'sponsor_id' => ['nullable', 'integer', 'exists:users,id'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'region' => ['nullable', Rule::enum(PriceRegion::class)],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
