<?php

namespace App\Actions\Translations;

use App\Jobs\CreateTranslationJob;

class BatchCreateTranslationsAction
{
    /**
     * Queue a create job for each translation payload.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return int  number of jobs queued
     */
    public function handle(array $items): int
    {
        foreach ($items as $payload) {
            CreateTranslationJob::dispatch($payload);
        }

        return count($items);
    }
}
