<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
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
            'email' => 'required|email|unique:users,email,'.$this->route('user')->id,
            'phone_number' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(User::ROLES)],
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::in(['super_admin', 'operations', 'finance', 'support'])],
            'is_active' => 'boolean',
        ];
    }
}
