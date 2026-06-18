<?php

namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
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

        $cached = Cache::tags('translations')->remember(
            $key,
            now()->addMinutes(10),
            function () use ($tag, $perPage, $page) {
                $paginator = $this->translation->newQuery()
                    ->whereJsonContains('tags', $tag)
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
