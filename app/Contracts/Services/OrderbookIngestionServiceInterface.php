<?php

namespace App\Contracts\Services;

/**
 * Contract for ingesting orderbook data from WebSocket.
 *
 * Handles snapshot upsert, history append, and sampled orderbook
 * metric computation (imbalance, VWAP mid, spread in basis points).
 */
interface OrderbookIngestionServiceInterface
{
    /**
     * Update the orderbook for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to update.
     * @param  array<string, mixed>  $data  Raw orderbook depth data from WebSocket stream.
     * @return void
     */
    public function updateOrderbook(int $tradingPairId, array $data): void;
}
