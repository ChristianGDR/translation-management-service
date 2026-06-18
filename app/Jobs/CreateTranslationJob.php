<?php

namespace App\Jobs;

use App\Actions\Translations\CreateTranslationAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{key: string, description?: string|null, tags?: array<int, string>, locales?: array<string, string>}  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(CreateTranslationAction $action): void
    {
        $action->handle($this->payload);
    }
}
