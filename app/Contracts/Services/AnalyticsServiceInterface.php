<?php

namespace App\Contracts\Services;

use App\DTOs\Analytics\AnalyticsChartData;
use App\DTOs\Analytics\CorrelationData;
use App\DTOs\Analytics\DepthHeatmapData;
use App\DTOs\Analytics\DistributionData;
use App\DTOs\Analytics\MarketRegimeData;

/**
 * Contract for assembling analytics data for the analytics page.
 *
 * Provides chart data, depth heatmap matrices, and distribution
 * analysis (spread histogram + hourly statistics).
 */
interface AnalyticsServiceInterface
{
    /**
     * Get chart data for the analytics page.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\DTOs\Analytics\AnalyticsChartData|null  Chart data DTO, or null if no metrics exist.
     */
    public function getChartData(int $tradingPairId): ?AnalyticsChartData;

    /**
     * Get depth heatmap data for the analytics page.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\DTOs\Analytics\DepthHeatmapData  Heatmap matrix with timestamps, price levels, and bid/ask heat arrays.
     */
    public function getDepthHeatmap(int $tradingPairId): DepthHeatmapData;

    /**
     * Get distribution data for spread histogram and hourly stats.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\DTOs\Analytics\DistributionData  Distribution data including spread histogram buckets and hourly stats.
     */
    public function getDistributions(int $tradingPairId): DistributionData;

    /**
     * Classify the current market regime based on recent data.
     *
     * Uses CVD magnitude, realized volatility, spread BPS, large trade count,
     * and price direction to classify as TRENDING_UP, TRENDING_DOWN, RANGING, or VOLATILE.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\DTOs\Analytics\MarketRegimeData  Market regime classification with confidence and signals.
     */
    public function getMarketRegime(int $tradingPairId): MarketRegimeData;

    /**
     * Get cross-metric correlation scatter plot data.
     *
     * Computes 4 scatter datasets: OI vs price change, funding vs premium,
     * volume vs volatility, and imbalance vs next-period price change.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\DTOs\Analytics\CorrelationData  Correlation scatter plot data.
     */
    public function getCorrelations(int $tradingPairId): CorrelationData;
}
