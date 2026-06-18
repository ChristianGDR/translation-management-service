<?php

namespace App\Actions\Translations;

use App\Jobs\UpdateTranslationJob;
use Illuminate\Support\Arr;

class BatchUpdateTranslationsAction
{
    /**
     * Queue an update job for each translation payload (id inside each item).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return int  number of jobs queued
     */
    public function handle(array $items): int
    {
        foreach ($items as $item) {
            UpdateTranslationJob::dispatch($item['id'], Arr::except($item, ['id']));
        }

        return count($items);
    }
}
