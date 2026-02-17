<?php

namespace App\Contracts\Services;

/**
 * Contract for computing trade aggregates.
 *
 * Processes raw trades for the last completed minute into 1-minute
 * rollups including VWAP, CVD, buy/sell volume, and realized volatility.
 */
interface TradeAggregationServiceInterface
{
    /**
     * Compute trade aggregates for the last completed minute.
     *
     * @return int  Number of aggregates created.
     */
    public function computeTradeAggregates(): int;
}
