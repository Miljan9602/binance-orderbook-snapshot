<?php

namespace App\DTOs\Futures;

use Illuminate\Support\Collection;

/**
 * Immutable data transfer object for futures chart JSON response.
 *
 * Contains live futures metric cards and chart series data for the
 * futures page's real-time AJAX polling endpoint.
 *
 * @property-read array{mark_price: float, index_price: float, funding_rate: float, next_funding_time: string|null, received_at: string}|null $futures  Latest futures metric data, or null if unavailable.
 * @property-read float|null $spotPrice  Current spot price from the ticker for premium calculation.
 * @property-read float|null $openInterest  Latest open interest value.
 * @property-read \Illuminate\Support\Collection $liquidations  Recent liquidation events for the live feed.
 * @property-read \Illuminate\Support\Collection $chartFunding  Time series of funding rate values.
 * @property-read \Illuminate\Support\Collection $chartOi  Time series of open interest values.
 * @property-read \Illuminate\Support\Collection $chartPremium  Time series of spot-futures premium percentages.
 * @property-read \Illuminate\Support\Collection $chartLiquidations  Minute-grouped liquidation volumes (buy/sell).
 */
readonly class FuturesChartData
{
    public function __construct(
        public ?array $futures,
        public ?float $spotPrice,
        public ?float $openInterest,
        public Collection $liquidations,
        public Collection $chartFunding,
        public Collection $chartOi,
        public Collection $chartPremium,
        public Collection $chartLiquidations,
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'futures' => $this->futures,
            'spot_price' => $this->spotPrice,
            'open_interest' => $this->openInterest,
            'liquidations' => $this->liquidations,
            'chart_funding' => $this->chartFunding,
            'chart_oi' => $this->chartOi,
            'chart_premium' => $this->chartPremium,
            'chart_liquidations' => $this->chartLiquidations,
        ];
    }
}
