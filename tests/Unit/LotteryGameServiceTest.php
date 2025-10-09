<?php

use App\Models\{Contest, LotteryGame, Prize};
use App\Services\LotteryGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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

it('can import a specific contest successfully', function () {
    $service = app(LotteryGameService::class);

    // Mock da resposta da API
    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/megasena/1' => Http::response([
            'loteria'    => 'megasena',
            'concurso'   => 1,
            'data'       => '11/03/1996',
            'local'      => 'SÃO PAULO, SP',
            'dezenas'    => ['04', '05', '30', '33', '41', '52'],
            'premiacoes' => [
                [
                    'descricao'   => '6 acertos',
                    'ganhadores'  => 1,
                    'valorPremio' => 1700000.00,
                ],
            ],
        ]),
    ]);

    // Primeiro cria o jogo no banco
    LotteryGame::create(['name' => 'Megasena', 'slug' => 'megasena']);

    $result = $service->importContest('megasena', 1);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['lottery_game'])->toBe('Megasena')
        ->and($result['contest_number'])->toBe(1);

    // Verifica se o concurso foi criado no banco
    expect(Contest::count())->toBe(1);
    expect(Prize::count())->toBe(1);
});

it('creates lottery game with correct attributes', function () {
    $service = app(LotteryGameService::class);

    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/quina/1' => Http::response([
            'loteria'    => 'quina',
            'concurso'   => 1,
            'data'       => '13/03/1994',
            'local'      => 'SÃO PAULO, SP',
            'dezenas'    => ['05', '20', '35', '55', '75'],
            'premiacoes' => [
                [
                    'ganhadores'  => 0,
                    'valorPremio' => 0,
                ],
            ],
        ]),
    ]);

    $result = $service->importContest('quina', 1);

    expect($result['success'])->toBeTrue();

    $lotteryGame = LotteryGame::first();

    expect($lotteryGame->name)->toBe('Quina')
        ->and($lotteryGame->slug)->toBe('quina');
});

it('updates existing contest data', function () {
    $service = app(LotteryGameService::class);

    // Primeiro cria um jogo no banco
    $lotteryGame = LotteryGame::create(['name' => 'Megasena', 'slug' => 'megasena']);

    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/megasena/1' => Http::response([
            'loteria'    => 'megasena',
            'concurso'   => 1,
            'data'       => '11/03/1996',
            'local'      => 'SÃO PAULO, SP',
            'dezenas'    => ['04', '05', '30', '33', '41', '52'],
            'premiacoes' => [],
        ]),
    ]);

    // Primeira importação
    $service->importContest('megasena', 1);

    // Segunda importação (mesmo concurso)
    $service->importContest('megasena', 1);

    // Deve ter apenas um registro
    expect(Contest::count())->toBe(1);
});
