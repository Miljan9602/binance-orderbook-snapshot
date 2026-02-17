<?php

namespace App\DTOs\Dashboard;

use Illuminate\Support\Collection;

/**
 * Immutable data transfer object for dashboard summary JSON response.
 *
 * Contains price, ticker statistics, and sparkline data for the
 * primary trading pair displayed on the dashboard hero section.
 *
 * @property-read float|null $lastPrice  Last traded price or best bid fallback.
 * @property-read float|null $priceChange  24h absolute price change.
 * @property-read float|null $priceChangePercent  24h percentage price change.
 * @property-read float|null $bestBid  Best bid price from the latest snapshot.
 * @property-read float|null $bestAsk  Best ask price from the latest snapshot.
 * @property-read float|null $spread  Absolute spread (ask - bid).
 * @property-read float|null $spreadPct  Spread as a percentage of bid price.
 * @property-read float|null $highPrice  24h high price.
 * @property-read float|null $lowPrice  24h low price.
 * @property-read float|null $volume  24h traded volume.
 * @property-read int|null $tradeCount  24h trade count.
 * @property-read string|null $lastUpdateAt  Human-readable last update time (e.g. "2 seconds ago").
 * @property-read int|null $updateId  Binance last update ID from the snapshot.
 * @property-read string|null $receivedAt  Snapshot received timestamp (H:i:s format).
 * @property-read \Illuminate\Support\Collection<int, float> $sparkline  Recent mid-price values for sparkline chart.
 */
readonly class DashboardSummaryData
{
    public function __construct(
        public ?float $lastPrice,
        public ?float $priceChange,
        public ?float $priceChangePercent,
        public ?float $bestBid,
        public ?float $bestAsk,
        public ?float $spread,
        public ?float $spreadPct,
        public ?float $highPrice,
        public ?float $lowPrice,
        public ?float $volume,
        public ?int $tradeCount,
        public ?string $lastUpdateAt,
        public ?int $updateId,
        public ?string $receivedAt,
        public Collection $sparkline,
        public ?float $quoteVolume = null,
        public ?float $weightedAvgPrice = null,
        public ?float $openPrice = null,
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'last_price' => $this->lastPrice,
            'price_change' => $this->priceChange,
            'price_change_percent' => $this->priceChangePercent,
            'best_bid' => $this->bestBid,
            'best_ask' => $this->bestAsk,
            'spread' => $this->spread,
            'spread_pct' => $this->spreadPct,
            'high_price' => $this->highPrice,
            'low_price' => $this->lowPrice,
            'volume' => $this->volume,
            'trade_count' => $this->tradeCount,
            'last_update_at' => $this->lastUpdateAt,
            'update_id' => $this->updateId,
            'received_at' => $this->receivedAt,
            'sparkline' => $this->sparkline,
            'quote_volume' => $this->quoteVolume,
            'weighted_avg_price' => $this->weightedAvgPrice,
            'open_price' => $this->openPrice,
        ];
    }
}
