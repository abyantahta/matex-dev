<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receive', $this->route('delivery_note')) ?? false;
    }

    public function rules(): array
    {
        return [
            'received_qty' => ['nullable', 'integer', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
