<?php

namespace App\Console\Commands;

use App\Services\LotteryGameService;
use Illuminate\Console\Command;

class ImportLotteryGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery:import
                            {game? : Nome do jogo específico para importar (ex: megasena, lotofacil)}
                            {--all : Importa todos os jogos disponíveis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados dos jogos de loteria da API das Loterias da Caixa';

    /**
     * Execute the console command.
     */
    public function handle(LotteryGameService $lotteryService): int
    {
        $this->info('🎲 Iniciando importação dos dados de loteria...');

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
            $this->error('❌ Erro durante a importação: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Importa todos os jogos disponíveis
     */
    private function importAllGames(LotteryGameService $lotteryService): int
    {
        $this->info('📥 Importando todos os jogos disponíveis...');

        $results = $lotteryService->importAllGames();

        $this->newLine();
        $this->info('📊 Resultado da importação:');

        $successCount = 0;
        foreach ($results as $game => $result) {
            if ($result['success']) {
                $this->line("✅ {$game}: Concurso {$result['contest_number']} com {$result['prizes_count']} prêmios");
                $successCount++;
            } else {
                $this->error("❌ {$game}: {$result['error']}");
            }
        }

        $this->newLine();
        $this->info("🎯 Importação concluída! {$successCount} de " . count($results) . " jogos importados com sucesso.");

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Importa um jogo específico
     */
    private function importSingleGame(LotteryGameService $lotteryService, string $game): int
    {
        $this->info("📥 Importando dados do jogo: {$game}");

        $result = $lotteryService->importGame($game);

        if ($result['success']) {
            $this->info("✅ Jogo '{$result['lottery_game']}' importado com sucesso!");
            $this->line("🎲 Concurso: {$result['contest_number']}");
            $this->line("🏆 Prêmios: {$result['prizes_count']}");
            return Command::SUCCESS;
        }

        $this->error("❌ Falha ao importar o jogo {$game}");
        return Command::FAILURE;
    }

    /**
     * Pergunta ao usuário qual jogo importar
     */
    private function askForGame(LotteryGameService $lotteryService): string
    {
        $games = $lotteryService->getAvailableGames();

        return $this->choice(
            'Qual jogo você deseja importar?',
            $games,
            0
        );
    }
}
