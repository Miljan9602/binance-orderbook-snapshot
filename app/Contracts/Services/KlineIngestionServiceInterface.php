<?php

namespace App\Contracts\Services;

/**
 * Contract for ingesting kline (candlestick) data from WebSocket.
 *
 * Handles upsert of kline records across all tracked intervals
 * (1m, 5m, 15m, 1h).
 */
interface KlineIngestionServiceInterface
{
    /**
     * Update a kline (candlestick) for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to update.
     * @param  array<string, mixed>  $data  Raw kline data from WebSocket stream.
     * @return void
     */
    public function updateKline(int $tradingPairId, array $data): void;
}
