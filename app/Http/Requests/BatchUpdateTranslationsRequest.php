<?php

namespace App\Http\Requests;

use App\Enums\TranslationContext;
use App\Models\Translation;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchUpdateTranslationsRequest extends FormRequest
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
            'translations.*.id' => ['required', 'integer', 'distinct', 'exists:translations,id'],
            'translations.*.key' => ['sometimes', 'required', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'translations.*.tags' => ['sometimes', 'nullable', 'array'],
            'translations.*.tags.*' => [Rule::enum(TranslationContext::class)],
            'translations.*.locales' => ['sometimes', 'array', 'min:1'],
            'translations.*.locales.*' => ['required', 'string'],
        ];
    }

    /**
     * Key must stay unique, ignoring the row it belongs to.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('translations', []) as $i => $item) {
                if (! isset($item['key'], $item['id'])) {
                    continue;
                }

                $taken = Translation::query()
                    ->where('key', $item['key'])
                    ->where('id', '!=', $item['id'])
                    ->exists();

                if ($taken) {
                    $validator->errors()->add("translations.{$i}.key", 'The key has already been taken.');
                }
            }
        });
    }
}
