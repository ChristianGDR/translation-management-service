<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\BatchUpdateTranslationsAction;
use App\Jobs\UpdateTranslationJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BatchUpdateTranslationsActionTest extends TestCase
{
    public function test_handle_dispatches_one_job_per_item_with_id_stripped(): void
    {
        Queue::fake();

        $items = [
            ['id' => 5, 'key' => 'first', 'locales' => ['en' => 'First']],
            ['id' => 9, 'description' => 'Second'],
        ];

        $queued = (new BatchUpdateTranslationsAction())->handle($items);

        $this->assertSame(2, $queued);
        Queue::assertPushed(UpdateTranslationJob::class, 2);

        Queue::assertPushed(fn (UpdateTranslationJob $job) => $job->id === 5
            && $job->data === ['key' => 'first', 'locales' => ['en' => 'First']]);

        Queue::assertPushed(fn (UpdateTranslationJob $job) => $job->id === 9
            && $job->data === ['description' => 'Second']);
    }

    public function test_handle_dispatches_nothing_for_empty_batch(): void
    {
        Queue::fake();

        $queued = (new BatchUpdateTranslationsAction())->handle([]);

        $this->assertSame(0, $queued);
        Queue::assertNothingPushed();
    }
}
