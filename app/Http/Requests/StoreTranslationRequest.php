<?php

namespace App\Http\Requests;

use App\Enums\TranslationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255', 'unique:translations,key'],
            'description' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::enum(TranslationContext::class)],
            'locales' => ['required', 'array', 'min:1'],
            'locales.*' => ['required', 'string'],
        ];
    }
}
