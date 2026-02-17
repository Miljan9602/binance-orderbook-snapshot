<?php

namespace Database\Factories;

use App\Models\TradingPair;
use App\Models\VpinMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VpinMetric>
 */
class VpinMetricFactory extends Factory
{
    protected $model = VpinMetric::class;

    public function definition(): array
    {
        return [
            'trading_pair_id' => TradingPair::factory(),
            'vpin' => $this->faker->randomFloat(4, 0.1, 0.9),
            'bucket_volume' => $this->faker->randomFloat(2, 500, 5000),
            'bucket_count' => 20,
            'window_size' => 10,
            'computed_at' => now(),
        ];
    }
}
