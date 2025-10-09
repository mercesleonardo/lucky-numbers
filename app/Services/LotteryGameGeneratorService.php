<?php

namespace App\Services;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Models\{Contest, LotteryGame};

class LotteryGameGeneratorService
{
    // Configurações por jogo
    private const GAME_CONFIGS = [
        'megasena' => [
            'total_numbers' => 60,
            'pick_count'    => 6,
            'min_number'    => 1,
            'max_number'    => 60,
        ],
        'lotofacil' => [
            'total_numbers' => 25,
            'pick_count'    => 15,
            'min_number'    => 1,
            'max_number'    => 25,
        ],
        'quina' => [
            'total_numbers' => 80,
            'pick_count'    => 5,
            'min_number'    => 1,
            'max_number'    => 80,
        ],
    ];

    /**
     * Gera jogos inteligentes evitando números premiados recentemente
     */
    public function generateSmartGames(string $gameSlug, int $count = 1): array
    {
        if (!isset(self::GAME_CONFIGS[$gameSlug])) {
            throw new InvalidArgumentException("Jogo '{$gameSlug}' não suportado");
        }

        $config               = self::GAME_CONFIGS[$gameSlug];
        $recentWinningNumbers = $this->getRecentWinningNumbers($gameSlug);
        $games                = [];

        for ($i = 0; $i < $count; $i++) {
            $games[] = $this->generateSingleGame($config, $recentWinningNumbers);
        }

        return $games;
    }

    /**
     * Gera um único jogo inteligente
     */
    private function generateSingleGame(array $config, Collection $recentWinningNumbers): array
    {
        $attempts    = 0;
        $maxAttempts = 100;

        do {
            $attempts++;
            $game        = $this->generateRandomGame($config);
            $gameNumbers = collect($game);

            // Verifica se há muita sobreposição com números premiados recentemente
            $overlap    = $gameNumbers->intersect($recentWinningNumbers)->count();
            $maxOverlap = (int) ceil($config['pick_count'] * 0.4); // Máximo 40% de sobreposição

        } while ($overlap > $maxOverlap && $attempts < $maxAttempts);

        sort($game);

        return $game;
    }

    /**
     * Gera um jogo aleatório básico
     */
    private function generateRandomGame(array $config): array
    {
        $numbers = range($config['min_number'], $config['max_number']);
        shuffle($numbers);

        return array_slice($numbers, 0, $config['pick_count']);
    }

    /**
     * Obtém números premiados recentemente (últimos 10 concursos)
     */
    private function getRecentWinningNumbers(string $gameSlug): Collection
    {
        $cacheKey = "recent_winning_numbers_{$gameSlug}";

        return Cache::remember($cacheKey, 3600, function () use ($gameSlug) {
            $game = LotteryGame::where('slug', $gameSlug)->first();

            if (!$game) {
                return collect();
            }

            $recentContests = Contest::where('lottery_game_id', $game->id)
                ->orderBy('draw_number', 'desc')
                ->limit(10)
                ->get(['numbers']);

            $allNumbers = collect();

            foreach ($recentContests as $contest) {
                $numbers    = collect($contest->numbers)->map(fn ($n) => (int) $n);
                $allNumbers = $allNumbers->merge($numbers);
            }

            return $allNumbers->unique()->values();
        });
    }

    /**
     * Valida se um jogo está dentro das regras
     */
    public function validateGame(string $gameSlug, array $numbers): array
    {
        if (!isset(self::GAME_CONFIGS[$gameSlug])) {
            return [
                'valid'  => false,
                'errors' => ["Jogo '{$gameSlug}' não suportado"],
            ];
        }

        $config = self::GAME_CONFIGS[$gameSlug];
        $errors = [];

        // Verifica quantidade de números
        if (count($numbers) !== $config['pick_count']) {
            $errors[] = "O jogo deve ter exatamente {$config['pick_count']} números";
        }

        // Verifica range dos números
        foreach ($numbers as $number) {
            if ($number < $config['min_number'] || $number > $config['max_number']) {
                $errors[] = "Números devem estar entre {$config['min_number']} e {$config['max_number']}";

                break;
            }
        }

        // Verifica números duplicados
        if (count($numbers) !== count(array_unique($numbers))) {
            $errors[] = "Não é permitido números duplicados";
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Retorna as configurações de um jogo
     */
    public function getGameConfig(string $gameSlug): ?array
    {
        return self::GAME_CONFIGS[$gameSlug] ?? null;
    }

    /**
     * Retorna todos os jogos suportados
     */
    public function getSupportedGames(): array
    {
        return array_keys(self::GAME_CONFIGS);
    }
}
