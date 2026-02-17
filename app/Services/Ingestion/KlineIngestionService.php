<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\KlineIngestionServiceInterface;
use App\Models\Kline;
use Illuminate\Support\Carbon;

/**
 * Service for ingesting kline data from WebSocket.
 */
class KlineIngestionService implements KlineIngestionServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function updateKline(int $tradingPairId, array $data): void
    {
        $k = $data['k'];

        Kline::updateOrCreate(
            [
                'trading_pair_id' => $tradingPairId,
                'interval' => $k['i'],
                'open_time' => Carbon::createFromTimestampMs($k['t']),
            ],
            [
                'close_time' => Carbon::createFromTimestampMs($k['T']),
                'open' => $k['o'],
                'high' => $k['h'],
                'low' => $k['l'],
                'close' => $k['c'],
                'volume' => $k['v'],
                'quote_volume' => $k['q'],
                'taker_buy_volume' => $k['V'],
                'taker_buy_quote_volume' => $k['Q'],
                'trade_count' => $k['n'],
                'is_closed' => $k['x'],
                'received_at' => now(),
            ]
        );
    }
}
