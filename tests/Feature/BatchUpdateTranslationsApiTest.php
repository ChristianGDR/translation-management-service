<?php

namespace Tests\Feature;

use App\Jobs\UpdateTranslationJob;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BatchUpdateTranslationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_batch_update_queues_a_job_per_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $one = Translation::factory()->create(['key' => 'k.one']);
        $two = Translation::factory()->create(['key' => 'k.two']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/batch', [
                'translations' => [
                    ['id' => $one->id, 'key' => 'k.one.new'],
                    ['id' => $two->id, 'description' => 'Two desc'],
                ],
            ]);

        $response->assertAccepted()->assertJsonPath('queued', 2);

        Queue::assertPushed(UpdateTranslationJob::class, 2);
    }

    public function test_batch_update_persists_when_jobs_run(): void
    {
        // No Queue::fake -> sync queue runs the jobs inline.
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $one = Translation::factory()->create(['key' => 'k.one']);
        $one->locales()->create(['locale' => 'en', 'content' => 'Old one']);
        $two = Translation::factory()->create(['key' => 'k.two']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/batch', [
                'translations' => [
                    ['id' => $one->id, 'key' => 'k.one.new', 'locales' => ['en' => 'New one']],
                    ['id' => $two->id, 'description' => 'Two desc'],
                ],
            ])->assertAccepted();

        $this->assertDatabaseHas('translations', ['id' => $one->id, 'key' => 'k.one.new']);
        $this->assertDatabaseHas('translations', ['id' => $two->id, 'description' => 'Two desc']);
        $this->assertDatabaseHas('translation_locales', [
            'translation_id' => $one->id,
            'locale' => 'en',
            'content' => 'New one',
        ]);
    }

    public function test_batch_update_requires_authentication(): void
    {
        $translation = Translation::factory()->create();

        $this->putJson('/api/translations/batch', [
            'translations' => [['id' => $translation->id, 'key' => 'x']],
        ])->assertUnauthorized();
    }

    public function test_batch_update_requires_existing_ids(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/batch', [
                'translations' => [['id' => 999999, 'key' => 'x']],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('translations.0.id');
    }

    public function test_batch_update_rejects_key_taken_by_another_row(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $taken = Translation::factory()->create(['key' => 'taken.key']);
        $target = Translation::factory()->create(['key' => 'target.key']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/batch', [
                'translations' => [['id' => $target->id, 'key' => 'taken.key']],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('translations.0.key');
    }

    public function test_batch_update_allows_keeping_own_key(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $target = Translation::factory()->create(['key' => 'keep.key']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/batch', [
                'translations' => [['id' => $target->id, 'key' => 'keep.key', 'description' => 'changed']],
            ])
            ->assertAccepted();
    }
}
