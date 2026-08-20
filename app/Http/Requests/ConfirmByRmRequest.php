<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmByRmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmAsRm', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.qty_confirmed' => ['required', 'integer', 'gt:0'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.id' => ['required', 'exists:delivery_schedules,id'],
            'schedules.*.qty_confirmed' => ['required', 'integer', 'gt:0'],
            'schedules.*.scheduled_date' => ['nullable', 'date'],
        ];
    }
}
