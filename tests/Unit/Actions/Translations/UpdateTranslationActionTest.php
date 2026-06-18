<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\UpdateTranslationAction;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class UpdateTranslationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_updates_attributes_upserts_locales_and_flushes_cache(): void
    {
        $data = [
            'key' => 'auth.login.title',
            'description' => 'New heading',
            'tags' => ['web'],
            'locales' => ['en' => 'Log in', 'fr' => 'Connexion'],
        ];

        $translation = Mockery::mock(Translation::class);

        // Only the present fields are filled, then saved.
        $translation->shouldReceive('fill')
            ->once()
            ->with([
                'key' => 'auth.login.title',
                'description' => 'New heading',
                'tags' => ['web'],
            ])
            ->andReturnSelf();
        $translation->shouldReceive('save')->once();

        // Locales upserted one row per locale.
        $localesRelation = Mockery::mock(HasMany::class);
        $localesRelation->shouldReceive('updateOrCreate')
            ->once()
            ->with(['locale' => 'en'], ['content' => 'Log in']);
        $localesRelation->shouldReceive('updateOrCreate')
            ->once()
            ->with(['locale' => 'fr'], ['content' => 'Connexion']);
        $translation->shouldReceive('locales')->twice()->andReturn($localesRelation);

        $translation->shouldReceive('rebuildSearchBlob')->once();
        $translation->shouldReceive('load')->once()->with('locales')->andReturnSelf();

        $tagged = Mockery::mock();
        $tagged->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')->once()->with('translations')->andReturn($tagged);

        $result = (new UpdateTranslationAction)->handle($translation, $data);

        $this->assertSame($translation, $result);
    }

    public function test_handle_only_fills_provided_fields(): void
    {
        // Only description provided -> key/tags untouched, no locales loop.
        $translation = Mockery::mock(Translation::class);
        $translation->shouldReceive('fill')
            ->once()
            ->with(['description' => 'Just this'])
            ->andReturnSelf();
        $translation->shouldReceive('save')->once();
        $translation->shouldReceive('locales')->never();
        $translation->shouldReceive('rebuildSearchBlob')->once();
        $translation->shouldReceive('load')->once()->with('locales')->andReturnSelf();

        $tagged = Mockery::mock();
        $tagged->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')->once()->with('translations')->andReturn($tagged);

        $result = (new UpdateTranslationAction)->handle($translation, ['description' => 'Just this']);

        $this->assertSame($translation, $result);
    }
}
