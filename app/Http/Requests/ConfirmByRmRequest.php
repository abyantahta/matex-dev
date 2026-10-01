<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfirmByRmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('confirmAsRm', $this->route('purchase_order')) ?? false;
    }

    /**
     * Supplier RM mengirim alokasi qty per (item × tanggal). Jadwal dari
     * Purchasing hanya usulan — RM bebas memindah/memecah/mengubah qty,
     * selama tanggalnya masih di bulan due date PO (satu kolom per hari di
     * matrix konfirmasi). Qty 0 = tanggal itu dibatalkan.
     */
    public function rules(): array
    {
        /** @var PurchaseOrder|null $po */
        $po = $this->route('purchase_order');
        $dueDate = $po?->due_date;

        return [
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.purchase_order_item_id' => [
                'required',
                'integer',
                Rule::exists('purchase_order_items', 'id')
                    ->where('purchase_order_id', $po?->id),
            ],
            'schedules.*.scheduled_date' => array_filter([
                'required',
                'date_format:Y-m-d',
                $dueDate ? 'after_or_equal:'.$dueDate->copy()->startOfMonth()->toDateString() : null,
                $dueDate ? 'before_or_equal:'.$dueDate->copy()->endOfMonth()->toDateString() : null,
            ]),
            'schedules.*.qty_confirmed' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedules.*.scheduled_date.after_or_equal' => 'Tanggal kirim harus di bulan due date PO.',
            'schedules.*.scheduled_date.before_or_equal' => 'Tanggal kirim harus di bulan due date PO.',
            'schedules.*.qty_confirmed.integer' => 'Qty harus bilangan bulat.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var PurchaseOrder|null $po */
                $po = $this->route('purchase_order');
                if (! $po || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $totals = collect($validator->getData()['schedules'] ?? [])
                    ->groupBy('purchase_order_item_id')
                    ->map(fn ($rows) => $rows->sum(fn ($r) => (int) ($r['qty_confirmed'] ?? 0)));

                $missing = $po->items()->with('item:id,item_number')->get()
                    ->filter(fn ($item) => ($totals->get($item->id) ?? 0) <= 0)
                    ->map(fn ($item) => $item->item?->item_number ?? "#{$item->id}");

                if ($missing->isNotEmpty()) {
                    $validator->errors()->add(
                        'schedules',
                        'Setiap part harus punya minimal satu tanggal dengan qty: '.$missing->join(', ').'.'
                    );
                }
            },
        ];
    }
}
