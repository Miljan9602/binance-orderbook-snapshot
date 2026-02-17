<?php

namespace Database\Factories;

use App\Models\OrderbookWall;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderbookWall>
 */
class OrderbookWallFactory extends Factory
{
    protected $model = OrderbookWall::class;

    public function definition(): array
    {
        return [
            'trading_pair_id' => TradingPair::factory(),
            'side' => fake()->randomElement(['BID', 'ASK']),
            'price' => fake()->randomFloat(4, 0.1, 1.0),
            'quantity' => fake()->randomFloat(2, 1000, 50000),
            'avg_level_quantity' => fake()->randomFloat(2, 100, 500),
            'size_multiple' => fake()->randomFloat(1, 3.0, 15.0),
            'status' => 'ACTIVE',
            'detected_at' => now(),
            'removed_at' => null,
        ];
    }
}
