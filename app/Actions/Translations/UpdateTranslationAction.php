<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class UpdateTranslationAction
{
    /**
     * Update a translation key and upsert its per-locale values.
     *
     * @param  array{key?: string, description?: string|null, tags?: array<int, string>, locales?: array<string, string>}  $data
     */
    public function handle(Translation $translation, array $data): Translation
    {
        $attributes = [];

        foreach (['key', 'description', 'tags'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        $translation->fill($attributes)->save();

        foreach ($data['locales'] ?? [] as $locale => $content) {
            $translation->locales()->updateOrCreate(
                ['locale' => $locale],
                ['content' => $content],
            );
        }

        Cache::tags('translations')->flush();

        return $translation->load('locales');
    }
}
