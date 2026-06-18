<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class ShowTranslationAction
{
    public function __construct(private Translation $translation) {}

    /**
     * Cached single translation with its locales.
     */
    public function handle(int $id): Translation
    {
        return Cache::tags('translations')->remember(
            "translations:show:{$id}",
            now()->addMinutes(10),
            fn () => $this->translation->newQuery()->with('locales')->findOrFail($id),
        );
    }
}
