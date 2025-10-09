<?php

namespace App\Services;

use App\Jobs\ImportContestJob;
use App\Models\{Contest, LotteryGame, Prize};
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\{Cache, DB, Http};
use Illuminate\Support\Str;

class LotteryGameService
{
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::baseUrl(config('lottery.api.base_url'))
            ->timeout(config('lottery.api.timeout'))
            ->retry(config('lottery.api.retry_attempts'), config('lottery.api.retry_delay'));
    }

    /**
     * Retorna a lista de jogos disponíveis (fixos)
     */
    public function getAvailableGames(): array
    {
        return LotteryGame::pluck('slug')->toArray();
    }

    /**
     * Valida se um jogo existe no banco de dados (com cache)
     */
    private function validateGameExists(string $gameName): bool
    {
        $cacheKey = "lottery_game_exists_{$gameName}";

        return Cache::remember($cacheKey, config('lottery.performance.cache_ttl'), function () use ($gameName) {
            return LotteryGame::where('slug', $gameName)->exists();
        });
    }

    /**
     * Importa um concurso específico da API
     */
    public function importContest(string $gameName, int $contestNumber): array
    {
        if (!$this->validateGameExists($gameName)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível no banco de dados"];
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
                    'success'        => true,
                    'lottery_game'   => ucfirst($gameName),
                    'contest_number' => $contestNumber,
                    'prizes_count'   => $existingContest->prizes()->count(),
                    'message'        => 'Concurso já existia no banco',
                ];
            }

            // Cria ou atualiza o jogo
            $lotteryGame = $this->createOrUpdateLotteryGame($data);

            // Usa transação para operações relacionadas
            $prizes = DB::transaction(function () use ($lotteryGame, $data) {
                // Cria ou atualiza o concurso
                $contest = $this->createOrUpdateContest($lotteryGame, $data);

                // Cria os prêmios
                $prizes = $this->createPrizes($contest, $data['premiacoes'] ?? []);

                return [$contest, $prizes];
            });

            [$contest, $prizes] = $prizes;

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
     * Importa todos os concursos de um jogo específico (otimizado)
     */
    public function importAllContests(string $gameName, ?\Closure $progressCallback = null, ?int $fromContest = null, ?int $toContest = null): array
    {
        if (!$this->validateGameExists($gameName)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível no banco de dados"];
        }

        try {
            // Primeiro, busca o último concurso para saber quantos existem
            $latestResponse = $this->client->get("/{$gameName}/latest");

            if (!$latestResponse->successful()) {
                return ['success' => false, 'error' => "Falha ao buscar último concurso de {$gameName}"];
            }

            $latestData        = $latestResponse->json();
            $lastContestNumber = $latestData['concurso'];

            // Define o range de concursos
            $startContest = $fromContest ?? 1;
            $endContest   = $toContest ?? $lastContestNumber;

            // Valida o range
            if ($startContest < 1) {
                $startContest = 1;
            }

            if ($endContest > $lastContestNumber) {
                $endContest = $lastContestNumber;
            }

            if ($startContest > $endContest) {
                return ['success' => false, 'error' => "Concurso inicial ({$startContest}) não pode ser maior que o final ({$endContest})"];
            }

            $totalToImport = $endContest - $startContest + 1;

            // OTIMIZAÇÃO: Verifica concursos existentes em lote
            $existingContests = Contest::whereHas('lotteryGame', function ($query) use ($gameName) {
                $query->where('slug', Str::slug($gameName));
            })
            ->whereBetween('draw_number', [$startContest, $endContest])
            ->pluck('draw_number')
            ->toArray();

            $results = [
                'success'        => true,
                'lottery_game'   => ucfirst($gameName),
                'total_contests' => $lastContestNumber,
                'range_start'    => $startContest,
                'range_end'      => $endContest,
                'range_total'    => $totalToImport,
                'imported'       => 0,
                'failed'         => 0,
                'skipped'        => count($existingContests),
                'errors'         => [],
            ];

            // Importa apenas os concursos que não existem
            for ($contestNumber = $startContest; $contestNumber <= $endContest; $contestNumber++) {
                try {
                    // OTIMIZAÇÃO: Pula concursos que já existem
                    if (in_array($contestNumber, $existingContests)) {
                        if ($progressCallback) {
                            $progressCallback($contestNumber, $endContest, [
                                'success' => true,
                                'message' => 'Concurso já existia no banco',
                            ]);
                        }

                        continue;
                    }

                    $result = $this->importContest($gameName, $contestNumber);

                    if ($result['success']) {
                        $results['imported']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Concurso {$contestNumber}: " . $result['error'];
                    }

                    // Chama callback de progresso se fornecido
                    if ($progressCallback) {
                        $progressCallback($contestNumber, $endContest, $result);
                    }

                    // Pequena pausa para não sobrecarregar a API
                    usleep(config('lottery.performance.request_delay'));

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
        $drawDate = Carbon::createFromFormat('d/m/Y', $data['data']);

        return Contest::updateOrCreate(
            [
                'lottery_game_id' => $lotteryGame->id,
                'draw_number'     => $data['concurso'],
            ],
            [
                'draw_date' => $drawDate,
                'location'  => $data['local'] ?? null,
                'numbers'   => $data['dezenas'] ?? [],
            ]
        );
    }

    /**
     * Cria os prêmios do concurso usando batch insert
     */
    private function createPrizes(Contest $contest, array $premiacoes): array
    {
        // Remove prêmios existentes para evitar duplicatas
        $contest->prizes()->delete();

        if (empty($premiacoes)) {
            return [];
        }

        // Prepara dados para batch insert
        $prizesData = [];
        $now        = now();

        foreach ($premiacoes as $index => $premiacao) {
            $prizesData[] = [
                'contest_id'   => $contest->id,
                'tier'         => $index + 1,
                'description'  => $premiacao['descricao'] ?? ($index + 1) . ' acertos',
                'winners'      => $premiacao['ganhadores'],
                'prize_amount' => $premiacao['valorPremio'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // Batch insert - muito mais rápido que múltiplos inserts
        Prize::insert($prizesData);

        // Retorna os prêmios criados para compatibilidade
        return $contest->prizes()->get()->toArray();
    }

    /**
     * Importa concursos usando Jobs para processamento paralelo
     */
    public function importContestsParallel(string $gameName, array $contestNumbers): string
    {
        if (!$this->validateGameExists($gameName)) {
            throw new \InvalidArgumentException("Jogo '{$gameName}' não está disponível no banco de dados");
        }

        $jobId = uniqid('parallel_import_', true);

        // Dispara jobs para cada concurso
        foreach ($contestNumbers as $contestNumber) {
            ImportContestJob::dispatch($gameName, $contestNumber, $jobId)
                ->onQueue('lottery-import');
        }

        return $jobId;
    }

    /**
     * Versão otimizada para importar apenas concursos faltantes
     */
    public function importMissingContests(string $gameName, ?int $fromContest = null, ?int $toContest = null): array
    {
        if (!$this->validateGameExists($gameName)) {
            return ['success' => false, 'error' => "Jogo '{$gameName}' não está disponível no banco de dados"];
        }

        try {
            // Busca o último concurso
            $latestResponse = $this->client->get("/{$gameName}/latest");

            if (!$latestResponse->successful()) {
                return ['success' => false, 'error' => "Falha ao buscar último concurso de {$gameName}"];
            }

            $latestData        = $latestResponse->json();
            $lastContestNumber = $latestData['concurso'];

            $startContest = $fromContest ?? 1;
            $endContest   = $toContest ?? $lastContestNumber;

            // Busca concursos que já existem
            $existingContests = Contest::whereHas('lotteryGame', function ($query) use ($gameName) {
                $query->where('slug', Str::slug($gameName));
            })
            ->whereBetween('draw_number', [$startContest, $endContest])
            ->pluck('draw_number')
            ->toArray();

            // Lista de concursos faltantes
            $missingContests = [];

            for ($i = $startContest; $i <= $endContest; $i++) {
                if (!in_array($i, $existingContests)) {
                    $missingContests[] = $i;
                }
            }

            if (empty($missingContests)) {
                return [
                    'success'        => true,
                    'message'        => 'Todos os concursos já existem no banco',
                    'missing_count'  => 0,
                    'existing_count' => count($existingContests),
                ];
            }

            // Usar processamento paralelo se houver muitos concursos
            if (count($missingContests) > 10 && config('lottery.performance.background_processing')) {
                $jobId = $this->importContestsParallel($gameName, $missingContests);

                return [
                    'success'        => true,
                    'message'        => 'Importação iniciada em background',
                    'job_id'         => $jobId,
                    'missing_count'  => count($missingContests),
                    'existing_count' => count($existingContests),
                ];
            }

            // Importação sequencial para poucos concursos
            return $this->importAllContests($gameName, null, $startContest, $endContest);

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
