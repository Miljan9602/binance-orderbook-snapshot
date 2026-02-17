<?php

namespace App\Contracts\Services;

/**
 * Contract for assembling kline chart data.
 *
 * Provides methods for retrieving klines formatted as OHLCV arrays
 * suitable for TradingView Lightweight Charts rendering, optionally
 * including computed technical indicators.
 */
interface KlineQueryServiceInterface
{
    /**
     * Get klines formatted for chart display, with optional indicators.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  string  $interval  The kline interval (e.g. '1m', '5m', '15m', '1h').
     * @param  array<string>  $indicators  Technical indicators to compute (rsi, bb, ema, macd, taker).
     * @return array{klines: array, indicators: array}
     */
    public function getChartKlines(int $tradingPairId, string $interval = '1m', array $indicators = []): array;
}
