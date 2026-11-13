<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(User::ROLES)],
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::in(['super_admin', 'operations', 'finance', 'support'])],
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
