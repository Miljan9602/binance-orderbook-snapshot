<?php

namespace Database\Factories;

use App\Models\Trade;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        return [
            'trading_pair_id' => TradingPair::factory(),
            'agg_trade_id' => $this->faker->numberBetween(100000, 999999),
            'price' => $this->faker->randomFloat(4, 0.2100, 0.2200),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'first_trade_id' => $this->faker->numberBetween(1000, 9999),
            'last_trade_id' => $this->faker->numberBetween(10000, 19999),
            'is_buyer_maker' => false,
            'traded_at' => now(),
            'created_at' => now(),
        ];
    }

    public function buyerMaker(): static
    {
        return $this->state(fn() => ['is_buyer_maker' => true]);
    }

    public function sellerMaker(): static
    {
        return $this->state(fn() => ['is_buyer_maker' => false]);
    }
}
