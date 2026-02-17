<?php

namespace Database\Factories;

use App\Models\Kline;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kline>
 */
class KlineFactory extends Factory
{
    protected $model = Kline::class;

    public function definition(): array
    {
        $open = $this->faker->randomFloat(4, 0.2100, 0.2200);
        $close = $open + $this->faker->randomFloat(4, -0.005, 0.005);
        $high = max($open, $close) + $this->faker->randomFloat(4, 0, 0.002);
        $low = min($open, $close) - $this->faker->randomFloat(4, 0, 0.002);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'interval' => '1m',
            'open_time' => now()->startOfMinute(),
            'close_time' => now()->startOfMinute()->addMinute(),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => $this->faker->randomFloat(2, 100, 10000),
            'quote_volume' => $this->faker->randomFloat(2, 20, 2000),
            'taker_buy_volume' => $this->faker->randomFloat(2, 50, 5000),
            'taker_buy_quote_volume' => $this->faker->randomFloat(2, 10, 1000),
            'trade_count' => $this->faker->numberBetween(10, 500),
            'is_closed' => true,
            'received_at' => now(),
        ];
    }

    public function interval(string $interval): static
    {
        return $this->state(fn() => ['interval' => $interval]);
    }

    public function open(): static
    {
        return $this->state(fn() => ['is_closed' => false]);
    }
}
