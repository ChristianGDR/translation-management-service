<?php

namespace App\Http\Controllers\Api;

use App\Actions\Translations\BatchCreateTranslationsAction;
use App\Actions\Translations\BatchUpdateTranslationsAction;
use App\Actions\Translations\CreateTranslationAction;
use App\Actions\Translations\UpdateTranslationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchStoreTranslationsRequest;
use App\Http\Requests\BatchUpdateTranslationsRequest;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
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
