<?php

namespace App\Http\Requests;

use App\Enums\CompanyType;
use App\Http\Requests\Concerns\AcceptsBase64Uploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreForecastRequest extends FormRequest
{
    use AcceptsBase64Uploads;

    public const FILE_MAX_KB = 10240;

    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Forecast::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->decodeBase64Uploads(['file' => self::FILE_MAX_KB]);
    }

    public function rules(): array
    {
        return [
            'supplier_rm_id' => [
                'required',
                Rule::exists('companies', 'id')->where('type', CompanyType::RawMat->value),
            ],
            'period_month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => [
                $this->route('forecast')?->file_path ? 'nullable' : 'required',
                'file',
                'mimes:pdf,xlsx,xls,csv',
                'max:'.self::FILE_MAX_KB,
            ],
        ];
    }
}
