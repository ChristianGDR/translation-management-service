<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTranslationApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_returns_single_translation_by_id(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $translation = Translation::factory()->create(['key' => 'auth.login.title']);
        $translation->locales()->create(['locale' => 'en', 'content' => 'Sign in']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/translations/id/{$translation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $translation->id)
            ->assertJsonPath('data.key', 'auth.login.title')
            ->assertJsonPath('data.locales.en', 'Sign in');
    }

    public function test_returns_404_for_missing_id(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/translations/id/999999')
            ->assertNotFound();
    }

    public function test_show_requires_authentication(): void
    {
        $translation = Translation::factory()->create();

        $this->getJson("/api/translations/id/{$translation->id}")
            ->assertUnauthorized();
    }
}
