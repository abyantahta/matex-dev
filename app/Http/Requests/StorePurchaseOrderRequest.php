<?php

namespace App\Http\Requests;

use App\Enums\CompanyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\PurchaseOrder::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'po_number' => ['required', 'string', 'max:50', 'unique:purchase_orders,po_number'],
            'supplier_rm_id' => ['required', 'exists:companies,id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id', 'distinct'],
            'items.*.qty_ordered' => ['required', 'integer', 'gt:0'],
            'items.*.schedules' => ['required', 'array', 'min:1'],
            'items.*.schedules.*.scheduled_date' => ['required', 'date'],
            'items.*.schedules.*.qty' => ['required', 'integer', 'gt:0'],
            'items.*.schedules.*.ohp_supplier_id' => [
                'required',
                'different:supplier_rm_id',
                Rule::exists('companies', 'id')->where('type', CompanyType::Ohp->value),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $ordered = (int) round((float) ($item['qty_ordered'] ?? 0));
                $scheduled = (int) round(
                    collect($item['schedules'] ?? [])->sum(fn ($s) => (float) ($s['qty'] ?? 0))
                );

                if ($scheduled !== $ordered) {
                    $remaining = $ordered - $scheduled;
                    $validator->errors()->add(
                        "items.$index.schedules",
                        $remaining > 0
                            ? "Total qty jadwal harus sama dengan qty order. Masih ada sisa {$remaining} kg."
                            : 'Total qty jadwal melebihi qty order.'
                    );
                }
            }
        });
    }
}
