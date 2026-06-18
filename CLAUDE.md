# Project Rules

Laravel 13 / PHP 8.3 project.

## Rule: Creating a new API endpoint

Always follow this pattern when adding an API endpoint.

### 1. Action Pattern (mandatory)

Every API call gets its own **Action class** under `app/Actions/`. One action = one
operation. Controllers stay thin: they only resolve input, call the action, return the
response. Never put business logic in a controller.

- One action class per controller method (one per API call).
- Inject the action into the controller method as a parameter (method injection) so
  Laravel's container resolves it. Do not `new` it.
- Action exposes a single public `handle(...)` method.

```php
// app/Actions/Translations/ListTranslationsAction.php
namespace App\Actions\Translations;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTranslationsAction
{
    public function handle(int $perPage = 15): LengthAwarePaginator
    {
        return Translation::query()
            ->latest('id')
            ->paginate($perPage);
    }
}
```

```php
// app/Http/Controllers/Api/TranslationController.php
namespace App\Http\Controllers\Api;

use App\Actions\Translations\ListTranslationsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request, ListTranslationsAction $action)
    {
        $perPage = (int) $request->integer('per_page', 15);

        return TranslationResource::collection(
            $action->handle($perPage)
        );
    }
}
```

### 2. Pagination (mandatory for collections)

Any endpoint returning multiple records must paginate. Use `paginate()` (or
`cursorPaginate()` for large/infinite scroll). Never return an unbounded `get()`/`all()`.

- Accept `per_page` from the request, with a sane default (15) and a hard max (e.g. 100).
- Return via an API `Resource` collection so pagination meta is included.

### 3. Cache whenever possible

Cache read/GET responses that are not user-specific or that change infrequently.

- Use `Cache::remember($key, $ttl, fn () => ...)` inside the Action.
- Key must include all query params that affect the result (page, per_page, filters).
- Invalidate the cache on writes (create/update/delete) — flush by key or tag.
- Prefer cache tags when the store supports them (redis): `Cache::tags('translations')`.

```php
public function handle(int $perPage, int $page): LengthAwarePaginator
{
    $key = "translations:list:{$perPage}:{$page}";

    return Cache::tags('translations')->remember($key, now()->addMinutes(10),
        fn () => Translation::query()->latest('id')->paginate($perPage, page: $page)
    );
}
```

### 4. Unit tests (mandatory)

Every Action class gets a unit test. Every endpoint gets a feature test.

- **Action = unit test** under `tests/Unit/Actions/`. Test `handle()` in isolation: mock
  collaborators, assert return value/side effects. No HTTP layer.
- **Endpoint = feature test** under `tests/Feature/`. Hit the route, assert status,
  JSON shape, and pagination meta.
- Use factories for model data, never hand-built arrays.
- Cover: happy path, validation failure, pagination (per_page + max clamp), and cache
  hit/invalidation when the action caches.
- Run with `php artisan test` (or `composer test`); all green before done.

```php
// tests/Unit/Actions/Translations/ListTranslationsActionTest.php
namespace Tests\Unit\Actions\Translations;

use App\Actions\Translations\ListTranslationsAction;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTranslationsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_paginated_translations(): void
    {
        Translation::factory()->count(20)->create();

        $result = (new ListTranslationsAction())->handle(perPage: 15);

        $this->assertCount(15, $result->items());
        $this->assertSame(20, $result->total());
    }
}
```

### Checklist for every new endpoint

- [ ] Action class created under `app/Actions/<Domain>/` with a single `handle()`.
- [ ] Action injected into the controller method as a parameter.
- [ ] Controller holds no business logic.
- [ ] Collection responses are paginated with a `per_page` default + max.
- [ ] FormRequest used for validation when input is accepted.
- [ ] API Resource used for the response shape.
- [ ] Cache applied to reads; invalidated on writes.
- [ ] Route registered in `routes/api.php`.
- [ ] Unit test for the Action (`tests/Unit/Actions/`).
- [ ] Feature test covering the endpoint (`tests/Feature/`).
- [ ] `php artisan test` green.
