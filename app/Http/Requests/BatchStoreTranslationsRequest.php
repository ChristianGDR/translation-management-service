<?php

namespace App\Http\Requests;

use App\Enums\TranslationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchStoreTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'translations' => ['required', 'array', 'min:1', 'max:100'],
            'translations.*.key' => ['required', 'string', 'max:255', 'distinct', 'unique:translations,key'],
            'translations.*.description' => ['nullable', 'string', 'max:255'],
            'translations.*.tags' => ['nullable', 'array'],
            'translations.*.tags.*' => [Rule::enum(TranslationContext::class)],
            'translations.*.locales' => ['required', 'array', 'min:1'],
            'translations.*.locales.*' => ['required', 'string'],
        ];
    }
}
