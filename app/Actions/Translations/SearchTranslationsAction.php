<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchTranslationsAction
{
    public function __construct(private Translation $translation) {}

    /**
     * Paginated, cached string search across the denormalized search_blob
     * (key + tags + locale contents).
     */
    public function handle(string $term, int $perPage, int $page): LengthAwarePaginator
    {
        $needle = Str::lower(trim($term));
        $key = 'translations:search:'.md5($needle).":{$perPage}:{$page}";

        $cached = Cache::tags('translations')->remember(
            $key,
            now()->addMinutes(10),
            function () use ($needle, $perPage, $page) {
                $paginator = $this->translation->newQuery()
                    ->where('search_blob', 'like', "%{$needle}%")
                    ->latest('id')
                    ->paginate($perPage, page: $page, columns: ['id']);

                return [
                    'ids' => $paginator->pluck('id')->all(),
                    'total' => $paginator->total(),
                ];
            },
        );

        $items = $this->translation->newQuery()
            ->with('locales')
            ->whereIn('id', $cached['ids'])
            ->orderByDesc('id')
            ->get()
            ->all();

        return new Paginator(
            $items,
            $cached['total'],
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }
}
