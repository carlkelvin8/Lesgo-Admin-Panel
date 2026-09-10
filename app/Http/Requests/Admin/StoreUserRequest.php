<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionKeys = array_keys(config('admin.permissions', []));

        return [
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users','email')->whereNull('deleted_at')],
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|in:customer,driver,partner,admin',
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::in(['super_admin', 'operations', 'finance', 'support'])],
            'admin_permissions' => ['nullable', 'array'],
            'admin_permissions.*' => ['string', 'max:100'],
            'password' => 'required|string|min:8|confirmed',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'cropped_profile_picture' => 'nullable|string|max:10000000',
        ];
    }
}
