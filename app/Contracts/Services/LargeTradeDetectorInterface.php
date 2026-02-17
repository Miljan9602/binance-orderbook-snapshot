<?php

namespace App\Contracts\Services;

use App\Models\Trade;

/**
 * Contract for detecting large trades using a rolling window.
 *
 * Evaluates each incoming trade against a configurable multiplier
 * of the rolling average trade size. Trades exceeding the threshold
 * are persisted as large trade records.
 */
interface LargeTradeDetectorInterface
{
    /**
     * Evaluate whether a trade qualifies as a large trade.
     *
     * @param  int  $tradingPairId  The trading pair the trade belongs to.
     * @param  \App\Models\Trade  $trade  The trade to evaluate against the rolling average.
     * @return void
     */
    public function evaluate(int $tradingPairId, Trade $trade): void;
}
