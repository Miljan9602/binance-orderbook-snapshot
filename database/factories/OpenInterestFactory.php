<?php

namespace Database\Factories;

use App\Models\OpenInterest;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OpenInterest>
 */
class OpenInterestFactory extends Factory
{
    protected $model = OpenInterest::class;

    public function definition(): array
    {
        return [
            'trading_pair_id' => TradingPair::factory(),
            'open_interest' => $this->faker->randomFloat(2, 100000, 5000000),
            'timestamp' => now(),
            'received_at' => now(),
        ];
    }
}
