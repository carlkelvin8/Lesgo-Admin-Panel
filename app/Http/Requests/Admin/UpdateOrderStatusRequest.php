<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,accepted,driver_arrived,in_progress,picked_up,completed,cancelled',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:1000',
        ];
    }
}
