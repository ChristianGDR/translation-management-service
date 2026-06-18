<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\BatchCreateTranslationsAction;
use App\Jobs\CreateTranslationJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BatchCreateTranslationsActionTest extends TestCase
{
    public function test_handle_dispatches_one_job_per_payload(): void
    {
        Queue::fake();

        $items = [
            ['key' => 'a.one', 'locales' => ['en' => 'One']],
            ['key' => 'a.two', 'locales' => ['en' => 'Two']],
        ];

        $queued = (new BatchCreateTranslationsAction())->handle($items);

        $this->assertSame(2, $queued);
        Queue::assertPushed(CreateTranslationJob::class, 2);
        Queue::assertPushed(
            fn (CreateTranslationJob $job) => $job->payload['key'] === 'a.one'
        );
        Queue::assertPushed(
            fn (CreateTranslationJob $job) => $job->payload['key'] === 'a.two'
        );
    }

    public function test_handle_dispatches_nothing_for_empty_batch(): void
    {
        Queue::fake();

        $queued = (new BatchCreateTranslationsAction())->handle([]);

        $this->assertSame(0, $queued);
        Queue::assertNothingPushed();
    }
}
