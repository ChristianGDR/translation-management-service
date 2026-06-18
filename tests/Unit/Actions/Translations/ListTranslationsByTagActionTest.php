<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\ListTranslationsByTagAction;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ListTranslationsByTagActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_filters_by_tag_and_caches_by_tag_per_page_and_page(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->once()->with('locales')->andReturnSelf();
        $builder->shouldReceive('whereJsonContains')->once()->with('tags', 'web')->andReturnSelf();
        $builder->shouldReceive('latest')->once()->with('id')->andReturnSelf();
        $builder->shouldReceive('paginate')->once()->with(15, ['*'], 'page', 1)->andReturn($paginator);

        $translation = Mockery::mock(Translation::class);
        $translation->shouldReceive('newQuery')->once()->andReturn($builder);

        Cache::shouldReceive('tags')->once()->with('translations')->andReturnSelf();
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(fn ($key, $ttl, $cb) => $key === 'translations:tag:web:15:1' && is_callable($cb))
            ->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $result = (new ListTranslationsByTagAction($translation))->handle(tag: 'web', perPage: 15, page: 1);

        $this->assertSame($paginator, $result);
    }
}
