<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Contest, LotteryGame};
use App\Services\LotteryGameGeneratorService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;

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

    /**
     * Verifica se os números escolhidos pelo usuário já foram sorteados
     */
    public function checkNumbers(Request $request, string $gameSlug): JsonResponse
    {
        $game = LotteryGame::where('slug', $gameSlug)->first();

        if (!$game) {
            return response()->json([
                'error'           => 'Jogo não encontrado',
                'available_games' => ['megasena', 'lotofacil', 'quina'],
            ], 404);
        }

        // Validação dos números
        $validator = Validator::make($request->all(), [
            'numbers'   => 'required|array',
            'numbers.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => 'Números inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        $userNumbers = collect($request->numbers)->map(fn ($n) => (int) $n)->sort()->values()->toArray();

        // Validação específica por jogo usando o service
        $gameGenerator = app(LotteryGameGeneratorService::class);
        $validation    = $gameGenerator->validateGame($gameSlug, $userNumbers);

        if (!$validation['valid']) {
            return response()->json([
                'error'  => 'Jogo inválido para ' . $gameSlug,
                'errors' => $validation['errors'],
            ], 400);
        }

        // Busca por concursos com esses números exatos
        $contests = Contest::where('lottery_game_id', $game->id)
            ->get()
            ->filter(function ($contest) use ($userNumbers) {
                $contestNumbers = collect($contest->numbers)->map(fn ($n) => (int) $n)->sort()->values()->toArray();

                return $contestNumbers === $userNumbers;
            });

        if ($contests->isNotEmpty()) {
            return response()->json([
                'winner'       => true,
                'message'      => 'Esses números já foram sorteados!',
                'user_numbers' => $userNumbers,
                'contests'     => $contests->map(function ($contest) {
                    return [
                        'draw_number' => $contest->draw_number,
                        'draw_date'   => $contest->draw_date,
                        'location'    => $contest->location,
                        'numbers'     => $contest->numbers,
                    ];
                })->values(),
                'total_wins' => $contests->count(),
            ]);
        }

        return response()->json([
            'winner'       => false,
            'message'      => 'Esses números ainda não foram sorteados.',
            'user_numbers' => $userNumbers,
            'suggestion'   => 'Continue jogando, você pode ser o próximo ganhador!',
        ]);
    }
}
