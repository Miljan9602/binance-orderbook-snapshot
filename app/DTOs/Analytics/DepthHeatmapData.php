<?php

namespace App\DTOs\Analytics;

/**
 * Immutable data transfer object for depth heatmap JSON response.
 *
 * Contains the heatmap matrix data for rendering a 2D depth heatmap
 * chart showing bid/ask volume concentration across price levels over time.
 *
 * @property-read array<int, string> $timestamps  Column labels (H:i:s format) for each snapshot.
 * @property-read array<int, float> $priceLevels  Row labels representing bucketed price levels.
 * @property-read array<int, array<int, float>> $bidHeat  2D matrix of bid volumes indexed by [priceLevel][timeCol].
 * @property-read array<int, array<int, float>> $askHeat  2D matrix of ask volumes indexed by [priceLevel][timeCol].
 * @property-read float|null $currentPrice  Current mid price for reference line overlay.
 */
readonly class DepthHeatmapData
{
    public function __construct(
        public array $timestamps,
        public array $priceLevels,
        public array $bidHeat,
        public array $askHeat,
        public ?float $currentPrice,
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timestamps' => $this->timestamps,
            'price_levels' => $this->priceLevels,
            'bid_heat' => $this->bidHeat,
            'ask_heat' => $this->askHeat,
            'current_price' => $this->currentPrice,
        ];
    }
}
