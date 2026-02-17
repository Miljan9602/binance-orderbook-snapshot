<?php

namespace App\Services\Web;

use App\Contracts\Repositories\TradeRepositoryInterface;
use App\Contracts\Services\OrderbookQueryServiceInterface;
use App\DTOs\Orderbook\OrderbookViewData;
use App\Models\TradingPair;

/**
 * Service for assembling orderbook view data.
 *
 * Combines the trading pair's snapshot and ticker with recent trades
 * into a structured DTO for the live orderbook page.
 */
class OrderbookQueryService implements OrderbookQueryServiceInterface
{
    /**
     * Create a new orderbook query service instance.
     *
     * @param  \App\Contracts\Repositories\TradeRepositoryInterface  $tradeRepository  Repository for trade data queries.
     */
    public function __construct(
        private TradeRepositoryInterface $tradeRepository,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getOrderbookViewData(TradingPair $tradingPair): OrderbookViewData
    {
        $tradingPair->load('snapshot');
        $snapshot = $tradingPair->snapshot;
        $ticker = $tradingPair->ticker;

        $recentTrades = $this->tradeRepository->getRecentTrades($tradingPair->id);

        return new OrderbookViewData(
            snapshot: $snapshot ? [
                'bids' => $snapshot->bids,
                'asks' => $snapshot->asks,
                'best_bid_price' => (float) $snapshot->best_bid_price,
                'best_ask_price' => (float) $snapshot->best_ask_price,
                'spread' => (float) $snapshot->spread,
                'last_update_id' => $snapshot->last_update_id,
                'received_at' => $snapshot->received_at->format('H:i:s'),
            ] : null,
            ticker: $ticker ? [
                'last_price' => (float) $ticker->last_price,
                'price_change_percent' => (float) $ticker->price_change_percent,
                'high_price' => (float) $ticker->high_price,
                'low_price' => (float) $ticker->low_price,
                'volume' => (float) $ticker->volume,
            ] : null,
            trades: $recentTrades,
        );
    }
}
