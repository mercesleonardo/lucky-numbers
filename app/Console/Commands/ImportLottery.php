<?php

namespace App\Console\Commands;

use App\Services\LotteryGameService;
use Illuminate\Console\Command;

class ImportLottery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:import
                            {game? : Nome do jogo específico para importar histórico (ex: megasena, lotofacil)}
                            {--all : Importa histórico de todos os jogos disponíveis}
                            {--force : Pula a confirmação de segurança}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa TODOS os concursos dos jogos de loteria da API das Loterias da Caixa';

    /**
     * Execute the console command.
     */
    public function handle(LotteryGameService $lotteryService): int
    {
        $this->warn('🏛️ ATENÇÃO: Esta operação irá importar TODOS os concursos históricos!');
        $this->warn('⚠️ Isso pode levar muito tempo e fazer muitas requisições à API.');

        if (!$this->option('force') && !$this->confirm('Tem certeza que deseja continuar?')) {
            $this->info('Operação cancelada pelo usuário.');

            return Command::SUCCESS;
        }

        $this->info('📚 Iniciando importação de dados de loteria...');

        try {
            if ($this->option('all')) {
                return $this->importAllGames($lotteryService);
            }

            $game = $this->argument('game');

            if (!$game) {
                $game = $this->askForGame($lotteryService);
            }

            return $this->importSingleGame($lotteryService, $game);

        } catch (\Exception $e) {
            $this->error('❌ Erro durante a importação histórica: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Importa todos os jogos
     */
    private function importAllGames(LotteryGameService $lotteryService): int
    {
        $this->info('🎯 Importando TODOS os jogos...');

        $availableGames = $lotteryService->getAvailableGames();
        $overallStats   = [
            'total_games'      => count($availableGames),
            'successful_games' => 0,
            'failed_games'     => 0,
            'total_contests'   => 0,
            'total_imported'   => 0,
            'total_failed'     => 0,
        ];

        foreach ($availableGames as $game) {
            $this->newLine();
            $this->info("🎲 Processando {$game}...");

            $result = $lotteryService->importAllContests($game, function ($current, $total, $result) use ($game) {
                $percentage = round(($current / $total) * 100, 1);
                $status     = $result['success'] ? '✅' : '❌';
                $this->line("  └─ {$status} Concurso {$current}/{$total} ({$percentage}%)");
            });

            if ($result['success']) {
                $overallStats['successful_games']++;
                $overallStats['total_contests'] += $result['total_contests'];
                $overallStats['total_imported'] += $result['imported'];
                $overallStats['total_failed'] += $result['failed'];

                $this->info("✅ {$game}: {$result['imported']}/{$result['total_contests']} concursos importados");

                if ($result['failed'] > 0) {
                    $this->warn("⚠️ {$result['failed']} concursos falharam para {$game}");
                }
            } else {
                $overallStats['failed_games']++;
                $this->error("❌ Falha ao importar {$game}: " . $result['error']);
            }
        }

        // Relatório final
        $this->newLine(2);
        $this->info('📊 RELATÓRIO FINAL DA IMPORTAÇÃO:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Jogos processados', $overallStats['total_games']],
                ['Jogos bem-sucedidos', $overallStats['successful_games']],
                ['Jogos com falha', $overallStats['failed_games']],
                ['Total de concursos', $overallStats['total_contests']],
                ['Concursos importados', $overallStats['total_imported']],
                ['Concursos falharam', $overallStats['total_failed']],
                ['Taxa de sucesso', round(($overallStats['total_imported'] / max($overallStats['total_contests'], 1)) * 100, 2) . '%'],
            ]
        );

        return $overallStats['failed_games'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Importa um jogo específico
     */
    private function importSingleGame(LotteryGameService $lotteryService, string $game): int
    {
        $progressBar = null;

        $result = $lotteryService->importAllContests($game, function ($current, $total, $result) use (&$progressBar) {
            if (!$progressBar) {
                $progressBar = $this->output->createProgressBar($total);
                $progressBar->setFormat('  %current%/%max% [%bar%] %percent:3s%% - Concurso %current%');
                $progressBar->start();
            }

            $progressBar->advance();

            if (!$result['success']) {
                $this->newLine();
                $this->warn("⚠️ Erro no concurso {$current}: " . $result['error']);
            }
        });

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine(2);
        }

        if ($result['success']) {
            $this->info("✅ Importação concluída!");
            $this->info("🏆 Jogo: {$result['lottery_game']}");

            if (isset($result['range_start'])) {
                $this->info("📊 Range importado: {$result['range_start']} até {$result['range_end']} ({$result['range_total']} concursos)");
                $this->info("📈 Total de concursos do jogo: {$result['total_contests']}");
            } else {
                $this->info("📊 Total de concursos: {$result['total_contests']}");
            }

            $this->info("✅ Importados com sucesso: {$result['imported']}");

            if (isset($result['skipped']) && $result['skipped'] > 0) {
                $this->info("⏭️ Já existiam: {$result['skipped']}");
            }

            if ($result['failed'] > 0) {
                $this->warn("❌ Falharam: {$result['failed']}");

                if (!empty($result['errors'])) {
                    $this->newLine();
                    $this->warn('Erros encontrados:');

                    foreach (array_slice($result['errors'], 0, 5) as $error) {
                        $this->line("  • {$error}");
                    }

                    if (count($result['errors']) > 5) {
                        $this->line("  • ... e mais " . (count($result['errors']) - 5) . " erros");
                    }
                }
            }

            $total       = $result['range_total'] ?? $result['total_contests'];
            $successRate = round(($result['imported'] / $total) * 100, 2);
            $this->info("📈 Taxa de sucesso: {$successRate}%");

            return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
        } else {
            $this->error("❌ Falha na importação: " . $result['error']);

            return Command::FAILURE;
        }
    }

    /**
     * Pergunta ao usuário qual jogo importar
     */
    private function askForGame(LotteryGameService $lotteryService): string
    {
        $availableGames = $lotteryService->getAvailableGames();

        $this->info('🎯 Jogos disponíveis para importação histórica:');

        foreach ($availableGames as $index => $game) {
            $this->line("  " . ($index + 1) . ". " . ucfirst($game));
        }

        $choice = $this->choice(
            '🎲 Qual jogo você deseja importar o histórico completo?',
            $availableGames,
            0
        );

        return $choice;
    }
}
