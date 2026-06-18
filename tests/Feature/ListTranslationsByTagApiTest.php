<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTranslationsByTagApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_lists_translations_carrying_the_tag_paginated(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        Translation::factory()->count(3)->create(['tags' => ['web']]);
        Translation::factory()->count(2)->create(['tags' => ['mobile']]);
        Translation::factory()->create(['tags' => ['web', 'desktop']]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/tag/web')
            ->assertOk()
            ->assertJsonPath('meta.total', 4)
            ->assertJsonStructure([
                'data' => [['id', 'key', 'tags', 'locales']],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_tag_results_are_paginated_and_clamped(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        Translation::factory()->count(20)->create(['tags' => ['web']]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/tag/web?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_unknown_tag_returns_404(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/tag/tablet')
            ->assertNotFound();
    }

    public function test_tag_list_requires_authentication(): void
    {
        $this->getJson('/api/translations/tag/web')->assertUnauthorized();
    }
}
