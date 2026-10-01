<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsBase64Uploads;
use Illuminate\Foundation\Http\FormRequest;

class UploadSignedPoRequest extends FormRequest
{
    use AcceptsBase64Uploads;

    public const MAX_KB = 10240;

    public function authorize(): bool
    {
        return $this->user()?->can('uploadSignedPo', $this->route('purchase_order')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeBase64Uploads(['signed_po' => self::MAX_KB]);
    }

    public function rules(): array
    {
        return [
            'signed_po' => ['required', 'file', 'mimes:pdf', 'max:'.self::MAX_KB],
        ];
    }

    public function messages(): array
    {
        $maxMb = self::MAX_KB / 1024;

        return [
            'signed_po.required' => 'Pilih file PDF Signed PO terlebih dahulu.',
            'signed_po.file' => "File gagal diunggah — kemungkinan ukurannya melebihi batas server. Maksimal {$maxMb} MB.",
            'signed_po.uploaded' => "File gagal diunggah — kemungkinan ukurannya melebihi batas server. Maksimal {$maxMb} MB.",
            'signed_po.mimes' => 'File harus berformat PDF.',
            'signed_po.max' => "Ukuran file maksimal {$maxMb} MB.",
        ];
    }
}
