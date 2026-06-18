<?php

use App\Http\Controllers\Api\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/translations', [TranslationController::class, 'index']);
    Route::get('/translations/id/{id}', [TranslationController::class, 'showById'])->whereNumber('id');
    Route::get('/translations/tag/{tag}', [TranslationController::class, 'indexByTag']);
    Route::post('/translations', [TranslationController::class, 'store']);

    Route::post('/translations/batch', [TranslationController::class, 'batchStore']);
    Route::match(['put', 'patch'], '/translations/batch', [TranslationController::class, 'batchUpdate']);

    Route::match(['put', 'patch'], '/translations/{translation}', [TranslationController::class, 'update']);
});
