<?php

namespace App\DTOs\Analytics;

/**
 * Immutable data transfer object for cross-metric correlation scatter plots.
 *
 * Contains 4 scatter datasets: OI change vs price change, funding vs premium,
 * volume vs volatility, and imbalance vs next-period price change.
 *
 * @property-read array $oiVsPrice  OI change vs price change scatter points.
 * @property-read array $fundingVsPremium  Funding rate vs premium scatter points.
 * @property-read array $volumeVsVolatility  Volume vs volatility scatter points.
 * @property-read array $imbalanceVsPrice  Imbalance vs next-period price change scatter points.
 */
readonly class CorrelationData
{
    public function __construct(
        public array $oiVsPrice = [],
        public array $fundingVsPremium = [],
        public array $volumeVsVolatility = [],
        public array $imbalanceVsPrice = [],
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, array>
     */
    public function toArray(): array
    {
        return [
            'oi_vs_price' => $this->oiVsPrice,
            'funding_vs_premium' => $this->fundingVsPremium,
            'volume_vs_volatility' => $this->volumeVsVolatility,
            'imbalance_vs_price' => $this->imbalanceVsPrice,
        ];
    }
}
