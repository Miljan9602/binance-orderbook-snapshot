<?php

namespace App\Services\Web;

use App\Contracts\Repositories\OrderbookRepositoryInterface;
use App\Contracts\Repositories\TradingPairRepositoryInterface;
use App\Contracts\Services\DashboardServiceInterface;
use App\DTOs\Dashboard\DashboardSummaryData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for assembling dashboard data.
 *
 * Orchestrates trading pair and orderbook repositories to build
 * the dashboard view data including price, ticker stats, and sparkline.
 */
class DashboardService implements DashboardServiceInterface
{
    /**
     * Create a new dashboard service instance.
     *
     * @param  \App\Contracts\Repositories\TradingPairRepositoryInterface  $tradingPairRepository  Repository for trading pair queries.
     * @param  \App\Contracts\Repositories\OrderbookRepositoryInterface  $orderbookRepository  Repository for orderbook data queries.
     */
    public function __construct(
        private TradingPairRepositoryInterface $tradingPairRepository,
        private OrderbookRepositoryInterface $orderbookRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getAllPairs(): Collection
    {
        return $this->tradingPairRepository->getAllWithSnapshotAndTicker();
    }

    /**
     * {@inheritdoc}
     */
    public function getSummaryData(): ?DashboardSummaryData
    {
        $pair = $this->tradingPairRepository->getFirstWithSnapshotAndTicker();

        if (!$pair) {
            return null;
        }

        $snapshot = $pair->snapshot;
        $ticker = $pair->ticker;

        $sparkline = $this->orderbookRepository->getSparklineData($pair->id);

        return new DashboardSummaryData(
            lastPrice: $ticker ? (float) $ticker->last_price : ($snapshot ? (float) $snapshot->best_bid_price : null),
            priceChange: $ticker ? (float) $ticker->price_change : null,
            priceChangePercent: $ticker ? (float) $ticker->price_change_percent : null,
            bestBid: $snapshot ? (float) $snapshot->best_bid_price : null,
            bestAsk: $snapshot ? (float) $snapshot->best_ask_price : null,
            spread: $snapshot ? (float) $snapshot->spread : null,
            spreadPct: $snapshot && (float) $snapshot->best_bid_price > 0
                ? ((float) $snapshot->spread / (float) $snapshot->best_bid_price) * 100 : null,
            highPrice: $ticker ? (float) $ticker->high_price : null,
            lowPrice: $ticker ? (float) $ticker->low_price : null,
            volume: $ticker ? (float) $ticker->volume : null,
            tradeCount: $ticker ? $ticker->trade_count : null,
            lastUpdateAt: $pair->last_update_at?->diffForHumans(),
            updateId: $snapshot?->last_update_id,
            receivedAt: $snapshot?->received_at?->format('H:i:s'),
            sparkline: $sparkline,
            quoteVolume: $ticker ? (float) $ticker->quote_volume : null,
            weightedAvgPrice: $ticker ? (float) $ticker->weighted_avg_price : null,
            openPrice: $ticker ? (float) $ticker->open_price : null,
        );
    }
}
