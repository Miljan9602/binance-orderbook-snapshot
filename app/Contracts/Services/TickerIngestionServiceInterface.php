<?php

namespace App\Contracts\Services;

/**
 * Contract for ingesting ticker data from WebSocket.
 *
 * Handles upsert of 24-hour rolling ticker statistics
 * for each trading pair.
 */
interface TickerIngestionServiceInterface
{
    /**
     * Update the ticker for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to update.
     * @param  array<string, mixed>  $data  Raw ticker data from WebSocket stream.
     * @return void
     */
    public function updateTicker(int $tradingPairId, array $data): void;
}
