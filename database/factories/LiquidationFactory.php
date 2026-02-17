<?php

namespace Database\Factories;

use App\Models\Liquidation;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Liquidation>
 */
class LiquidationFactory extends Factory
{
    protected $model = Liquidation::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(4, 0.2100, 0.2200);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'side' => $this->faker->randomElement(['BUY', 'SELL']),
            'order_type' => 'LIMIT',
            'price' => $price,
            'quantity' => $this->faker->randomFloat(2, 100, 10000),
            'avg_price' => $price + $this->faker->randomFloat(4, -0.001, 0.001),
            'order_status' => 'FILLED',
            'order_time' => now(),
            'received_at' => now(),
        ];
    }

    public function buy(): static
    {
        return $this->state(fn() => ['side' => 'BUY']);
    }

    public function sell(): static
    {
        return $this->state(fn() => ['side' => 'SELL']);
    }
}
