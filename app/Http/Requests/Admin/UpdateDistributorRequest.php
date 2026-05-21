<?php

namespace App\Http\Requests\Admin;

use App\Enums\DistributorPricingRegion;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $distributor = $this->route('distributor');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($distributor?->id)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'is_main' => ['sometimes', 'boolean'],
            'pricing_region' => ['required', Rule::enum(DistributorPricingRegion::class)],
        ];
    }
}
