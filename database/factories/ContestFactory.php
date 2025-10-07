<?php

namespace Database\Factories;

use App\Models\{Contest, LotteryGame};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contest>
 */
class ContestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Contest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lottery_game_id'           => LotteryGame::factory(),
            'draw_number'               => $this->faker->numberBetween(1000, 9999),
            'draw_date'                 => $this->faker->date(),
            'location'                  => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'numbers'                   => $this->faker->randomElements(range(1, 60), 6),
            'has_accumulated'           => $this->faker->boolean(),
            'next_draw_number'          => $this->faker->numberBetween(1000, 9999),
            'next_draw_date'            => $this->faker->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
            'estimated_prize_next_draw' => $this->faker->randomFloat(2, 1000000, 50000000),
            'extra_data'                => [
                'observacao'      => $this->faker->sentence(),
                'valorArrecadado' => $this->faker->randomFloat(2, 10000000, 100000000),
            ],
        ];
    }
}
