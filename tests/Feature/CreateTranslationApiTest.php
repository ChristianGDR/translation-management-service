<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTranslationApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_create_translation_with_api_token(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $payload = [
            'key' => 'auth.login.title',
            'description' => 'Login heading',
            'tags' => ['mobile', 'web'],
            'locales' => ['en' => 'Sign in', 'fr' => 'Se connecter'],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'auth.login.title')
            ->assertJsonPath('data.tags', ['mobile', 'web'])
            ->assertJsonPath('data.locales.en', 'Sign in')
            ->assertJsonPath('data.locales.fr', 'Se connecter');

        $this->assertDatabaseHas('translations', ['key' => 'auth.login.title']);
        $this->assertDatabaseHas('translation_locales', [
            'locale' => 'en',
            'content' => 'Sign in',
        ]);
    }

    public function test_endpoint_returns_json_even_without_accept_header(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        // Plain post() sends no Accept: application/json header.
        $response = $this->post('/api/translations',
            ['locales' => ['en' => 'x']],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors('key');
    }

    public function test_create_translation_requires_authentication(): void
    {
        $response = $this->postJson('/api/translations', [
            'key' => 'no.token',
            'locales' => ['en' => 'x'],
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_translation_validates_required_key(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', ['locales' => ['en' => 'x']]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('key');
    }

    public function test_create_translation_rejects_invalid_tag(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/translations', [
                'key' => 'bad.tag',
                'tags' => ['tablet'],
                'locales' => ['en' => 'x'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('tags.0');
    }
}
