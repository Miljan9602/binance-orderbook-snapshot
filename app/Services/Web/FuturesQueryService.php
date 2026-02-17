<?php

namespace App\Services\Web;

use App\Contracts\Repositories\FuturesRepositoryInterface;
use App\Contracts\Services\FuturesQueryServiceInterface;
use App\DTOs\Futures\FuturesChartData;
use App\Models\TradingPair;

/**
 * Service for assembling futures page data.
 *
 * Orchestrates the futures repository to build chart data including
 * funding rate, open interest, spot-futures premium, and liquidation
 * volume charts for the futures page.
 */
class FuturesQueryService implements FuturesQueryServiceInterface
{
    /**
     * Create a new futures query service instance.
     *
     * @param  \App\Contracts\Repositories\FuturesRepositoryInterface  $futuresRepository  Repository for futures data queries.
     */
    public function __construct(
        private FuturesRepositoryInterface $futuresRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getChartData(TradingPair $tradingPair): FuturesChartData
    {
        $futuresMetric = $tradingPair->futuresMetric;
        $ticker = $tradingPair->ticker;
        $latestOi = $this->futuresRepository->getLatestOpenInterest($tradingPair->id);
        $spotPrice = $ticker ? (float) $ticker->last_price : null;

        $liquidations = $this->futuresRepository->getRecentLiquidations($tradingPair->id)
            ->map(fn($l) => [
                'side' => $l->side,
                'order_type' => $l->order_type,
                'price' => (float) $l->price,
                'quantity' => (float) $l->quantity,
                'avg_price' => (float) $l->avg_price,
                'notional' => round((float) $l->quantity * (float) $l->avg_price, 4),
                'order_status' => $l->order_status,
                'time' => $l->order_time->format('H:i:s'),
            ]);

        $recentFuturesHistory = $this->futuresRepository->getRecentFuturesHistory($tradingPair->id);

        $chartFunding = $recentFuturesHistory->map(fn($r) => [
            'time' => $r->received_at->format('H:i:s'),
            'funding_rate' => (float) $r->funding_rate,
            'index_price' => (float) $r->index_price,
            'mark_price' => (float) $r->mark_price,
            'annualized_funding' => (float) $r->funding_rate * 3 * 365 * 100,
        ]);

        $rawOi = $this->futuresRepository->getRecentOpenInterest($tradingPair->id);
        $previousOi = null;
        $chartOi = $rawOi->map(function ($r) use (&$previousOi) {
            $currentOi = (float) $r->open_interest;
            $oiDelta = $previousOi !== null ? $currentOi - $previousOi : 0;
            $previousOi = $currentOi;
            return [
                'time' => $r->timestamp->format('H:i:s'),
                'open_interest' => $currentOi,
                'oi_delta' => round($oiDelta, 4),
            ];
        });

        $chartPremium = $recentFuturesHistory->map(function ($r) use ($spotPrice) {
            $mark = (float) $r->mark_price;
            $premium = ($spotPrice && $spotPrice > 0) ? (($mark - $spotPrice) / $spotPrice) * 100 : 0;
            return [
                'time' => $r->received_at->format('H:i:s'),
                'premium' => round($premium, 6),
            ];
        });

        $liqCutoff = now()->subMinutes(30);
        $chartLiquidations = $this->futuresRepository->getLiquidationsSince($tradingPair->id, $liqCutoff)
            ->groupBy(fn($l) => $l->order_time->format('H:i'))
            ->map(function ($group, $minute) {
                $buyQty = $group->where('side', 'BUY')->sum(fn($l) => (float) $l->quantity);
                $sellQty = $group->where('side', 'SELL')->sum(fn($l) => (float) $l->quantity);
                $buyNotional = $group->where('side', 'BUY')->sum(fn($l) => (float) $l->quantity * (float) $l->avg_price);
                $sellNotional = $group->where('side', 'SELL')->sum(fn($l) => (float) $l->quantity * (float) $l->avg_price);
                return [
                    'time' => $minute,
                    'buy_qty' => round($buyQty, 4),
                    'sell_qty' => round($sellQty, 4),
                    'buy_notional' => round($buyNotional, 4),
                    'sell_notional' => round($sellNotional, 4),
                ];
            })
            ->values();

        return new FuturesChartData(
            futures: $futuresMetric ? [
                'mark_price' => (float) $futuresMetric->mark_price,
                'index_price' => (float) $futuresMetric->index_price,
                'funding_rate' => (float) $futuresMetric->funding_rate,
                'next_funding_time' => $futuresMetric->next_funding_time?->toIso8601String(),
                'received_at' => $futuresMetric->received_at->format('H:i:s'),
            ] : null,
            spotPrice: $spotPrice,
            openInterest: $latestOi ? (float) $latestOi->open_interest : null,
            liquidations: $liquidations,
            chartFunding: $chartFunding,
            chartOi: $chartOi,
            chartPremium: $chartPremium,
            chartLiquidations: $chartLiquidations,
        );
    }
}
