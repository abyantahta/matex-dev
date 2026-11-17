<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approveAsPurchasing', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        $po = $this->route('purchase_order');

        return [
            'qad_remarks' => ['nullable', 'string', 'max:40'],
            'items' => ['nullable', 'array'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('purchase_order_items', 'id')->where('purchase_order_id', $po?->id),
            ],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
