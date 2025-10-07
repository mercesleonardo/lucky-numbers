<?php

namespace App\Services;

use App\Models\{Contest, LotteryGame, Prize};
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\{Http, Log};
use Illuminate\Support\Str;

class LotteryGameService
{
    private PendingRequest $client;

    private array $availableGames = [
        'maismilionaria',
        'megasena',
        'lotofacil',
        'quina',
        'lotomania',
        'timemania',
        'duplasena',
        'federal',
        'diadesorte',
        'supersete',
    ];

    public function __construct()
    {
        $this->client = Http::baseUrl('https://loteriascaixa-api.herokuapp.com/api')
            ->timeout(30)
            ->retry(3, 100);
    }

    /**
     * Importa todos os jogos disponíveis da API
     */
    public function importAllGames(): array
    {
        $results = [];

        foreach ($this->availableGames as $game) {
            try {
                $result         = $this->importGame($game);
                $results[$game] = $result;
            } catch (\Exception $e) {
                Log::error("Erro ao importar jogo {$game}: " . $e->getMessage());
                $results[$game] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Importa um jogo específico da API (apenas o último resultado)
     */
    public function importGame(string $gameName): array
    {
        if (!in_array($gameName, $this->availableGames)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível na API"];
        }

        try {
            // Busca o último resultado do jogo
            $response = $this->client->get("/{$gameName}/latest");

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Falha ao buscar dados da API para {$gameName}"];
            }

            $data = $response->json();

            // Cria ou atualiza o jogo
            $lotteryGame = $this->createOrUpdateLotteryGame($data);

            // Cria ou atualiza o concurso
            $contest = $this->createOrUpdateContest($lotteryGame, $data);

            // Cria os prêmios
            $prizes = $this->createPrizes($contest, $data['premiacoes'] ?? []);

            return [
                'success'        => true,
                'lottery_game'   => $lotteryGame->name,
                'contest_number' => $contest->draw_number,
                'prizes_count'   => count($prizes),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Importa um concurso específico da API
     */
    public function importContest(string $gameName, int $contestNumber): array
    {
        if (!in_array($gameName, $this->availableGames)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível na API"];
        }

        try {
            // Busca o concurso específico
            $response = $this->client->get("/{$gameName}/{$contestNumber}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => "Falha ao buscar concurso {$contestNumber} para {$gameName}"];
            }

            $data = $response->json();

            // Verifica se os dados são válidos
            if (!$data || !isset($data['loteria']) || !isset($data['concurso'])) {
                return ['success' => false, 'error' => "Dados inválidos retornados para concurso {$contestNumber}"];
            }

            // Verifica se o concurso já existe no banco
            $existingContest = Contest::whereHas('lotteryGame', function ($query) use ($gameName) {
                $query->where('slug', Str::slug($gameName));
            })->where('draw_number', $contestNumber)->first();

            if ($existingContest) {
                return [
                    'success' => true,
                    'lottery_game' => ucfirst($gameName),
                    'contest_number' => $contestNumber,
                    'prizes_count' => $existingContest->prizes()->count(),
                    'message' => 'Concurso já existia no banco'
                ];
            }

            // Cria ou atualiza o jogo
            $lotteryGame = $this->createOrUpdateLotteryGame($data);

            // Cria ou atualiza o concurso
            $contest = $this->createOrUpdateContest($lotteryGame, $data);

            // Cria os prêmios
            $prizes = $this->createPrizes($contest, $data['premiacoes'] ?? []);

            return [
                'success'        => true,
                'lottery_game'   => $lotteryGame->name,
                'contest_number' => $contest->draw_number,
                'prizes_count'   => count($prizes),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Importa todos os concursos históricos de um jogo específico
     */
    public function importAllContests(string $gameName, ?\Closure $progressCallback = null, ?int $fromContest = null, ?int $toContest = null): array
    {
        if (!in_array($gameName, $this->availableGames)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível na API"];
        }

        try {
            // Primeiro, busca o último concurso para saber quantos existem
            $latestResponse = $this->client->get("/{$gameName}/latest");

            if (!$latestResponse->successful()) {
                return ['success' => false, 'error' => "Falha ao buscar último concurso de {$gameName}"];
            }

            $latestData = $latestResponse->json();
            $lastContestNumber = $latestData['concurso'];

            // Define o range de concursos
            $startContest = $fromContest ?? 1;
            $endContest = $toContest ?? $lastContestNumber;

            // Valida o range
            if ($startContest < 1) $startContest = 1;
            if ($endContest > $lastContestNumber) $endContest = $lastContestNumber;
            if ($startContest > $endContest) {
                return ['success' => false, 'error' => "Concurso inicial ({$startContest}) não pode ser maior que o final ({$endContest})"];
            }

            $totalToImport = $endContest - $startContest + 1;

            $results = [
                'success' => true,
                'lottery_game' => ucfirst($gameName),
                'total_contests' => $lastContestNumber,
                'range_start' => $startContest,
                'range_end' => $endContest,
                'range_total' => $totalToImport,
                'imported' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            // Importa todos os concursos no range especificado
            for ($contestNumber = $startContest; $contestNumber <= $endContest; $contestNumber++) {
                try {
                    $result = $this->importContest($gameName, $contestNumber);

                    if ($result['success']) {
                        $results['imported']++;

                        // Se já existia, conta como 'skipped'
                        if (isset($result['message']) && str_contains($result['message'], 'já existia')) {
                            $results['skipped']++;
                        }
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Concurso {$contestNumber}: " . $result['error'];
                    }

                    // Chama callback de progresso se fornecido
                    if ($progressCallback) {
                        $progressCallback($contestNumber, $endContest, $result);
                    }

                    // Pequena pausa para não sobrecarregar a API
                    usleep(100000); // 0.1 segundos

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Concurso {$contestNumber}: " . $e->getMessage();

                    if ($progressCallback) {
                        $progressCallback($contestNumber, $endContest, ['success' => false, 'error' => $e->getMessage()]);
                    }
                }
            }

            return $results;

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Importa todos os concursos históricos de todos os jogos disponíveis
     */
    public function importAllGamesAllContests(?\Closure $progressCallback = null): array
    {
        $results = [];

        foreach ($this->availableGames as $game) {
            try {
                $result = $this->importAllContests($game, $progressCallback);
                $results[$game] = $result;
            } catch (\Exception $e) {
                Log::error("Erro ao importar todos os concursos do jogo {$game}: " . $e->getMessage());
                $results[$game] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Cria ou atualiza um jogo de loteria
     */
    private function createOrUpdateLotteryGame(array $data): LotteryGame
    {
        $name = ucfirst($data['loteria']);
        $slug = Str::slug($data['loteria']);

        return LotteryGame::updateOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }

    /**
     * Cria ou atualiza um concurso
     */
    private function createOrUpdateContest(LotteryGame $lotteryGame, array $data): Contest
    {
        $drawDate     = Carbon::createFromFormat('d/m/Y', $data['data']);
        $nextDrawDate = null;

        if (isset($data['dataProximoConcurso'])) {
            try {
                $nextDrawDate = Carbon::createFromFormat('d/m/Y', $data['dataProximoConcurso']);
            } catch (\Exception $e) {
                Log::warning("Data do próximo concurso inválida: " . $data['dataProximoConcurso']);
            }
        }

        return Contest::updateOrCreate(
            [
                'lottery_game_id' => $lotteryGame->id,
                'draw_number'     => $data['concurso'],
            ],
            [
                'draw_date'                 => $drawDate,
                'location'                  => $data['local'] ?? null,
                'numbers'                   => $data['dezenas'] ?? [],
                'has_accumulated'           => $data['acumulou'] ?? false,
                'next_draw_number'          => $data['proximoConcurso'] ?? null,
                'next_draw_date'            => $nextDrawDate,
                'estimated_prize_next_draw' => $data['valorEstimadoProximoConcurso'] ?? null,
                'extra_data'                => [
                    'dezenasOrdemSorteio'            => $data['dezenasOrdemSorteio'] ?? [],
                    'trevos'                         => $data['trevos'] ?? [],
                    'timeCoracao'                    => $data['timeCoracao'] ?? null,
                    'mesSorte'                       => $data['mesSorte'] ?? null,
                    'valorArrecadado'                => $data['valorArrecadado'] ?? null,
                    'valorAcumuladoConcurso_0_5'     => $data['valorAcumuladoConcurso_0_5'] ?? null,
                    'valorAcumuladoConcursoEspecial' => $data['valorAcumuladoConcursoEspecial'] ?? null,
                    'valorAcumuladoProximoConcurso'  => $data['valorAcumuladoProximoConcurso'] ?? null,
                    'observacao'                     => $data['observacao'] ?? null,
                    'estadosPremiados'               => $data['estadosPremiados'] ?? [],
                    'localGanhadores'                => $data['localGanhadores'] ?? [],
                ],
            ]
        );
    }

    /**
     * Cria os prêmios do concurso
     */
    private function createPrizes(Contest $contest, array $premiacoes): array
    {
        // Remove prêmios existentes para evitar duplicatas
        Prize::where('contest_id', $contest->id)->delete();

        $prizes = [];

        foreach ($premiacoes as $premiacao) {
            $prize = Prize::create([
                'contest_id'   => $contest->id,
                'description'  => $premiacao['descricao'],
                'tier'         => $premiacao['faixa'],
                'winners'      => $premiacao['ganhadores'],
                'prize_amount' => $premiacao['valorPremio'],
            ]);

            $prizes[] = $prize;
        }

        return $prizes;
    }

    /**
     * Retorna a lista de jogos disponíveis
     */
    public function getAvailableGames(): array
    {
        return $this->availableGames;
    }
}
