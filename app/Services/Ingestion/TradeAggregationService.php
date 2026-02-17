<?php

namespace App\Services\Ingestion;

use App\Contracts\Services\TradeAggregationServiceInterface;
use App\Models\Kline;
use App\Models\Trade;
use App\Models\TradeAggregate;
use App\Models\TradingPair;

/**
 * Service for computing trade aggregates (VWAP, CVD, volatility).
 */
class TradeAggregationService implements TradeAggregationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function computeTradeAggregates(): int
    {
        $periodStart = now()->startOfMinute()->subMinute();
        $periodEnd = (clone $periodStart)->addMinute();

        $pairs = TradingPair::active()->get();
        $created = 0;

        foreach ($pairs as $pair) {
            $trades = Trade::forTradingPair($pair->id)
                ->where('traded_at', '>=', $periodStart)
                ->where('traded_at', '<', $periodEnd)
                ->get();

            if ($trades->isEmpty()) {
                continue;
            }

            $totalValue = 0;
            $totalQty = 0;
            $buyVolume = 0;
            $sellVolume = 0;
            $maxTradeSize = 0;

            foreach ($trades as $trade) {
                $price = (float) $trade->price;
                $qty = (float) $trade->quantity;
                $totalValue += $price * $qty;
                $totalQty += $qty;

                if ($trade->is_buyer_maker) {
                    $sellVolume += $qty;
                } else {
                    $buyVolume += $qty;
                }

                if ($qty > $maxTradeSize) {
                    $maxTradeSize = $qty;
                }
            }

            $vwap = $totalQty > 0 ? $totalValue / $totalQty : 0;
            $tradeCount = $trades->count();

            $latestKline = Kline::forTradingPair($pair->id)
                ->where('interval', '1m')
                ->where('open_time', '<=', $periodStart)
                ->orderByDesc('open_time')
                ->first();

            $closePrice = $latestKline ? (float) $latestKline->close : $vwap;

            $prevAggregate = TradeAggregate::forTradingPair($pair->id)
                ->where('interval', '1m')
                ->where('period_start', '<', $periodStart)
                ->orderByDesc('period_start')
                ->first();

            $priceChangePct = null;
            if ($prevAggregate && $prevAggregate->close_price && (float) $prevAggregate->close_price > 0) {
                $priceChangePct = (($closePrice - (float) $prevAggregate->close_price) / (float) $prevAggregate->close_price) * 100;
            }

            $realizedVol = null;
            $recentAggs = TradeAggregate::forTradingPair($pair->id)
                ->where('interval', '1m')
                ->where('period_start', '<', $periodStart)
                ->whereNotNull('price_change_pct')
                ->orderByDesc('period_start')
                ->limit(4)
                ->pluck('price_change_pct')
                ->map(fn($v) => (float) $v)
                ->toArray();

            if ($priceChangePct !== null) {
                $returns = array_merge([$priceChangePct], $recentAggs);
                if (count($returns) >= 3) {
                    $mean = array_sum($returns) / count($returns);
                    $sumSqDiff = 0;
                    foreach ($returns as $r) {
                        $sumSqDiff += ($r - $mean) ** 2;
                    }
                    $realizedVol = sqrt($sumSqDiff / (count($returns) - 1));
                }
            }

            TradeAggregate::updateOrCreate(
                [
                    'trading_pair_id' => $pair->id,
                    'interval' => '1m',
                    'period_start' => $periodStart,
                ],
                [
                    'vwap' => $vwap,
                    'buy_volume' => $buyVolume,
                    'sell_volume' => $sellVolume,
                    'cvd' => $buyVolume - $sellVolume,
                    'trade_count' => $tradeCount,
                    'avg_trade_size' => $tradeCount > 0 ? $totalQty / $tradeCount : 0,
                    'max_trade_size' => $maxTradeSize,
                    'close_price' => $closePrice,
                    'price_change_pct' => $priceChangePct,
                    'realized_vol_5m' => $realizedVol,
                ]
            );

            $created++;
        }

        return $created;
    }
}
