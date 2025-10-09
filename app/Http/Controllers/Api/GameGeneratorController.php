<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LotteryGameGeneratorService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Validator};

class GameGeneratorController extends Controller
{
    public function __construct(
        private LotteryGameGeneratorService $gameGeneratorService
    ) {
    }

    /**
     * Gera jogos inteligentes para um jogo específico
     */
    public function generate(Request $request, string $gameSlug): JsonResponse
    {
        // Valida se o jogo é suportado
        if (!in_array($gameSlug, $this->gameGeneratorService->getSupportedGames())) {
            return response()->json([
                'error'           => 'Jogo não suportado',
                'supported_games' => $this->gameGeneratorService->getSupportedGames(),
            ], 400);
        }

        // Valida a requisição
        $validator = Validator::make($request->all(), [
            'count' => 'integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        $count      = $request->get('count', 1);
        $sessionKey = $this->getSessionKey($request);

        // Verifica limite de 20 jogos por sessão
        $gamesGenerated = $this->getSessionGameCount($sessionKey);

        if ($gamesGenerated + $count > 20) {
            $remaining = 20 - $gamesGenerated;

            return response()->json([
                'error'           => 'Limite de 20 jogos por sessão excedido',
                'generated_today' => $gamesGenerated,
                'remaining'       => $remaining,
                'max_allowed'     => $remaining > 0 ? $remaining : 0,
            ], 429);
        }

        try {
            // Gera os jogos
            $games = $this->gameGeneratorService->generateSmartGames($gameSlug, $count);

            // Atualiza contador da sessão
            $this->updateSessionGameCount($sessionKey, $count);

            $newTotal = $this->getSessionGameCount($sessionKey);

            return response()->json([
                'success'       => true,
                'game'          => $gameSlug,
                'games'         => $games,
                'count'         => $count,
                'session_stats' => [
                    'generated_today' => $newTotal,
                    'remaining'       => 20 - $newTotal,
                ],
                'config' => $this->gameGeneratorService->getGameConfig($gameSlug),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro interno ao gerar jogos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna informações sobre os jogos suportados
     */
    public function info(): JsonResponse
    {
        $games = [];

        foreach ($this->gameGeneratorService->getSupportedGames() as $game) {
            $config       = $this->gameGeneratorService->getGameConfig($game);
            $games[$game] = [
                'name'          => ucfirst($game),
                'pick_count'    => $config['pick_count'],
                'number_range'  => "{$config['min_number']}-{$config['max_number']}",
                'total_numbers' => $config['total_numbers'],
            ];
        }

        return response()->json([
            'supported_games' => $games,
            'daily_limit'     => 20,
            'rules'           => [
                'smart_generation' => 'Evita números premiados recentemente',
                'max_overlap'      => '40% máximo de sobreposição com números recentes',
                'session_limit'    => '20 jogos por IP/sessão por dia',
            ],
        ]);
    }

    /**
     * Retorna estatísticas da sessão atual
     */
    public function sessionStats(Request $request): JsonResponse
    {
        $sessionKey     = $this->getSessionKey($request);
        $gamesGenerated = $this->getSessionGameCount($sessionKey);

        return response()->json([
            'generated_today' => $gamesGenerated,
            'remaining'       => 20 - $gamesGenerated,
            'daily_limit'     => 20,
            'reset_time'      => 'Meia-noite (00:00)',
        ]);
    }

    /**
     * Gera chave única para a sessão (baseada no IP)
     */
    private function getSessionKey(Request $request): string
    {
        $ip   = $request->ip();
        $date = now()->format('Y-m-d');

        return "lottery_games_generated:{$ip}:{$date}";
    }

    /**
     * Obtém contador de jogos gerados na sessão
     */
    private function getSessionGameCount(string $sessionKey): int
    {
        return (int) Cache::get($sessionKey, 0);
    }

    /**
     * Atualiza contador de jogos gerados na sessão
     */
    private function updateSessionGameCount(string $sessionKey, int $count): void
    {
        $current  = $this->getSessionGameCount($sessionKey);
        $newCount = $current + $count;

        // Cache expira à meia-noite do próximo dia
        $expiresAt = now()->endOfDay();

        Cache::put($sessionKey, $newCount, $expiresAt);
    }
}
