<?php

namespace App\Http\Controllers\Api;

use App\Actions\Translations\BatchCreateTranslationsAction;
use App\Actions\Translations\BatchUpdateTranslationsAction;
use App\Actions\Translations\CreateTranslationAction;
use App\Actions\Translations\ListTranslationsAction;
use App\Actions\Translations\ListTranslationsByTagAction;
use App\Actions\Translations\SearchTranslationsAction;
use App\Actions\Translations\ShowTranslationAction;
use App\Actions\Translations\UpdateTranslationAction;
use App\Enums\TranslationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchStoreTranslationsRequest;
use App\Http\Requests\BatchUpdateTranslationsRequest;
use App\Http\Requests\SearchTranslationsRequest;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function index(Request $request, ListTranslationsAction $action): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page = max($request->integer('page', 1), 1);

        return TranslationResource::collection(
            $action->handle($perPage, $page)
        );
    }

    public function search(SearchTranslationsRequest $request, SearchTranslationsAction $action): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page = max($request->integer('page', 1), 1);

        return TranslationResource::collection(
            $action->handle($request->validated()['q'], $perPage, $page)
        );
    }

    public function showById(int $id, ShowTranslationAction $action): TranslationResource
    {
        return TranslationResource::make($action->handle($id));
    }

    public function indexByTag(Request $request, string $tag, ListTranslationsByTagAction $action): AnonymousResourceCollection
    {
        abort_unless(TranslationContext::tryFrom($tag) !== null, 404);

        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $page = max($request->integer('page', 1), 1);

        return TranslationResource::collection(
            $action->handle($tag, $perPage, $page)
        );
    }

    public function store(StoreTranslationRequest $request, CreateTranslationAction $action): JsonResponse
    {
        $translation = $action->handle($request->validated());

        return TranslationResource::make($translation)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateTranslationRequest $request,
        Translation $translation,
        UpdateTranslationAction $action,
    ): JsonResponse {
        $translation = $action->handle($translation, $request->validated());

        return TranslationResource::make($translation)
            ->response()
            ->setStatusCode(200);
    }

    public function batchStore(BatchStoreTranslationsRequest $request, BatchCreateTranslationsAction $action): JsonResponse
    {
        $queued = $action->handle($request->validated()['translations']);

        return response()->json([
            'message' => "Queued {$queued} translation(s) for creation.",
            'queued' => $queued,
        ], 202);
    }

    public function batchUpdate(BatchUpdateTranslationsRequest $request, BatchUpdateTranslationsAction $action): JsonResponse
    {
        $queued = $action->handle($request->validated()['translations']);

        return response()->json([
            'message' => "Queued {$queued} translation(s) for update.",
            'queued' => $queued,
        ], 202);
    }
}
