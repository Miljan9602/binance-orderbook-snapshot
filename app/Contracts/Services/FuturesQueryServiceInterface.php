<?php

namespace App\Contracts\Services;

use App\DTOs\Futures\FuturesChartData;

/**
 * Contract for assembling futures page chart data.
 *
 * Provides methods for building the futures chart data including
 * funding rate, open interest, spot-futures premium, and liquidation charts.
 */
interface FuturesQueryServiceInterface
{
    /**
     * Get chart data for the futures page.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \App\DTOs\Futures\FuturesChartData
     */
    public function getChartData(\App\Models\TradingPair $tradingPair): FuturesChartData;
}
