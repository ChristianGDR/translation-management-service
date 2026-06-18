<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class CreateTranslationAction
{
    public function __construct(private Translation $translation) {}

    /**
     * Create a translation key with its per-locale values.
     *
     * @param  array{key: string, description?: string|null, tags?: array<int, string>, locales?: array<string, string>}  $data
     */
    public function handle(array $data): Translation
    {
        $translation = $this->translation->newInstance([
            'key' => $data['key'],
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);

        $translation->save();

        foreach ($data['locales'] ?? [] as $locale => $content) {
            $translation->locales()->create([
                'locale' => $locale,
                'content' => $content,
            ]);
        }

        $translation->rebuildSearchBlob();

        Cache::tags('translations')->flush();

        return $translation->load('locales');
    }
}
