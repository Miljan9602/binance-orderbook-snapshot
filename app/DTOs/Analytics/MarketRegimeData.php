<?php

namespace App\DTOs\Analytics;

/**
 * Immutable data transfer object for market regime classification.
 *
 * Classifies the current market state based on CVD magnitude, volatility,
 * spread, large trade activity, and price direction signals.
 *
 * @property-read string $regime  Market regime: TRENDING_UP, TRENDING_DOWN, RANGING, or VOLATILE.
 * @property-read float $confidence  Classification confidence score (0.0 to 1.0).
 * @property-read array $signals  Individual signal values used for classification.
 */
readonly class MarketRegimeData
{
    public function __construct(
        public string $regime,
        public float $confidence,
        public array $signals,
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'regime' => $this->regime,
            'confidence' => $this->confidence,
            'signals' => $this->signals,
        ];
    }
}
