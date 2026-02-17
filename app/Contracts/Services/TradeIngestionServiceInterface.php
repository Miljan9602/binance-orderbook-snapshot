<?php

namespace App\Contracts\Services;

/**
 * Contract for ingesting trade data from WebSocket.
 *
 * Handles persistence of aggregated trade records and delegates
 * to the large trade detector for threshold-based alerting.
 */
interface TradeIngestionServiceInterface
{
    /**
     * Save a new trade for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to record the trade for.
     * @param  array<string, mixed>  $data  Raw aggregated trade data from WebSocket stream.
     * @return void
     */
    public function saveTrade(int $tradingPairId, array $data): void;
}
