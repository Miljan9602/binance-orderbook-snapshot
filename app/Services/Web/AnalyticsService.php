<?php

namespace App\Services\Web;

use App\Contracts\Repositories\FuturesRepositoryInterface;
use App\Contracts\Repositories\OrderbookRepositoryInterface;
use App\Contracts\Repositories\TradeRepositoryInterface;
use App\Contracts\Services\AnalyticsServiceInterface;
use App\DTOs\Analytics\AnalyticsChartData;
use App\DTOs\Analytics\CorrelationData;
use App\DTOs\Analytics\DepthHeatmapData;
use App\DTOs\Analytics\DistributionData;
use App\DTOs\Analytics\MarketRegimeData;

/**
 * Service for assembling analytics data including heatmaps and distributions.
 *
 * Orchestrates orderbook and trade repositories to build chart data,
 * depth heatmap matrices, spread histograms, and hourly statistics
 * for the analytics page.
 */
class AnalyticsService implements AnalyticsServiceInterface
{
    /**
     * Create a new analytics service instance.
     *
     * @param  \App\Contracts\Repositories\OrderbookRepositoryInterface  $orderbookRepository  Repository for orderbook data queries.
     * @param  \App\Contracts\Repositories\TradeRepositoryInterface  $tradeRepository  Repository for trade data queries.
     * @param  \App\Contracts\Repositories\FuturesRepositoryInterface  $futuresRepository  Repository for futures data queries.
     */
    public function __construct(
        private OrderbookRepositoryInterface $orderbookRepository,
        private TradeRepositoryInterface $tradeRepository,
        private FuturesRepositoryInterface $futuresRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getChartData(int $tradingPairId): ?AnalyticsChartData
    {
        $latest = $this->orderbookRepository->getLatestMetric($tradingPairId);

        if (!$latest) {
            return null;
        }

        $recentMetrics = $this->orderbookRepository->getRecentMetrics($tradingPairId)
            ->map(fn($m) => [
                'time' => $m->received_at->format('H:i:s'),
                'imbalance' => (float) $m->imbalance,
                'mid_price' => (float) $m->mid_price,
                'spread_bps' => (float) $m->spread_bps,
                'bid_volume' => (float) $m->bid_volume,
                'ask_volume' => (float) $m->ask_volume,
            ]);

        $rawAggregates = $this->tradeRepository->getRecentAggregates($tradingPairId);

        $recentAggregates = $rawAggregates->map(fn($a) => [
            'time' => $a->period_start->format('H:i'),
            'cvd' => (float) $a->cvd,
            'buy_volume' => (float) $a->buy_volume,
            'sell_volume' => (float) $a->sell_volume,
            'vwap' => (float) $a->vwap,
            'avg_trade_size' => (float) $a->avg_trade_size,
            'max_trade_size' => (float) $a->max_trade_size,
            'trade_count' => (int) $a->trade_count,
            'price_change_pct' => $a->price_change_pct !== null ? (float) $a->price_change_pct : null,
            'close_price' => $a->close_price !== null ? (float) $a->close_price : null,
        ]);

        $chartVolatility = $rawAggregates
            ->filter(fn($a) => $a->realized_vol_5m !== null)
            ->values()
            ->map(fn($a) => [
                'time' => $a->period_start->format('H:i'),
                'realized_vol' => (float) $a->realized_vol_5m,
            ]);

        // Cumulative CVD: base sum before window + running accumulation
        $chartCumulativeCvd = collect();
        $chartBuySellRatio = collect();
        if ($rawAggregates->isNotEmpty()) {
            $baseCvd = $this->tradeRepository->getCumulativeCvdBefore(
                $tradingPairId,
                $rawAggregates->first()->period_start
            );
            $runningCvd = $baseCvd;
            $chartCumulativeCvd = $rawAggregates->map(function ($a) use (&$runningCvd) {
                $runningCvd += (float) $a->cvd;
                return [
                    'time' => $a->period_start->format('H:i'),
                    'value' => round($runningCvd, 8),
                ];
            });

            $chartBuySellRatio = $rawAggregates->map(function ($a) {
                $sellVol = (float) $a->sell_volume;
                $ratio = $sellVol > 0 ? (float) $a->buy_volume / $sellVol : null;
                return [
                    'time' => $a->period_start->format('H:i'),
                    'value' => $ratio !== null ? round($ratio, 4) : null,
                ];
            });
        }

        $chartTrades = $this->tradeRepository->getRecentTradesForChart($tradingPairId)
            ->map(fn($t) => [
                'time' => $t->traded_at->format('H:i:s'),
                'price' => (float) $t->price,
                'quantity' => (float) $t->quantity,
                'is_buyer_maker' => $t->is_buyer_maker,
            ]);

        $largeTrades = $this->tradeRepository->getRecentLargeTrades($tradingPairId)
            ->map(fn($lt) => [
                'time' => $lt->traded_at->format('H:i:s'),
                'side' => $lt->is_buyer_maker ? 'SELL' : 'BUY',
                'price' => (float) $lt->price,
                'quantity' => (float) $lt->quantity,
                'size_multiple' => (float) $lt->size_multiple,
            ]);

        $orderbookWalls = $this->orderbookRepository->getRecentWalls($tradingPairId)
            ->map(fn($w) => [
                'time' => $w->detected_at->format('H:i:s'),
                'side' => $w->side,
                'price' => (float) $w->price,
                'quantity' => (float) $w->quantity,
                'size_multiple' => (float) $w->size_multiple,
                'status' => $w->status,
                'removed_at' => $w->removed_at?->format('H:i:s'),
            ]);

        // VPIN data
        $vpinRecords = \App\Models\VpinMetric::forTradingPair($tradingPairId)
            ->orderByDesc('computed_at')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        $vpinValue = $vpinRecords->isNotEmpty() ? (float) $vpinRecords->last()->vpin : null;
        $chartVpin = $vpinRecords->map(fn($v) => [
            'time' => $v->computed_at->format('H:i'),
            'value' => (float) $v->vpin,
        ]);

        return new AnalyticsChartData(
            bidVolume: (float) $latest->bid_volume,
            askVolume: (float) $latest->ask_volume,
            imbalance: (float) $latest->imbalance,
            midPrice: (float) $latest->mid_price,
            weightedMidPrice: (float) $latest->weighted_mid_price,
            spreadBps: (float) $latest->spread_bps,
            receivedAt: $latest->received_at->format('H:i:s'),
            chartMetrics: $recentMetrics,
            chartAggregates: $recentAggregates,
            chartTrades: $chartTrades,
            chartVolatility: $chartVolatility,
            largeTrades: $largeTrades,
            chartCumulativeCvd: $chartCumulativeCvd,
            chartBuySellRatio: $chartBuySellRatio,
            orderbookWalls: $orderbookWalls,
            vpinValue: $vpinValue,
            chartVpin: $chartVpin,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDepthHeatmap(int $tradingPairId): DepthHeatmapData
    {
        $snapshots = $this->orderbookRepository->getRecentSnapshots($tradingPairId);

        if ($snapshots->isEmpty()) {
            return new DepthHeatmapData([], [], [], [], null);
        }

        $allPrices = collect();
        foreach ($snapshots as $snap) {
            $bids = is_array($snap->bids) ? $snap->bids : [];
            $asks = is_array($snap->asks) ? $snap->asks : [];
            foreach ($bids as $b) { $allPrices->push((float) $b[0]); }
            foreach ($asks as $a) { $allPrices->push((float) $a[0]); }
        }

        if ($allPrices->isEmpty()) {
            return new DepthHeatmapData([], [], [], [], null);
        }

        $minPrice = $allPrices->min();
        $maxPrice = $allPrices->max();
        $range = $maxPrice - $minPrice;

        $bucketCount = 40;
        $bucketSize = $range > 0 ? $range / $bucketCount : 0.0001;
        $priceLevels = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $priceLevels[] = round($minPrice + $i * $bucketSize, 6);
        }

        $timestamps = [];
        $bidHeat = array_fill(0, $bucketCount, array_fill(0, $snapshots->count(), 0));
        $askHeat = array_fill(0, $bucketCount, array_fill(0, $snapshots->count(), 0));

        foreach ($snapshots as $colIdx => $snap) {
            $timestamps[] = $snap->received_at->format('H:i:s');
            $bids = is_array($snap->bids) ? $snap->bids : [];
            $asks = is_array($snap->asks) ? $snap->asks : [];

            foreach ($bids as $b) {
                $price = (float) $b[0];
                $vol = (float) $b[1];
                $bucketIdx = $bucketSize > 0 ? min((int) floor(($price - $minPrice) / $bucketSize), $bucketCount - 1) : 0;
                if ($bucketIdx >= 0) {
                    $bidHeat[$bucketIdx][$colIdx] += $vol;
                }
            }
            foreach ($asks as $a) {
                $price = (float) $a[0];
                $vol = (float) $a[1];
                $bucketIdx = $bucketSize > 0 ? min((int) floor(($price - $minPrice) / $bucketSize), $bucketCount - 1) : 0;
                if ($bucketIdx >= 0) {
                    $askHeat[$bucketIdx][$colIdx] += $vol;
                }
            }
        }

        $currentPrice = null;
        $latestSnap = $snapshots->last();
        if ($latestSnap) {
            $bestBid = !empty($latestSnap->bids) ? (float) $latestSnap->bids[0][0] : 0;
            $bestAsk = !empty($latestSnap->asks) ? (float) $latestSnap->asks[0][0] : 0;
            $currentPrice = ($bestBid + $bestAsk) / 2;
        }

        return new DepthHeatmapData($timestamps, $priceLevels, $bidHeat, $askHeat, $currentPrice);
    }

    /**
     * {@inheritdoc}
     */
    public function getDistributions(int $tradingPairId): DistributionData
    {
        $metrics = $this->orderbookRepository->getRecentSpreadBps($tradingPairId);

        $spreadHistogram = [];
        if ($metrics->isNotEmpty()) {
            $buckets = [
                ['0-1', 0, 1],
                ['1-2', 1, 2],
                ['2-3', 2, 3],
                ['3-5', 3, 5],
                ['5-10', 5, 10],
                ['10-20', 10, 20],
                ['20-50', 20, 50],
                ['50+', 50, PHP_FLOAT_MAX],
            ];
            foreach ($buckets as $bucket) {
                $count = $metrics->filter(fn($v) => $v >= $bucket[1] && $v < $bucket[2])->count();
                $spreadHistogram[] = ['bucket' => $bucket[0], 'count' => $count];
            }
        }

        $tradeHourly = $this->tradeRepository->getHourlyTradeStats($tradingPairId);
        $obHourly = $this->orderbookRepository->getHourlyOrderbookStats($tradingPairId);

        $hourlyStats = [];
        for ($h = 0; $h < 24; $h++) {
            $trade = $tradeHourly->get($h);
            $ob = $obHourly->get($h);
            $hourlyStats[] = [
                'hour' => $h,
                'avg_volume' => $trade ? (float) $trade->avg_volume : 0,
                'avg_trades' => $trade ? (float) $trade->avg_trades : 0,
                'avg_spread' => $ob ? (float) $ob->avg_spread : 0,
                'avg_imbalance' => $ob ? (float) $ob->avg_imbalance : 0,
            ];
        }

        return new DistributionData($spreadHistogram, $hourlyStats);
    }

    /**
     * {@inheritdoc}
     */
    public function getMarketRegime(int $tradingPairId): MarketRegimeData
    {
        $lookback = config('binance.regime_lookback', 10);
        $aggregates = $this->tradeRepository->getRecentAggregates($tradingPairId, $lookback);
        $latestMetric = $this->orderbookRepository->getLatestMetric($tradingPairId);
        $largeTrades = $this->tradeRepository->getRecentLargeTrades($tradingPairId, 20);

        if ($aggregates->isEmpty()) {
            return new MarketRegimeData('RANGING', 0.0, []);
        }

        // Signal 1: CVD magnitude (net buying/selling pressure)
        $totalCvd = $aggregates->sum('cvd');
        $cvdThreshold = config('binance.regime_cvd_threshold', 500);
        $cvdSignal = abs($totalCvd) / max($cvdThreshold, 1);

        // Signal 2: Price direction from close prices
        $closePrices = $aggregates->filter(fn($a) => $a->close_price !== null)->values();
        $priceDirection = 0.0;
        if ($closePrices->count() >= 2) {
            $first = (float) $closePrices->first()->close_price;
            $last = (float) $closePrices->last()->close_price;
            if ($first > 0) {
                $priceDirection = ($last - $first) / $first;
            }
        }

        // Signal 3: Realized volatility (average of available values)
        $volValues = $aggregates->filter(fn($a) => $a->realized_vol_5m !== null)->pluck('realized_vol_5m');
        $avgVol = $volValues->isNotEmpty() ? $volValues->avg() : 0;
        $volThreshold = config('binance.regime_volatility_threshold', 0.02);
        $volSignal = $avgVol / max($volThreshold, 0.0001);

        // Signal 4: Spread BPS (from latest metric)
        $spreadBps = $latestMetric ? (float) $latestMetric->spread_bps : 0;
        $spreadThreshold = config('binance.regime_spread_threshold', 10);
        $spreadSignal = $spreadBps / max($spreadThreshold, 1);

        // Signal 5: Large trade count (recent activity)
        $recentLargeTradeCount = $largeTrades->count();
        $ltThreshold = config('binance.regime_large_trade_threshold', 3);
        $ltSignal = $recentLargeTradeCount / max($ltThreshold, 1);

        // Classification logic
        $isHighVol = $volSignal > 1.5;
        $isHighSpread = $spreadSignal > 1.5;
        $isStrongCvd = $cvdSignal > 1.0;
        $isTrending = abs($priceDirection) > 0.001;

        $signals = [
            'cvd_total' => round($totalCvd, 4),
            'cvd_signal' => round($cvdSignal, 4),
            'price_direction' => round($priceDirection, 6),
            'avg_volatility' => round((float) $avgVol, 6),
            'volatility_signal' => round($volSignal, 4),
            'spread_bps' => round($spreadBps, 2),
            'spread_signal' => round($spreadSignal, 4),
            'large_trade_count' => $recentLargeTradeCount,
            'large_trade_signal' => round($ltSignal, 4),
        ];

        if ($isHighVol && $isHighSpread) {
            $confidence = min(($volSignal + $spreadSignal + $ltSignal) / 6, 1.0);
            return new MarketRegimeData('VOLATILE', round($confidence, 4), $signals);
        }

        if ($isTrending && $isStrongCvd) {
            $regime = ($totalCvd > 0 && $priceDirection > 0) ? 'TRENDING_UP' : 'TRENDING_DOWN';
            $confidence = min(($cvdSignal + abs($priceDirection) * 200) / 4, 1.0);
            return new MarketRegimeData($regime, round($confidence, 4), $signals);
        }

        if ($isTrending) {
            $regime = $priceDirection > 0 ? 'TRENDING_UP' : 'TRENDING_DOWN';
            $confidence = min(abs($priceDirection) * 200 / 2, 1.0);
            return new MarketRegimeData($regime, round($confidence, 4), $signals);
        }

        // Default: RANGING
        $confidence = min((1 - min($cvdSignal, 1.0) + (1 - min($volSignal, 1.0))) / 2, 1.0);
        return new MarketRegimeData('RANGING', round(max($confidence, 0), 4), $signals);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelations(int $tradingPairId): CorrelationData
    {
        $aggregates = $this->tradeRepository->getRecentAggregates($tradingPairId, 30);
        $metrics = $this->orderbookRepository->getRecentMetrics($tradingPairId, 60);
        $oiRecords = $this->futuresRepository->getRecentOpenInterest($tradingPairId, 30);
        $futuresHistory = $this->futuresRepository->getRecentFuturesHistory($tradingPairId, 60);

        // 1. OI change vs Price change
        $oiVsPrice = [];
        if ($oiRecords->count() >= 2 && $aggregates->count() >= 2) {
            $oiValues = $oiRecords->values();
            for ($i = 1; $i < $oiValues->count(); $i++) {
                $prevOi = (float) $oiValues[$i - 1]->open_interest;
                $currOi = (float) $oiValues[$i]->open_interest;
                $oiChange = $prevOi > 0 ? (($currOi - $prevOi) / $prevOi) * 100 : 0;

                // Find closest aggregate for price change
                $aggIdx = min($i - 1, $aggregates->count() - 2);
                if ($aggIdx >= 0 && isset($aggregates[$aggIdx]) && isset($aggregates[$aggIdx + 1])) {
                    $pricePrev = (float) ($aggregates[$aggIdx]->close_price ?? $aggregates[$aggIdx]->vwap);
                    $priceCurr = (float) ($aggregates[$aggIdx + 1]->close_price ?? $aggregates[$aggIdx + 1]->vwap);
                    $priceChange = $pricePrev > 0 ? (($priceCurr - $pricePrev) / $pricePrev) * 100 : 0;
                    $oiVsPrice[] = ['x' => round($oiChange, 4), 'y' => round($priceChange, 4)];
                }
            }
        }

        // 2. Funding rate vs Premium
        $fundingVsPremium = [];
        if ($futuresHistory->count() >= 2) {
            foreach ($futuresHistory as $fh) {
                $fundingRate = (float) $fh->funding_rate;
                $markPrice = (float) $fh->mark_price;
                $indexPrice = (float) ($fh->index_price ?? $markPrice);
                $premium = $indexPrice > 0 ? (($markPrice - $indexPrice) / $indexPrice) * 100 : 0;
                $fundingVsPremium[] = ['x' => round($fundingRate * 100, 6), 'y' => round($premium, 4)];
            }
        }

        // 3. Volume vs Volatility
        $volumeVsVolatility = [];
        foreach ($aggregates as $a) {
            if ($a->realized_vol_5m !== null) {
                $totalVol = (float) $a->buy_volume + (float) $a->sell_volume;
                $volumeVsVolatility[] = ['x' => round($totalVol, 2), 'y' => round((float) $a->realized_vol_5m, 6)];
            }
        }

        // 4. Imbalance vs Next-period price change
        $imbalanceVsPrice = [];
        if ($metrics->count() >= 2 && $aggregates->count() >= 2) {
            $aggArr = $aggregates->values();
            for ($i = 0; $i < $aggArr->count() - 1; $i++) {
                // Find closest metric imbalance for this aggregate period
                $aggTime = $aggArr[$i]->period_start;
                $closestMetric = $metrics->sortBy(fn($m) => abs($m->received_at->diffInSeconds($aggTime)))->first();
                if ($closestMetric) {
                    $priceCurr = (float) ($aggArr[$i]->close_price ?? $aggArr[$i]->vwap);
                    $priceNext = (float) ($aggArr[$i + 1]->close_price ?? $aggArr[$i + 1]->vwap);
                    $priceChange = $priceCurr > 0 ? (($priceNext - $priceCurr) / $priceCurr) * 100 : 0;
                    $imbalanceVsPrice[] = ['x' => round((float) $closestMetric->imbalance, 4), 'y' => round($priceChange, 4)];
                }
            }
        }

        return new CorrelationData(
            oiVsPrice: $oiVsPrice,
            fundingVsPremium: $fundingVsPremium,
            volumeVsVolatility: $volumeVsVolatility,
            imbalanceVsPrice: $imbalanceVsPrice,
        );
    }
}
