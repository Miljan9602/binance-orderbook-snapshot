<?php

namespace App\DTOs\Analytics;

/**
 * Immutable data transfer object for distribution analysis JSON response.
 *
 * Contains spread histogram bucketed data and hourly trading statistics
 * used by the analytics page distribution charts.
 *
 * @property-read array<int, array{bucket: string, count: int}> $spreadHistogram  Spread distribution histogram with labeled buckets.
 * @property-read array<int, array{hour: int, avg_volume: float, avg_trades: float, avg_spread: float, avg_imbalance: float}> $hourlyStats  Hourly statistics for all 24 hours.
 */
readonly class DistributionData
{
    public function __construct(
        public array $spreadHistogram,
        public array $hourlyStats,
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'spread_histogram' => $this->spreadHistogram,
            'hourly_stats' => $this->hourlyStats,
        ];
    }
}
