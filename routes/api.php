<?php

use App\Http\Controllers\Api\{ContestController, GameGeneratorController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas públicas para concursos
Route::prefix('contests')->group(function () {
    Route::get('/latest', [ContestController::class, 'latestAll']);
    Route::get('/latest/{gameSlug}', [ContestController::class, 'latest']);
});

// Rotas públicas para geração de jogos
Route::prefix('games')->group(function () {
    Route::get('/info', [GameGeneratorController::class, 'info']);
    Route::get('/session-stats', [GameGeneratorController::class, 'sessionStats']);
    Route::post('/generate/{gameSlug}', [GameGeneratorController::class, 'generate']);
});
