<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\FuturesIngestionServiceInterface;
use App\Models\FuturesMetric;
use App\Models\FuturesMetricHistory;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use App\Models\TradingPair;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for ingesting futures data from WebSocket and REST API.
 *
 * Handles persistence of mark price updates (with sampled history),
 * liquidation events, and open interest polling from the Binance Futures REST API.
 */
class FuturesIngestionService implements FuturesIngestionServiceInterface
{
    /**
     * Tracks the last history write timestamp per trading pair to enforce the sample interval.
     *
     * @var array<int, int>
     */
    private array $lastHistoryWrite = [];

    /**
     * {@inheritdoc}
     */
    public function updateMarkPrice(int $tradingPairId, array $data): void
    {
        $now = now();

        $attributes = [
            'mark_price' => $data['p'],
            'index_price' => $data['i'],
            'funding_rate' => $data['r'],
            'next_funding_time' => Carbon::createFromTimestampMs($data['T']),
            'received_at' => $now,
        ];

        FuturesMetric::updateOrCreate(
            ['trading_pair_id' => $tradingPairId],
            $attributes
        );

        $interval = config('binance.metrics_sample_interval');
        $lastWrite = $this->lastHistoryWrite[$tradingPairId] ?? 0;

        if (($now->timestamp - $lastWrite) >= $interval) {
            FuturesMetricHistory::create(array_merge(
                ['trading_pair_id' => $tradingPairId],
                $attributes
            ));
            $this->lastHistoryWrite[$tradingPairId] = $now->timestamp;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveLiquidation(int $tradingPairId, array $data): void
    {
        $order = $data['o'];

        Liquidation::create([
            'trading_pair_id' => $tradingPairId,
            'side' => $order['S'],
            'order_type' => $order['o'],
            'price' => $order['p'],
            'quantity' => $order['q'],
            'avg_price' => $order['ap'],
            'order_status' => $order['X'],
            'order_time' => Carbon::createFromTimestampMs($order['T']),
            'received_at' => now(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAndSaveOpenInterest(): int
    {
        $pairs = TradingPair::active()->hasFuturesSymbol()->get();
        $saved = 0;

        foreach ($pairs as $pair) {
            try {
                $response = Http::get(config('binance.futures_rest_base_url') . '/fapi/v1/openInterest', [
                    'symbol' => strtoupper($pair->futures_symbol),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    OpenInterest::create([
                        'trading_pair_id' => $pair->id,
                        'open_interest' => $data['openInterest'],
                        'timestamp' => Carbon::createFromTimestampMs($data['time']),
                        'received_at' => now(),
                    ]);

                    $saved++;
                } else {
                    Log::warning('Failed to fetch open interest', [
                        'symbol' => $pair->futures_symbol,
                        'status' => $response->status(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error fetching open interest', [
                    'symbol' => $pair->futures_symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $saved;
    }
}
