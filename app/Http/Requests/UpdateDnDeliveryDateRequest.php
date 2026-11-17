<?php

namespace App\Http\Requests;

use App\Support\DnQtyCap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDnDeliveryDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('delivery_note');

        return $this->user()?->can('updateDeliveryDate', $note) ?? false;
    }

    public function rules(): array
    {
        return [
            'delivery_date' => ['required', 'date'],
            'qty' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('qty')) {
                return;
            }

            $note = $this->route('delivery_note');
            $max = DnQtyCap::maxFor($note);
            $qty = (int) $this->input('qty');

            if ($qty > $max) {
                $validator->errors()->add(
                    'qty',
                    "Total surat jalan tidak boleh melebihi qty PO ({$max} kg tersisa)."
                );
            }
        });
    }
}
