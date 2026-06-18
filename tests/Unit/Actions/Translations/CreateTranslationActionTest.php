<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\CreateTranslationAction;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CreateTranslationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_saves_translation_with_locales_and_flushes_cache(): void
    {
        $data = [
            'key' => 'auth.login.title',
            'description' => 'Login heading',
            'tags' => ['mobile', 'web'],
            'locales' => ['en' => 'Sign in', 'fr' => 'Se connecter'],
        ];

        $created = Mockery::mock(Translation::class);
        $created->shouldReceive('save')->once();

        $localesRelation = Mockery::mock(HasMany::class);
        $localesRelation->shouldReceive('create')
            ->once()
            ->with(['locale' => 'en', 'content' => 'Sign in'])
            ->andReturn($created);
        $localesRelation->shouldReceive('create')
            ->once()
            ->with(['locale' => 'fr', 'content' => 'Se connecter'])
            ->andReturn($created);
        $created->shouldReceive('locales')->twice()->andReturn($localesRelation);
        $created->shouldReceive('load')->once()->with('locales')->andReturnSelf();

        $factory = Mockery::mock(Translation::class);
        $factory->shouldReceive('newInstance')
            ->once()
            ->with([
                'key' => 'auth.login.title',
                'description' => 'Login heading',
                'tags' => ['mobile', 'web'],
            ])
            ->andReturn($created);

        $tagged = Mockery::mock();
        $tagged->shouldReceive('flush')->once();
        Cache::shouldReceive('tags')->once()->with('translations')->andReturn($tagged);

        $result = (new CreateTranslationAction($factory))->handle($data);

        $this->assertSame($created, $result);
    }
}
