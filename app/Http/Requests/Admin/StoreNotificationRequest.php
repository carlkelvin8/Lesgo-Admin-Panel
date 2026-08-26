<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_type' => 'required|in:user,role',
            'user_id' => 'required_if:recipient_type,user|nullable|exists:users,id',
            'recipient_role' => 'required_if:recipient_type,role|nullable|in:all,customer,driver,partner,admin',
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'channel' => 'required|in:in_app,push,sms,email',
            'data' => 'nullable|json',
        ];
    }
}
