<?php

namespace Database\Factories;

use App\Models\LargeTrade;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LargeTrade>
 */
class LargeTradeFactory extends Factory
{
    protected $model = LargeTrade::class;

    public function definition(): array
    {
        $avgSize = $this->faker->randomFloat(2, 10, 50);
        $multiple = $this->faker->randomFloat(1, 3.1, 10);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'trade_id' => $this->faker->numberBetween(1, 99999),
            'price' => $this->faker->randomFloat(4, 0.2100, 0.2200),
            'quantity' => round($avgSize * $multiple, 2),
            'is_buyer_maker' => $this->faker->boolean(),
            'avg_trade_size' => $avgSize,
            'size_multiple' => $multiple,
            'traded_at' => now(),
            'created_at' => now(),
        ];
    }
}
