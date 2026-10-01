<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsBase64Uploads;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmOhpRequest extends FormRequest
{
    use AcceptsBase64Uploads;

    public const MAX_KB = 5120;

    public function authorize(): bool
    {
        return $this->user()?->can('confirmAsOhp', $this->route('delivery_note')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeBase64Uploads(['sj_document' => self::MAX_KB]);
    }

    public function rules(): array
    {
        return [
            'sj_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.self::MAX_KB],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        $maxMb = self::MAX_KB / 1024;

        return [
            'sj_document.required' => 'Unggah foto/dokumen SJ berstempel terlebih dahulu.',
            'sj_document.file' => "File gagal diunggah — kemungkinan ukurannya melebihi batas server. Maksimal {$maxMb} MB.",
            'sj_document.uploaded' => "File gagal diunggah — kemungkinan ukurannya melebihi batas server. Maksimal {$maxMb} MB.",
            'sj_document.mimes' => 'File harus berformat JPG, PNG, atau PDF.',
            'sj_document.max' => "Ukuran file maksimal {$maxMb} MB.",
        ];
    }
}
