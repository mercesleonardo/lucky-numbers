<?php

use App\Http\Controllers\Api\{ContestController, GameGeneratorController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas públicas para concursos
Route::prefix('contests')->middleware('throttle:60,1')->group(function () {
    Route::get('/latest', [ContestController::class, 'latestAll']);
    Route::get('/latest/{gameSlug}', [ContestController::class, 'latest']);
    Route::get('/exists/{gameSlug}/{drawNumber}', [ContestController::class, 'exists']);
    Route::post('/check-numbers/{gameSlug}', [ContestController::class, 'checkNumbers'])
        ->middleware('throttle:30,1'); // Limite mais restritivo para verificação de números
});

// Rotas públicas para geração de jogos
Route::prefix('games')->group(function () {
    Route::get('/info', [GameGeneratorController::class, 'info'])
        ->middleware('throttle:120,1'); // Mais permissivo para informações
    Route::get('/session-stats', [GameGeneratorController::class, 'sessionStats'])
        ->middleware('throttle:60,1');
    Route::post('/generate/{gameSlug}', [GameGeneratorController::class, 'generate'])
        ->middleware('throttle:10,1'); // Mais restritivo para geração
});
