<?php

namespace App\DTOs\Orderbook;

use Illuminate\Database\Eloquent\Collection;

/**
 * Immutable data transfer object for orderbook detail JSON response.
 *
 * Contains the current orderbook snapshot, ticker statistics, and
 * recent trades for the live orderbook page's AJAX polling endpoint.
 *
 * @property-read array{bids: array, asks: array, best_bid_price: float, best_ask_price: float, spread: float, last_update_id: int, received_at: string}|null $snapshot  Formatted snapshot data, or null if unavailable.
 * @property-read array{last_price: float, price_change_percent: float, high_price: float, low_price: float, volume: float}|null $ticker  Formatted ticker data, or null if unavailable.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trade> $trades  Recent trades ordered by most recent first.
 */
readonly class OrderbookViewData
{
    public function __construct(
        public ?array $snapshot,
        public ?array $ticker,
        public Collection $trades,
    ) {}
}
