<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTranslationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    /**
     * Create via the API so the search_blob is built by the action.
     */
    private function seedViaApi(string $token, array $payload): void
    {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', $payload)
            ->assertCreated();
    }

    public function test_search_matches_by_key_tag_and_content(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->seedViaApi($token, [
            'key' => 'auth.login.title',
            'tags' => ['web'],
            'locales' => ['en' => 'Sign in', 'fr' => 'Connexion'],
        ]);
        $this->seedViaApi($token, [
            'key' => 'dashboard.greeting',
            'tags' => ['mobile'],
            'locales' => ['en' => 'Welcome'],
        ]);

        // Match by locale content.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search?q=connexion')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.key', 'auth.login.title');

        // Match by key fragment.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search?q=dashboard')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.key', 'dashboard.greeting');

        // Match by tag.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search?q=web')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.key', 'auth.login.title');
    }

    public function test_search_response_never_exposes_search_blob(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->seedViaApi($token, [
            'key' => 'auth.login.title',
            'tags' => ['web'],
            'locales' => ['en' => 'Sign in'],
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search?q=sign')
            ->assertOk()
            ->assertJsonMissingPath('data.0.search_blob');
    }

    public function test_search_requires_query_term(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_search_is_paginated_and_clamped(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        for ($i = 0; $i < 3; $i++) {
            $this->seedViaApi($token, [
                'key' => "common.button.{$i}",
                'tags' => ['web'],
                'locales' => ['en' => 'Save now'],
            ]);
        }

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/search?q=save&per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_search_requires_authentication(): void
    {
        $this->getJson('/api/translations/search?q=sign')->assertUnauthorized();
    }
}
