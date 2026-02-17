<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\DTOs\Filters\HistoryFilter;

/**
 * Contract for orderbook data access operations.
 *
 * Provides methods for querying orderbook snapshots, history, metrics,
 * sparkline data, spread analysis, and hourly statistics.
 */
interface OrderbookRepositoryInterface
{
    /**
     * Get recent orderbook history snapshots for a trading pair.
     *
     * Results are returned in chronological order (oldest first).
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of snapshots to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookHistory>
     */
    public function getRecentSnapshots(int $tradingPairId, int $limit = 30): Collection;

    /**
     * Get filtered and paginated orderbook history.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\HistoryFilter  $filter  Filter criteria including date range and spread bounds.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\OrderbookHistory>
     */
    public function getFilteredHistory(int $tradingPairId, HistoryFilter $filter, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get recent orderbook metrics for chart display.
     *
     * Results are returned in chronological order (oldest first).
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of metrics to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookMetric>
     */
    public function getRecentMetrics(int $tradingPairId, int $limit = 60): Collection;

    /**
     * Get the latest orderbook metric for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\Models\OrderbookMetric|null  The most recent metric, or null if none exist.
     */
    public function getLatestMetric(int $tradingPairId): ?\App\Models\OrderbookMetric;

    /**
     * Get sparkline data (mid prices) for a trading pair.
     *
     * Returns a collection of float mid-price values in chronological order
     * for rendering mini sparkline charts on the dashboard.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of data points to return.
     * @return \Illuminate\Support\Collection<int, float>
     */
    public function getSparklineData(int $tradingPairId, int $limit = 30): \Illuminate\Support\Collection;

    /**
     * Get recent spread values in basis points.
     *
     * Used for computing the spread distribution histogram.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of spread values to return.
     * @return \Illuminate\Support\Collection<int, float>
     */
    public function getRecentSpreadBps(int $tradingPairId, int $limit = 500): \Illuminate\Support\Collection;

    /**
     * Get hourly orderbook statistics grouped by hour.
     *
     * Returns average spread and imbalance for each hour of the day,
     * keyed by hour (0-23). Used for the hourly heatmap chart.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $hoursBack  Number of hours to look back from now.
     * @return \Illuminate\Support\Collection<int, object{hour: int, avg_spread: float, avg_imbalance: float}>
     */
    public function getHourlyOrderbookStats(int $tradingPairId, int $hoursBack = 24): \Illuminate\Support\Collection;

    /**
     * Get recent orderbook walls for a trading pair.
     *
     * Returns the most recently detected walls ordered by detection time descending.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of walls to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookWall>
     */
    public function getRecentWalls(int $tradingPairId, int $limit = 20): Collection;

    /**
     * Get currently active orderbook walls for a trading pair.
     *
     * Returns only walls with ACTIVE status ordered by detection time descending.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookWall>
     */
    public function getActiveWalls(int $tradingPairId): Collection;
}
