<?php

namespace Database\Factories;

use App\Models\Ticker;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticker>
 */
class TickerFactory extends Factory
{
    protected $model = Ticker::class;

    public function definition(): array
    {
        $open = 0.2100;
        $last = 0.2150;
        $change = $last - $open;
        $changePct = ($change / $open) * 100;

        return [
            'trading_pair_id' => TradingPair::factory(),
            'price_change' => $change,
            'price_change_percent' => $changePct,
            'weighted_avg_price' => ($open + $last) / 2,
            'last_price' => $last,
            'last_quantity' => $this->faker->randomFloat(2, 1, 50),
            'best_bid_price' => 0.2149,
            'best_bid_quantity' => $this->faker->randomFloat(2, 10, 200),
            'best_ask_price' => 0.2151,
            'best_ask_quantity' => $this->faker->randomFloat(2, 10, 200),
            'open_price' => $open,
            'high_price' => 0.2200,
            'low_price' => 0.2050,
            'volume' => $this->faker->randomFloat(2, 10000, 500000),
            'quote_volume' => $this->faker->randomFloat(2, 2000, 100000),
            'trade_count' => $this->faker->numberBetween(1000, 50000),
            'received_at' => now(),
        ];
    }
}
