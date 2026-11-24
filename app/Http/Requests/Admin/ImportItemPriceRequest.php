<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\AcceptsBase64Uploads;
use Illuminate\Foundation\Http\FormRequest;

class ImportItemPriceRequest extends FormRequest
{
    use AcceptsBase64Uploads;

    public const FILE_MAX_KB = 10240;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeBase64Uploads(['file' => self::FILE_MAX_KB]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xls,xlsx,csv,xml,txt', 'max:'.self::FILE_MAX_KB],
        ];
    }

    public function messages(): array
    {
        $maxMb = self::FILE_MAX_KB / 1024;

        return [
            'file.required' => 'Pilih file Excel/CSV terlebih dahulu.',
            'file.file' => "File gagal diunggah. Maksimal {$maxMb} MB.",
            'file.uploaded' => "File gagal diunggah. Maksimal {$maxMb} MB.",
            'file.mimes' => 'File harus berformat XLS, XLSX, CSV, atau XML.',
            'file.max' => "Ukuran file maksimal {$maxMb} MB.",
        ];
    }
}
