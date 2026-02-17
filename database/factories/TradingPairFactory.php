<?php

namespace Database\Factories;

use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradingPair>
 */
class TradingPairFactory extends Factory
{
    protected $model = TradingPair::class;

    public function definition(): array
    {
        return [
            'symbol' => 'SEIUSDC',
            'base_asset' => 'SEI',
            'quote_asset' => 'USDC',
            'stream_name' => 'seiusdc@depth20',
            'futures_symbol' => 'seiusdt',
            'is_active' => true,
            'depth_level' => 20,
            'last_update_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function withoutFutures(): static
    {
        return $this->state(fn() => ['futures_symbol' => null]);
    }
}
