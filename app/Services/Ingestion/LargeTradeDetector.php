<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\LargeTradeDetectorInterface;
use App\Models\LargeTrade;
use App\Models\Trade;

/**
 * Service for detecting large trades using a rolling window.
 *
 * Maintains an in-memory rolling window of recent trade sizes per trading pair.
 * When a trade's quantity exceeds a configurable multiplier of the rolling average,
 * it is persisted as a large trade record.
 */
class LargeTradeDetector implements LargeTradeDetectorInterface
{
    /**
     * Rolling window of trade sizes per trading pair for average computation.
     *
     * @var array<int, array<int, float>>
     */
    private array $tradeWindowSizes = [];

    /**
     * {@inheritdoc}
     */
    public function evaluate(int $tradingPairId, Trade $trade): void
    {
        $windowSize = config('binance.large_trade_window', 100);
        $multiplier = config('binance.large_trade_multiplier', 3);

        if (!isset($this->tradeWindowSizes[$tradingPairId])) {
            $this->tradeWindowSizes[$tradingPairId] = [];
        }

        $window = &$this->tradeWindowSizes[$tradingPairId];
        $qty = (float) $trade->quantity;
        $window[] = $qty;

        if (count($window) > $windowSize) {
            array_shift($window);
        }

        if (count($window) < 10) {
            return;
        }

        $avg = array_sum($window) / count($window);

        if ($avg > 0 && $qty > $multiplier * $avg) {
            LargeTrade::create([
                'trading_pair_id' => $tradingPairId,
                'trade_id' => $trade->id,
                'price' => $trade->price,
                'quantity' => $trade->quantity,
                'is_buyer_maker' => $trade->is_buyer_maker,
                'avg_trade_size' => $avg,
                'size_multiple' => round($qty / $avg, 2),
                'traded_at' => $trade->traded_at,
                'created_at' => now(),
            ]);
        }
    }
}
