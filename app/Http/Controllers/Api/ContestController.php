<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Contest, LotteryGame};
use Illuminate\Http\JsonResponse;

class ContestController extends Controller
{
    /**
     * Retorna o último concurso de um jogo específico
     */
    public function latest(string $gameSlug): JsonResponse
    {
        $game = LotteryGame::where('slug', $gameSlug)->first();

        if (!$game) {
            return response()->json([
                'error'           => 'Jogo não encontrado',
                'available_games' => ['megasena', 'lotofacil', 'quina'],
            ], 404);
        }

        $contest = Contest::where('lottery_game_id', $game->id)
            ->orderBy('draw_number', 'desc')
            ->first();

        if (!$contest) {
            return response()->json([
                'error' => 'Nenhum concurso encontrado para este jogo',
            ], 404);
        }

        return response()->json([
            'game' => [
                'name' => $game->name,
                'slug' => $game->slug,
            ],
            'contest' => [
                'draw_number' => $contest->draw_number,
                'draw_date'   => $contest->draw_date,
                'location'    => $contest->location,
                'numbers'     => $contest->numbers, // Laravel já faz o cast automático para array
            ],
        ]);
    }

    /**
     * Retorna os últimos concursos de todos os jogos
     */
    public function latestAll(): JsonResponse
    {
        $games   = LotteryGame::all();
        $results = [];

        foreach ($games as $game) {
            $contest = Contest::where('lottery_game_id', $game->id)
                ->orderBy('draw_number', 'desc')
                ->first();

            if ($contest) {
                $results[] = [
                    'game' => [
                        'name' => $game->name,
                        'slug' => $game->slug,
                    ],
                    'contest' => [
                        'draw_number' => $contest->draw_number,
                        'draw_date'   => $contest->draw_date,
                        'location'    => $contest->location,
                        'numbers'     => $contest->numbers, // Laravel já faz o cast automático para array
                    ],
                ];
            }
        }

        return response()->json($results);
    }
}
