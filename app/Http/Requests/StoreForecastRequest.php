<?php

namespace App\Http\Requests;

use App\Enums\CompanyType;
use App\Http\Requests\Concerns\AcceptsBase64Uploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'file' => ['nullable', 'file', 'mimes:pdf,xlsx,xls,csv,jpg,jpeg,png', 'max:'.self::FILE_MAX_KB],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'gt:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);
            $hasItems = collect(is_array($items) ? $items : [])
                ->contains(fn ($item) => filled($item['item_id'] ?? null) && (int) ($item['qty'] ?? 0) > 0);
            $hasFile = $this->hasFile('file');
            $existing = $this->route('forecast');

            if (! $hasItems && ! $hasFile && ! $existing?->file_path) {
                $validator->errors()->add(
                    'items',
                    'Lengkapi minimal satu item forecast atau unggah file forecast.'
                );
            }
        });
    }
}
