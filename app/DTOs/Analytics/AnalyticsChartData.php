<?php

namespace App\DTOs\Analytics;

use Illuminate\Support\Collection;

/**
 * Immutable data transfer object for analytics chart JSON response.
 *
 * Contains live metric card values and chart series data for the
 * analytics page's real-time AJAX polling endpoint.
 *
 * @property-read float $bidVolume  Total bid-side volume from latest metric.
 * @property-read float $askVolume  Total ask-side volume from latest metric.
 * @property-read float $imbalance  Orderbook imbalance ratio from latest metric.
 * @property-read float $midPrice  Mid price from latest metric.
 * @property-read float $weightedMidPrice  Volume-weighted mid price from latest metric.
 * @property-read float $spreadBps  Spread in basis points from latest metric.
 * @property-read string $receivedAt  Timestamp of the latest metric (H:i:s format).
 * @property-read \Illuminate\Support\Collection $chartMetrics  Time series of orderbook metrics for charts.
 * @property-read \Illuminate\Support\Collection $chartAggregates  Time series of trade aggregates for charts.
 * @property-read \Illuminate\Support\Collection $chartTrades  Time series of recent trades for scatter plot.
 * @property-read \Illuminate\Support\Collection $chartVolatility  Time series of realized volatility values.
 * @property-read \Illuminate\Support\Collection $largeTrades  Recent large trades for the live feed.
 * @property-read \Illuminate\Support\Collection $chartCumulativeCvd  Running cumulative CVD time series.
 * @property-read \Illuminate\Support\Collection $chartBuySellRatio  Buy/sell volume ratio time series.
 * @property-read \Illuminate\Support\Collection $orderbookWalls  Recent detected orderbook walls.
 * @property-read ?float $vpinValue  Latest VPIN value (null if not computed yet).
 * @property-read \Illuminate\Support\Collection $chartVpin  VPIN time series.
 */
readonly class AnalyticsChartData
{
    public function __construct(
        public float $bidVolume,
        public float $askVolume,
        public float $imbalance,
        public float $midPrice,
        public float $weightedMidPrice,
        public float $spreadBps,
        public string $receivedAt,
        public Collection $chartMetrics,
        public Collection $chartAggregates,
        public Collection $chartTrades,
        public Collection $chartVolatility,
        public Collection $largeTrades,
        public Collection $chartCumulativeCvd = new Collection(),
        public Collection $chartBuySellRatio = new Collection(),
        public Collection $orderbookWalls = new Collection(),
        public ?float $vpinValue = null,
        public Collection $chartVpin = new Collection(),
    ) {}

    /**
     * Convert to array for JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'bid_volume' => $this->bidVolume,
            'ask_volume' => $this->askVolume,
            'imbalance' => $this->imbalance,
            'mid_price' => $this->midPrice,
            'weighted_mid_price' => $this->weightedMidPrice,
            'spread_bps' => $this->spreadBps,
            'received_at' => $this->receivedAt,
            'chart_metrics' => $this->chartMetrics,
            'chart_aggregates' => $this->chartAggregates,
            'chart_trades' => $this->chartTrades,
            'chart_volatility' => $this->chartVolatility,
            'large_trades' => $this->largeTrades,
            'chart_cumulative_cvd' => $this->chartCumulativeCvd,
            'chart_buy_sell_ratio' => $this->chartBuySellRatio,
            'orderbook_walls' => $this->orderbookWalls,
            'vpin_value' => $this->vpinValue,
            'chart_vpin' => $this->chartVpin,
        ];
    }
}
