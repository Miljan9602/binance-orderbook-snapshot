<?php

namespace Database\Factories;

use App\Models\OrderbookHistory;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderbookHistory>
 */
class OrderbookHistoryFactory extends Factory
{
    protected $model = OrderbookHistory::class;

    public function definition(): array
    {
        $basePrice = 0.2150;
        $bids = [];
        $asks = [];
        for ($i = 0; $i < 20; $i++) {
            $bids[] = [number_format($basePrice - ($i * 0.0001), 4, '.', ''), number_format($this->faker->randomFloat(2, 10, 500), 2, '.', '')];
            $asks[] = [number_format($basePrice + 0.0001 + ($i * 0.0001), 4, '.', ''), number_format($this->faker->randomFloat(2, 10, 500), 2, '.', '')];
        }

        return [
            'trading_pair_id' => TradingPair::factory(),
            'last_update_id' => $this->faker->numberBetween(100000, 999999),
            'bids' => $bids,
            'asks' => $asks,
            'best_bid_price' => $bids[0][0],
            'best_ask_price' => $asks[0][0],
            'spread' => (float) $asks[0][0] - (float) $bids[0][0],
            'received_at' => now(),
        ];
    }
}
