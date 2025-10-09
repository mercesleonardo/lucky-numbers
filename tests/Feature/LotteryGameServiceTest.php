<?php

use App\Models\{Contest, LotteryGame};
use App\Services\LotteryGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can get available games', function () {
    $service = app(LotteryGameService::class);
    $games   = $service->getAvailableGames();

    expect($games)->toBeArray()
        ->and($games)->toContain('megasena', 'lotofacil', 'quina')
        ->and(count($games))->toBe(3);
});

it('returns error for invalid game name', function () {
    $service = app(LotteryGameService::class);

    $result = $service->importContest('invalidgame', 1);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeFalse()
        ->and($result['error'])->toContain("Jogo 'invalidgame' não está disponível no banco de dados");
});

it('can create lottery game and contest models', function () {
    $lotteryGame = LotteryGame::factory()->create([
        'name' => 'Test Game',
        'slug' => 'test-game',
    ]);

    $contest = Contest::factory()->create([
        'lottery_game_id' => $lotteryGame->id,
        'draw_number'     => 12345,
    ]);

    expect($lotteryGame->name)->toBe('Test Game')
        ->and($lotteryGame->slug)->toBe('test-game')
        ->and($contest->lottery_game_id)->toBe($lotteryGame->id)
        ->and($contest->draw_number)->toBe(12345);
});
