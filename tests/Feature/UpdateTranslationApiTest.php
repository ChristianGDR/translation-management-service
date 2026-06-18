<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTranslationApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    private function makeTranslation(): Translation
    {
        $translation = Translation::factory()->create([
            'key' => 'auth.login.title',
            'tags' => ['mobile'],
        ]);
        $translation->locales()->create(['locale' => 'en', 'content' => 'Sign in']);

        return $translation;
    }

    public function test_update_translation_with_api_token(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $translation = $this->makeTranslation();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'auth.login.heading',
                'tags' => ['web', 'desktop'],
                'locales' => ['en' => 'Log in', 'fr' => 'Connexion'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.key', 'auth.login.heading')
            ->assertJsonPath('data.tags', ['web', 'desktop'])
            ->assertJsonPath('data.locales.en', 'Log in')
            ->assertJsonPath('data.locales.fr', 'Connexion');

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'key' => 'auth.login.heading',
        ]);
        // Existing locale overwritten, not duplicated.
        $this->assertDatabaseHas('translation_locales', [
            'translation_id' => $translation->id,
            'locale' => 'en',
            'content' => 'Log in',
        ]);
        $this->assertSame(2, $translation->locales()->count());
    }

    public function test_update_allows_partial_payload(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $translation = $this->makeTranslation();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/translations/{$translation->id}", [
                'description' => 'Updated only',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated only')
            ->assertJsonPath('data.key', 'auth.login.title');
    }

    public function test_update_requires_authentication(): void
    {
        $translation = $this->makeTranslation();

        $this->putJson("/api/translations/{$translation->id}", ['key' => 'x'])
            ->assertUnauthorized();
    }

    public function test_update_rejects_duplicate_key(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $other = Translation::factory()->create(['key' => 'taken.key']);
        $translation = $this->makeTranslation();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", ['key' => 'taken.key'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('key');
    }

    public function test_update_allows_keeping_same_key(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $translation = $this->makeTranslation();

        // Same key on self must not trip the unique rule.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/translations/{$translation->id}", [
                'key' => 'auth.login.title',
                'description' => 'Same key, new desc',
            ])
            ->assertOk();
    }

    public function test_update_returns_404_for_missing_translation(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/translations/999999', ['key' => 'x'])
            ->assertNotFound();
    }
}
