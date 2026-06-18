<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListTranslationsAction
{
    public function __construct(private Translation $translation) {}

    /**
     * Paginated, cached list of translations with their locales.
     */
    public function handle(int $perPage, int $page): LengthAwarePaginator
    {
        $key = "translations:list:{$perPage}:{$page}";

        return Cache::tags('translations')->remember(
            $key,
            now()->addMinutes(10),
            fn () => $this->translation->newQuery()
                ->with('locales')
                ->latest('id')
                ->paginate($perPage, page: $page),
        );
    }
}
