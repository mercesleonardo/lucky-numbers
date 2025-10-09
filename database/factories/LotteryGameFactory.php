<?php

namespace Database\Factories;

use App\Models\LotteryGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LotteryGame>
 */
class LotteryGameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = LotteryGame::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $games = [
            'megasena'  => 'Megasena',
            'lotofacil' => 'Lotofácil',
            'quina'     => 'Quina',
        ];

        $slug = $this->faker->randomKey($games);

        return [
            'name' => $games[$slug],
            'slug' => $slug,
        ];
    }
}
