<?php

namespace App\Contracts\Services;

/**
 * Contract for ingesting futures data from WebSocket and REST API.
 *
 * Handles persistence of mark price updates, liquidation events,
 * and open interest polling from the Binance Futures API.
 */
interface FuturesIngestionServiceInterface
{
    /**
     * Update the mark price for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to update.
     * @param  array<string, mixed>  $data  Raw mark price data from WebSocket stream.
     * @return void
     */
    public function updateMarkPrice(int $tradingPairId, array $data): void;

    /**
     * Save a liquidation event for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to record the liquidation for.
     * @param  array<string, mixed>  $data  Raw liquidation data from WebSocket stream.
     * @return void
     */
    public function saveLiquidation(int $tradingPairId, array $data): void;

    /**
     * Fetch and save open interest from the REST API.
     *
     * @return int  Number of pairs for which OI was fetched.
     */
    public function fetchAndSaveOpenInterest(): int;
}
