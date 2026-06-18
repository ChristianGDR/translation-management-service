<?php

namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\ListTranslationsAction;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ListTranslationsActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_caches_under_per_page_and_page_key_and_paginates(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        // Eager-load + newest-first + paginate with the given page.
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('with')->once()->with('locales')->andReturnSelf();
        $builder->shouldReceive('latest')->once()->with('id')->andReturnSelf();
        $builder->shouldReceive('paginate')->once()->with(15, ['*'], 'page', 2)->andReturn($paginator);

        $translation = Mockery::mock(Translation::class);
        $translation->shouldReceive('newQuery')->once()->andReturn($builder);

        // Cache key includes per_page + page; closure executed to build the result.
        Cache::shouldReceive('tags')->once()->with('translations')->andReturnSelf();
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(fn ($key, $ttl, $callback) => $key === 'translations:list:15:2' && is_callable($callback))
            ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

        $result = (new ListTranslationsAction($translation))->handle(perPage: 15, page: 2);

        $this->assertSame($paginator, $result);
    }
}
