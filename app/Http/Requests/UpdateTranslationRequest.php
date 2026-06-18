<?php

namespace App\Http\Requests;

use App\Enums\TranslationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTranslationRequest extends FormRequest
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
            'key' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('translations', 'key')->ignore($this->route('translation')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => [Rule::enum(TranslationContext::class)],
            'locales' => ['sometimes', 'array', 'min:1'],
            'locales.*' => ['required', 'string'],
        ];
    }
}
