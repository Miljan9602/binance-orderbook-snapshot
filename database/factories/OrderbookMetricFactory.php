<?php

namespace Database\Factories;

use App\Models\OrderbookMetric;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderbookMetric>
 */
class OrderbookMetricFactory extends Factory
{
    protected $model = OrderbookMetric::class;

    public function definition(): array
    {
        $bidVol = $this->faker->randomFloat(2, 500, 5000);
        $askVol = $this->faker->randomFloat(2, 500, 5000);
        $total = $bidVol + $askVol;

        return [
            'trading_pair_id' => TradingPair::factory(),
            'bid_volume' => $bidVol,
            'ask_volume' => $askVol,
            'imbalance' => $total > 0 ? ($bidVol - $askVol) / $total : 0,
            'mid_price' => $this->faker->randomFloat(4, 0.2140, 0.2160),
            'weighted_mid_price' => $this->faker->randomFloat(4, 0.2140, 0.2160),
            'spread_bps' => $this->faker->randomFloat(2, 0.5, 20),
            'received_at' => now(),
        ];
    }
}
