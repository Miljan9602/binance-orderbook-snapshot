<?php

namespace App\Contracts\Services;

use App\DTOs\Orderbook\OrderbookViewData;
use App\Models\TradingPair;

/**
 * Contract for assembling orderbook view data.
 *
 * Provides methods for building the complete orderbook detail view
 * including snapshot, ticker, and recent trades.
 */
interface OrderbookQueryServiceInterface
{
    /**
     * Get complete orderbook view data for a trading pair.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \App\DTOs\Orderbook\OrderbookViewData
     */
    public function getOrderbookViewData(TradingPair $tradingPair): OrderbookViewData;
}
