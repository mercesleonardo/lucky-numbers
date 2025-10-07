<?php

namespace App\Console\Commands;

use App\Services\LotteryGameService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledLotteryImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:scheduled-import 
                            {--type=latest : Tipo de importação (latest, gap-fill)}
                            {--games=* : Jogos específicos para importar}
                            {--days=7 : Número de dias para trás (gap-fill)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa importações automáticas de loterias (para uso em agendamento)';

    /**
     * Execute the console command.
     */
    public function handle(LotteryGameService $lotteryService): int
    {
        $type  = $this->option('type');
        $games = $this->option('games');
        $days  = (int) $this->option('days');

        $this->info("🤖 Importação automática iniciada - Tipo: {$type}");

        try {
            switch ($type) {
                case 'latest':
                    return $this->importLatestResults($lotteryService, $games);

                case 'gap-fill':
                    return $this->importGapFill($lotteryService, $games, $days);

                default:
                    $this->error("Tipo de importação inválido: {$type}");

                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Erro durante importação: {$e->getMessage()}");
            Log::error('Erro na importação agendada', [
                'type'  => $type,
                'games' => $games,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Importa os últimos resultados de todos os jogos ou jogos específicos
     */
    private function importLatestResults(LotteryGameService $lotteryService, array $games): int
    {
        if (empty($games)) {
            $this->info('📈 Importando últimos resultados de todos os jogos...');
            $results = $lotteryService->importAllGames();
        } else {
            $this->info('📈 Importando últimos resultados dos jogos: ' . implode(', ', $games));
            $results = [];

            foreach ($games as $game) {
                $results[$game] = $lotteryService->importGame($game);
            }
        }

        return $this->processResults($results, 'últimos resultados');
    }

    /**
     * Importa concursos dos últimos N dias para preencher lacunas
     */
    private function importGapFill(LotteryGameService $lotteryService, array $games, int $days): int
    {
        $targetGames = empty($games) ? $lotteryService->getAvailableGames() : $games;

        $this->info("🔄 Preenchendo lacunas dos últimos {$days} dias para jogos: " . implode(', ', $targetGames));

        $results = [];

        foreach ($targetGames as $game) {
            $this->line("Processando {$game}...");

            // Para gap-fill, importamos apenas alguns concursos recentes
            // Em vez de calcular datas, vamos importar os últimos 10 concursos
            $result = $lotteryService->importAllContests(
                $game,
                null, // sem callback de progresso
                null, // from será calculado pela API
                null  // to será o último concurso
            );

            // Limitamos para importar apenas se houver poucos concursos
            if (isset($result['total_contests'])) {
                $lastTen = max(1, $result['total_contests'] - 10);
                $result  = $lotteryService->importAllContests(
                    $game,
                    null,
                    $lastTen,
                    null
                );
            }

            $results[$game] = $result;
        }

        return $this->processResults($results, 'preenchimento de lacunas');
    }

    /**
     * Processa e exibe os resultados da importação
     */
    private function processResults(array $results, string $type): int
    {
        $totalSuccess = 0;
        $totalFailed  = 0;
        $hasErrors    = false;

        foreach ($results as $game => $result) {
            if ($result['success'] ?? false) {
                $imported = $result['imported'] ?? ($result['contest_number'] ? 1 : 0);
                $skipped  = $result['skipped'] ?? 0;

                $this->info("✅ {$game}: {$imported} importados" . ($skipped > 0 ? ", {$skipped} já existiam" : ""));
                $totalSuccess += $imported;
            } else {
                $error = $result['error'] ?? 'Erro desconhecido';
                $this->error("❌ {$game}: {$error}");
                $totalFailed++;
                $hasErrors = true;
            }
        }

        $this->newLine();
        $this->info("📊 Resumo da importação ({$type}):");
        $this->info("✅ Sucessos: {$totalSuccess}");

        if ($totalFailed > 0) {
            $this->warn("❌ Falhas: {$totalFailed}");
        }

        Log::info('Importação agendada concluída', [
            'type'          => $type,
            'total_success' => $totalSuccess,
            'total_failed'  => $totalFailed,
            'results'       => $results,
        ]);

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }
}
