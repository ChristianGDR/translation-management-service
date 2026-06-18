<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\ShowTranslationAction;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ShowTranslationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_caches_by_id_and_loads_locales(): void
    {
        $model = Mockery::mock(Translation::class);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->once()->with('locales')->andReturnSelf();
        $builder->shouldReceive('findOrFail')->once()->with(7)->andReturn($model);

        $translation = Mockery::mock(Translation::class);
        $translation->shouldReceive('newQuery')->once()->andReturn($builder);

        Cache::shouldReceive('tags')->once()->with('translations')->andReturnSelf();
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(fn ($key, $ttl, $cb) => $key === 'translations:show:7' && is_callable($cb))
            ->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $result = (new ShowTranslationAction($translation))->handle(7);

        $this->assertSame($model, $result);
    }
}
