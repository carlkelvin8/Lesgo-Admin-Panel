<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users','email')->whereNull('deleted_at')->ignore($this->route('user')->id)],
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|in:customer,driver,partner,admin',
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::in(['super_admin', 'operations', 'finance', 'support'])],
            'is_active' => 'boolean',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_profile_picture' => 'nullable|boolean',
        ];
    }
}
