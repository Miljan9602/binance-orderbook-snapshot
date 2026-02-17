<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\OrderbookIngestionServiceInterface;
use App\Models\OrderbookHistory;
use App\Models\OrderbookMetric;
use App\Models\OrderbookSnapshot;
use App\Models\OrderbookWall;
use App\Models\TradingPair;

/**
 * Service for ingesting orderbook data from WebSocket.
 *
 * Handles snapshot upsert, history append, and sampled orderbook metric
 * computation including imbalance, VWAP mid price, and spread in basis points.
 * Metrics are written at a configurable sample interval to control storage volume.
 */
class OrderbookIngestionService implements OrderbookIngestionServiceInterface
{
    /**
     * Tracks the last metric write timestamp per trading pair to enforce the sample interval.
     *
     * @var array<int, int>
     */
    private array $lastMetricsWrite = [];

    /**
     * {@inheritdoc}
     */
    public function updateOrderbook(int $tradingPairId, array $data): void
    {
        $bids = $data['bids'] ?? [];
        $asks = $data['asks'] ?? [];
        $lastUpdateId = $data['lastUpdateId'] ?? 0;

        $bestBidPrice = !empty($bids) ? (float) $bids[0][0] : 0;
        $bestAskPrice = !empty($asks) ? (float) $asks[0][0] : 0;
        $spread = $bestAskPrice - $bestBidPrice;

        $now = now();

        $attributes = [
            'last_update_id' => $lastUpdateId,
            'bids' => $bids,
            'asks' => $asks,
            'best_bid_price' => $bestBidPrice,
            'best_ask_price' => $bestAskPrice,
            'spread' => $spread,
            'received_at' => $now,
        ];

        OrderbookSnapshot::updateOrCreate(
            ['trading_pair_id' => $tradingPairId],
            $attributes
        );

        TradingPair::where('id', $tradingPairId)->update(['last_update_at' => $now]);

        OrderbookHistory::create(array_merge(
            ['trading_pair_id' => $tradingPairId],
            $attributes
        ));

        $this->computeOrderbookMetrics($tradingPairId, $bids, $asks, $now);

        $this->detectWalls($tradingPairId, $bids, $asks);
    }

    /**
     * Compute and store orderbook metrics at sampled intervals.
     *
     * Calculates bid/ask volume totals, imbalance ratio, mid price,
     * volume-weighted mid price, and spread in basis points. Only writes
     * to the database if the configured sample interval has elapsed.
     *
     * @param  int  $tradingPairId  The trading pair to compute metrics for.
     * @param  array<int, array{0: string, 1: string}>  $bids  Bid levels as [price, quantity] pairs.
     * @param  array<int, array{0: string, 1: string}>  $asks  Ask levels as [price, quantity] pairs.
     * @param  \Illuminate\Support\Carbon  $now  The current timestamp.
     * @return void
     */
    private function computeOrderbookMetrics(int $tradingPairId, array $bids, array $asks, $now): void
    {
        $interval = config('binance.metrics_sample_interval');
        $lastWrite = $this->lastMetricsWrite[$tradingPairId] ?? 0;

        if (($now->timestamp - $lastWrite) < $interval) {
            return;
        }

        $bidVolume = 0;
        foreach ($bids as $bid) {
            $bidVolume += (float) $bid[1];
        }

        $askVolume = 0;
        foreach ($asks as $ask) {
            $askVolume += (float) $ask[1];
        }

        $totalVolume = $bidVolume + $askVolume;
        $imbalance = $totalVolume > 0 ? ($bidVolume - $askVolume) / $totalVolume : 0;

        $bestBidPrice = !empty($bids) ? (float) $bids[0][0] : 0;
        $bestAskPrice = !empty($asks) ? (float) $asks[0][0] : 0;
        $midPrice = ($bestBidPrice + $bestAskPrice) / 2;

        $bestBidQty = !empty($bids) ? (float) $bids[0][1] : 0;
        $bestAskQty = !empty($asks) ? (float) $asks[0][1] : 0;
        $totalBestQty = $bestBidQty + $bestAskQty;
        $weightedMidPrice = $totalBestQty > 0
            ? ($bestBidPrice * $bestAskQty + $bestAskPrice * $bestBidQty) / $totalBestQty
            : $midPrice;

        $spreadBps = $midPrice > 0 ? (($bestAskPrice - $bestBidPrice) / $midPrice) * 10000 : 0;

        OrderbookMetric::create([
            'trading_pair_id' => $tradingPairId,
            'bid_volume' => $bidVolume,
            'ask_volume' => $askVolume,
            'imbalance' => $imbalance,
            'mid_price' => $midPrice,
            'weighted_mid_price' => $weightedMidPrice,
            'spread_bps' => $spreadBps,
            'received_at' => $now,
        ]);

        $this->lastMetricsWrite[$tradingPairId] = $now->timestamp;
    }

    /**
     * Detect orderbook walls (large resting orders) from the current snapshot.
     *
     * Identifies price levels where the quantity exceeds a configurable multiple
     * of the average quantity across all levels on that side. Tracks wall lifecycle
     * by creating new ACTIVE walls and marking removed walls when they disappear.
     *
     * @param  int  $tradingPairId  The trading pair to detect walls for.
     * @param  array<int, array{0: string, 1: string}>  $bids  Bid levels as [price, quantity] pairs.
     * @param  array<int, array{0: string, 1: string}>  $asks  Ask levels as [price, quantity] pairs.
     * @return void
     */
    public function detectWalls(int $tradingPairId, array $bids, array $asks): void
    {
        $multiplier = config('binance.wall_detection_multiplier', 3);
        $now = now();

        $sides = [
            'BID' => $bids,
            'ASK' => $asks,
        ];

        foreach ($sides as $side => $levels) {
            if (empty($levels)) {
                continue;
            }

            // Compute average quantity across all levels on this side
            $totalQuantity = 0;
            foreach ($levels as $level) {
                $totalQuantity += (float) $level[1];
            }
            $avgQuantity = $totalQuantity / count($levels);

            if ($avgQuantity <= 0) {
                continue;
            }

            $threshold = $multiplier * $avgQuantity;

            // Find wall levels (quantity exceeds threshold)
            $wallPrices = [];
            foreach ($levels as $level) {
                $price = (float) $level[0];
                $quantity = (float) $level[1];

                if ($quantity > $threshold) {
                    $wallPrices[(string) $price] = true;

                    // Check if an ACTIVE wall already exists at this price+side
                    $existing = OrderbookWall::forTradingPair($tradingPairId)
                        ->active()
                        ->where('side', $side)
                        ->where('price', $level[0])
                        ->first();

                    if (!$existing) {
                        OrderbookWall::create([
                            'trading_pair_id' => $tradingPairId,
                            'side' => $side,
                            'price' => $price,
                            'quantity' => $quantity,
                            'avg_level_quantity' => $avgQuantity,
                            'size_multiple' => $quantity / $avgQuantity,
                            'status' => 'ACTIVE',
                            'detected_at' => $now,
                        ]);
                    }
                }
            }

            // Mark ACTIVE walls as REMOVED if no longer present with sufficient quantity
            $activeWalls = OrderbookWall::forTradingPair($tradingPairId)
                ->active()
                ->where('side', $side)
                ->get();

            foreach ($activeWalls as $wall) {
                $priceKey = (string) (float) $wall->price;
                if (!isset($wallPrices[$priceKey])) {
                    $wall->update([
                        'status' => 'REMOVED',
                        'removed_at' => $now,
                    ]);
                }
            }
        }
    }
}
