<?php

namespace App\Jobs;

use App\Actions\Translations\UpdateTranslationAction;
use App\Models\Translation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{key?: string, description?: string|null, tags?: array<int, string>, locales?: array<string, string>}  $data
     */
    public function __construct(public int $id, public array $data) {}

    public function handle(Translation $translation, UpdateTranslationAction $action): void
    {
        $model = $translation->newQuery()->findOrFail($this->id);

        $action->handle($model, $this->data);
    }
}
