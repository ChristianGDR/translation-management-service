<?php

namespace Tests\Feature;

use App\Jobs\CreateTranslationJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BatchCreateTranslationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_batch_create_queues_a_job_per_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations/batch', [
                'translations' => [
                    ['key' => 'a.one', 'tags' => ['web'], 'locales' => ['en' => 'One']],
                    ['key' => 'a.two', 'locales' => ['en' => 'Two', 'fr' => 'Deux']],
                ],
            ]);

        $response->assertAccepted()
            ->assertJsonPath('queued', 2);

        Queue::assertPushed(CreateTranslationJob::class, 2);
    }

    public function test_batch_create_persists_when_jobs_run(): void
    {
        // No Queue::fake -> sync queue runs the jobs inline.
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations/batch', [
                'translations' => [
                    ['key' => 'b.one', 'locales' => ['en' => 'One']],
                    ['key' => 'b.two', 'locales' => ['en' => 'Two']],
                ],
            ])->assertAccepted();

        $this->assertDatabaseCount('translations', 2);
        $this->assertDatabaseHas('translations', ['key' => 'b.one']);
        $this->assertDatabaseHas('translation_locales', ['locale' => 'en', 'content' => 'Two']);
    }

    public function test_batch_create_requires_authentication(): void
    {
        $this->postJson('/api/translations/batch', [
            'translations' => [['key' => 'x', 'locales' => ['en' => 'x']]],
        ])->assertUnauthorized();
    }

    public function test_batch_create_validates_each_item(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations/batch', [
                'translations' => [
                    ['locales' => ['en' => 'missing key']],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('translations.0.key');
    }

    public function test_batch_create_rejects_duplicate_keys_in_batch(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations/batch', [
                'translations' => [
                    ['key' => 'dup.key', 'locales' => ['en' => 'a']],
                    ['key' => 'dup.key', 'locales' => ['en' => 'b']],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('translations.1.key');
    }
}
