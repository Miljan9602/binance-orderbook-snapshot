<?php

namespace App\DTOs\Filters;

/**
 * Immutable filter DTO for kline queries.
 *
 * Combines a candlestick interval selector with an optional date range
 * for filtering kline records on the klines page.
 *
 * @property-read string $interval  The kline interval (e.g. '1m', '5m', '15m', '1h').
 * @property-read string|null $from  Start date filter (inclusive).
 * @property-read string|null $to  End date filter (inclusive).
 */
readonly class KlineFilter
{
    public function __construct(
        public string $interval = '1m',
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
