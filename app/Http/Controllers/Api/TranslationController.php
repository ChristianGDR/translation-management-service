<?php

namespace App\Http\Controllers\Api;

use App\Actions\Translations\CreateTranslationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Resources\TranslationResource;
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
}
