<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListTranslationsByTagAction
{
    public function __construct(private Translation $translation) {}

    /**
     * Paginated, cached list of translations carrying the given tag.
     */
    public function handle(string $tag, int $perPage, int $page): LengthAwarePaginator
    {
        $key = "translations:tag:{$tag}:{$perPage}:{$page}";

        return Cache::tags('translations')->remember(
            $key,
            now()->addMinutes(10),
            fn () => $this->translation->newQuery()
                ->with('locales')
                ->whereJsonContains('tags', $tag)
                ->latest('id')
                ->paginate($perPage, page: $page),
        );
    }
}
