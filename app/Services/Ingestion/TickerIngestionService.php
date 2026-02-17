<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\TickerIngestionServiceInterface;
use App\Models\Ticker;

/**
 * Service for ingesting ticker data from WebSocket.
 */
class TickerIngestionService implements TickerIngestionServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function updateTicker(int $tradingPairId, array $data): void
    {
        Ticker::updateOrCreate(
            ['trading_pair_id' => $tradingPairId],
            [
                'price_change' => $data['p'],
                'price_change_percent' => $data['P'],
                'weighted_avg_price' => $data['w'],
                'last_price' => $data['c'],
                'last_quantity' => $data['Q'],
                'best_bid_price' => $data['b'],
                'best_bid_quantity' => $data['B'],
                'best_ask_price' => $data['a'],
                'best_ask_quantity' => $data['A'],
                'open_price' => $data['o'],
                'high_price' => $data['h'],
                'low_price' => $data['l'],
                'volume' => $data['v'],
                'quote_volume' => $data['q'],
                'trade_count' => $data['n'],
                'received_at' => now(),
            ]
        );
    }
}
