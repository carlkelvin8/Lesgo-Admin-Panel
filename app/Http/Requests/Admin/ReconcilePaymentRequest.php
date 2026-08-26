<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReconcilePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reconciliation_status' => ['required', 'in:matched,discrepancy,needs_review'],
            'reconciliation_notes' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
