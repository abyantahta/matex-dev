<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSignedPoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('uploadSignedPo', $this->route('purchase_order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'signed_po' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
