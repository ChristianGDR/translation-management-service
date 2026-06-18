<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTranslationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_lists_translations_paginated(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        Translation::factory()->count(20)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations?per_page=15');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure([
                'data' => [['id', 'key', 'tags', 'locales']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_per_page_is_clamped_to_max_100(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/translations')->assertUnauthorized();
    }

    public function test_list_cache_is_invalidated_on_create(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        Translation::factory()->count(3)->create();

        // First call populates the cache.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        // Create endpoint flushes the "translations" cache tag.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', [
                'key' => 'fresh.key',
                'locales' => ['en' => 'Fresh'],
            ])->assertCreated();

        // List reflects the new record, not a stale cached page.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations')
            ->assertOk()
            ->assertJsonPath('meta.total', 4);
    }
}
