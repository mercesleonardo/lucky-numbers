<?php

use App\Models\Contest;
use App\Models\LotteryGame;
use App\Models\Prize;
use App\Services\LotteryGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('can get available games', function () {
    $service = app(LotteryGameService::class);
    $games = $service->getAvailableGames();

    expect($games)->toBeArray()
        ->and($games)->toContain('megasena', 'lotofacil', 'quina');
});

it('throws exception for invalid game name', function () {
    $service = app(LotteryGameService::class);

    expect(fn() => $service->importGame('invalidgame'))
        ->toThrow(InvalidArgumentException::class, "Jogo 'invalidgame' não está disponível na API");
});

it('can import a single game successfully', function () {
    $service = app(LotteryGameService::class);

    // Mock da resposta da API
    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/megasena/latest' => Http::response([
            'loteria' => 'megasena',
            'concurso' => 2923,
            'data' => '04/10/2025',
            'local' => 'ESPAÇO DA SORTE em SÃO PAULO, SP',
            'dezenas' => ['18', '27', '32', '39', '55', '56'],
            'dezenasOrdemSorteio' => ['39', '56', '55', '32', '18', '27'],
            'trevos' => [],
            'timeCoracao' => null,
            'mesSorte' => null,
            'premiacoes' => [
                [
                    'descricao' => '6 acertos',
                    'faixa' => 1,
                    'ganhadores' => 0,
                    'valorPremio' => 0.0
                ],
                [
                    'descricao' => '5 acertos',
                    'faixa' => 2,
                    'ganhadores' => 29,
                    'valorPremio' => 63029.43
                ]
            ],
            'estadosPremiados' => [],
            'observacao' => '',
            'acumulou' => true,
            'proximoConcurso' => 2924,
            'dataProximoConcurso' => '07/10/2025',
            'localGanhadores' => [],
            'valorArrecadado' => 45869610,
            'valorAcumuladoConcurso_0_5' => 12673711.97,
            'valorAcumuladoConcursoEspecial' => 116378916.46,
            'valorAcumuladoProximoConcurso' => 12708997.9,
            'valorEstimadoProximoConcurso' => 20000000.0
        ], 200)
    ]);

    $result = $service->importGame('megasena');

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['lottery_game'])->toBe('Megasena')
        ->and($result['contest_number'])->toBe(2923)
        ->and($result['prizes_count'])->toBe(2);

    // Verifica se os dados foram salvos no banco
    $lotteryGame = LotteryGame::where('slug', 'megasena')->first();
    expect($lotteryGame)->not->toBeNull()
        ->and($lotteryGame->name)->toBe('Megasena');

    $contest = Contest::where('lottery_game_id', $lotteryGame->id)
        ->where('draw_number', 2923)
        ->first();
    expect($contest)->not->toBeNull()
        ->and($contest->has_accumulated)->toBeTrue()
        ->and($contest->numbers)->toBe(['18', '27', '32', '39', '55', '56']);

    $prizes = Prize::where('contest_id', $contest->id)->get();
    expect($prizes)->toHaveCount(2);
});

it('handles API failures gracefully', function () {
    $service = app(LotteryGameService::class);

    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/megasena/latest' => Http::response([], 500)
    ]);

    expect(fn() => $service->importGame('megasena'))
        ->toThrow(Exception::class, 'Falha ao buscar dados da API para megasena');
});

it('can import all games', function () {
    $service = app(LotteryGameService::class);

    // Mock para todos os jogos
    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/*/latest' => Http::response([
            'loteria' => 'megasena',
            'concurso' => 2923,
            'data' => '04/10/2025',
            'local' => 'ESPAÇO DA SORTE em SÃO PAULO, SP',
            'dezenas' => ['18', '27', '32', '39', '55', '56'],
            'premiacoes' => [
                [
                    'descricao' => '6 acertos',
                    'faixa' => 1,
                    'ganhadores' => 0,
                    'valorPremio' => 0.0
                ]
            ],
            'acumulou' => true,
            'proximoConcurso' => 2924,
            'dataProximoConcurso' => '07/10/2025',
            'valorEstimadoProximoConcurso' => 20000000.0
        ], 200)
    ]);

    $results = $service->importAllGames();

    expect($results)->toBeArray()
        ->and(count($results))->toBe(10); // Todos os jogos disponíveis

    foreach ($results as $game => $result) {
        expect($result['success'])->toBeTrue();
    }
});

it('creates lottery game with correct attributes', function () {
    $service = app(LotteryGameService::class);

    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/quina/latest' => Http::response([
            'loteria' => 'quina',
            'concurso' => 6845,
            'data' => '06/10/2025',
            'dezenas' => ['30', '45', '56', '57', '62'],
            'premiacoes' => [],
            'acumulou' => false,
            'proximoConcurso' => 6846
        ], 200)
    ]);

    $service->importGame('quina');

    $lotteryGame = LotteryGame::where('slug', 'quina')->first();

    expect($lotteryGame->name)->toBe('Quina')
        ->and($lotteryGame->slug)->toBe('quina');
});

it('updates existing contest data', function () {
    $service = app(LotteryGameService::class);

    // Primeiro, cria um contest
    $lotteryGame = LotteryGame::factory()->create(['slug' => 'megasena']);
    $existingContest = Contest::factory()->create([
        'lottery_game_id' => $lotteryGame->id,
        'draw_number' => 2923,
        'has_accumulated' => false
    ]);

    Http::fake([
        'https://loteriascaixa-api.herokuapp.com/api/megasena/latest' => Http::response([
            'loteria' => 'megasena',
            'concurso' => 2923,
            'data' => '04/10/2025',
            'dezenas' => ['18', '27', '32', '39', '55', '56'],
            'premiacoes' => [],
            'acumulou' => true,
            'proximoConcurso' => 2924
        ], 200)
    ]);

    $service->importGame('megasena');

    $updatedContest = Contest::find($existingContest->id);
    expect($updatedContest->has_accumulated)->toBeTrue();
});
