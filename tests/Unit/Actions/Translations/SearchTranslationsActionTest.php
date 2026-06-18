<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\SearchTranslationsAction;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SearchTranslationsActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_searches_blob_lowercased_and_caches(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        // Term is trimmed + lowercased before the LIKE.
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->once()->with('locales')->andReturnSelf();
        $builder->shouldReceive('where')->once()->with('search_blob', 'like', '%sign in%')->andReturnSelf();
        $builder->shouldReceive('latest')->once()->with('id')->andReturnSelf();
        $builder->shouldReceive('paginate')->once()->with(15, ['*'], 'page', 1)->andReturn($paginator);

        $translation = Mockery::mock(Translation::class);
        $translation->shouldReceive('newQuery')->once()->andReturn($builder);

        $expectedKey = 'translations:search:'.md5('sign in').':15:1';
        Cache::shouldReceive('tags')->once()->with('translations')->andReturnSelf();
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(fn ($key, $ttl, $cb) => $key === $expectedKey && is_callable($cb))
            ->andReturnUsing(fn ($key, $ttl, $cb) => $cb());

        $result = (new SearchTranslationsAction($translation))->handle('  Sign In  ', perPage: 15, page: 1);

        $this->assertSame($paginator, $result);
    }
}
