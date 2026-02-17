<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\LargeTradeDetectorInterface;
use App\Contracts\Services\TradeIngestionServiceInterface;
use App\Models\Trade;
use Illuminate\Support\Carbon;

/**
 * Service for ingesting trade data from WebSocket.
 *
 * Persists aggregated trade records from the Binance WebSocket stream
 * and delegates to the large trade detector for threshold-based detection.
 */
class TradeIngestionService implements TradeIngestionServiceInterface
{
    /**
     * Create a new trade ingestion service instance.
     *
     * @param  \App\Contracts\Services\LargeTradeDetectorInterface  $largeTradeDetector  Detector for identifying large trades.
     */
    public function __construct(
        private LargeTradeDetectorInterface $largeTradeDetector,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function saveTrade(int $tradingPairId, array $data): void
    {
        $trade = Trade::create([
            'trading_pair_id' => $tradingPairId,
            'agg_trade_id' => $data['a'],
            'price' => $data['p'],
            'quantity' => $data['q'],
            'first_trade_id' => $data['f'],
            'last_trade_id' => $data['l'],
            'is_buyer_maker' => $data['m'],
            'traded_at' => Carbon::createFromTimestampMs($data['T']),
            'created_at' => now(),
        ]);

        $this->largeTradeDetector->evaluate($tradingPairId, $trade);
    }
}
