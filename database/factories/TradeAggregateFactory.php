<?php

namespace Database\Factories;

use App\Models\TradeAggregate;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradeAggregate>
 */
class TradeAggregateFactory extends Factory
{
    protected $model = TradeAggregate::class;

    public function definition(): array
    {
        $buyVol = $this->faker->randomFloat(2, 10, 500);
        $sellVol = $this->faker->randomFloat(2, 10, 500);
        $tradeCount = $this->faker->numberBetween(5, 200);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'interval' => '1m',
            'period_start' => now()->startOfMinute()->subMinute(),
            'vwap' => $this->faker->randomFloat(4, 0.2100, 0.2200),
            'buy_volume' => $buyVol,
            'sell_volume' => $sellVol,
            'cvd' => $buyVol - $sellVol,
            'trade_count' => $tradeCount,
            'avg_trade_size' => ($buyVol + $sellVol) / max($tradeCount, 1),
            'max_trade_size' => $this->faker->randomFloat(2, 50, 200),
            'close_price' => $this->faker->randomFloat(4, 0.2100, 0.2200),
            'price_change_pct' => $this->faker->randomFloat(4, -2, 2),
            'realized_vol_5m' => $this->faker->randomFloat(4, 0.01, 1.0),
        ];
    }

    public function withoutVolatility(): static
    {
        return $this->state(fn() => [
            'realized_vol_5m' => null,
            'price_change_pct' => null,
        ]);
    }
}
