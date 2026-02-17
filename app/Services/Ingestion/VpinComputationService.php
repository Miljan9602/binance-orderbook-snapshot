<?php

namespace App\Services\Ingestion;

use App\Contracts\Repositories\TradingPairRepositoryInterface;
use App\Contracts\Repositories\TradeRepositoryInterface;
use App\Contracts\Services\VpinComputationServiceInterface;
use App\Models\VpinMetric;

/**
 * Service for computing Volume-Synchronized Probability of Informed Trading (VPIN).
 *
 * Uses volume-synchronized bucketing of recent trades to measure the probability
 * of informed trading activity. Higher VPIN values indicate greater order flow toxicity.
 */
class VpinComputationService implements VpinComputationServiceInterface
{
    public function __construct(
        private readonly TradingPairRepositoryInterface $tradingPairRepository,
        private readonly TradeRepositoryInterface $tradeRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function computeAll(): void
    {
        $pairs = $this->tradingPairRepository->getActive();

        foreach ($pairs as $pair) {
            $this->compute($pair->id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compute(int $tradingPairId): void
    {
        $bucketCount = (int) config('binance.vpin_bucket_count', 20);
        $windowSize = (int) config('binance.vpin_window', 10);

        $trades = $this->tradeRepository->getRecentTradesForChart($tradingPairId, 500);

        if ($trades->isEmpty()) {
            return;
        }

        // Calculate total volume from all trades.
        $totalVolume = 0.0;
        foreach ($trades as $trade) {
            $totalVolume += (float) $trade->quantity;
        }

        if ($totalVolume <= 0) {
            return;
        }

        // Determine bucket size.
        $bucketSize = $totalVolume / $bucketCount;

        if ($bucketSize <= 0) {
            return;
        }

        // Fill buckets with volume-synchronized bucketing.
        $buckets = [];
        $currentBuyVolume = 0.0;
        $currentSellVolume = 0.0;
        $currentBucketVolume = 0.0;

        foreach ($trades as $trade) {
            $qty = (float) $trade->quantity;

            // Classify trade: is_buyer_maker = false means taker bought (buy), true means taker sold (sell).
            $isBuy = !$trade->is_buyer_maker;

            $remaining = $qty;

            while ($remaining > 0) {
                $spaceInBucket = $bucketSize - $currentBucketVolume;
                $fill = min($remaining, $spaceInBucket);

                if ($isBuy) {
                    $currentBuyVolume += $fill;
                } else {
                    $currentSellVolume += $fill;
                }

                $currentBucketVolume += $fill;
                $remaining -= $fill;

                // Bucket is full, save it and start a new one.
                if ($currentBucketVolume >= $bucketSize) {
                    $buckets[] = [
                        'buy_volume' => $currentBuyVolume,
                        'sell_volume' => $currentSellVolume,
                    ];

                    $currentBuyVolume = 0.0;
                    $currentSellVolume = 0.0;
                    $currentBucketVolume = 0.0;
                }
            }
        }

        // Skip if not enough filled buckets for the window.
        if (count($buckets) < $windowSize) {
            return;
        }

        // Compute VPIN over the last window_size buckets.
        $windowBuckets = array_slice($buckets, -$windowSize);
        $imbalanceSum = 0.0;

        foreach ($windowBuckets as $bucket) {
            $bucketTotal = $bucket['buy_volume'] + $bucket['sell_volume'];

            if ($bucketTotal > 0) {
                $imbalanceSum += abs($bucket['buy_volume'] - $bucket['sell_volume']) / $bucketTotal;
            }
        }

        $vpin = $imbalanceSum / $windowSize;

        VpinMetric::create([
            'trading_pair_id' => $tradingPairId,
            'vpin' => $vpin,
            'bucket_volume' => $bucketSize,
            'bucket_count' => $bucketCount,
            'window_size' => $windowSize,
            'computed_at' => now(),
        ]);
    }
}
