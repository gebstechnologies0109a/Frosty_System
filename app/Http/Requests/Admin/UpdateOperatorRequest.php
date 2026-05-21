<?php

namespace App\Http\Requests\Admin;

use App\Enums\PriceRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $operator = $this->route('operator');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($operator?->id)],
            'distributor_id' => ['required', 'integer', 'exists:distributors,id'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'region' => ['required', Rule::enum(PriceRegion::class)],
        ];
    }
}
