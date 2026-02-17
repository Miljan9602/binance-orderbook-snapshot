<?php

namespace Database\Factories;

use App\Models\FuturesMetric;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuturesMetric>
 */
class FuturesMetricFactory extends Factory
{
    protected $model = FuturesMetric::class;

    public function definition(): array
    {
        $markPrice = $this->faker->randomFloat(4, 0.2140, 0.2180);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'mark_price' => $markPrice,
            'index_price' => $markPrice + $this->faker->randomFloat(4, -0.001, 0.001),
            'funding_rate' => $this->faker->randomFloat(8, -0.001, 0.001),
            'next_funding_time' => now()->addHours($this->faker->numberBetween(1, 8)),
            'received_at' => now(),
        ];
    }
}
