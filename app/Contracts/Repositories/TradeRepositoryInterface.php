<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for trade and trade aggregate data access operations.
 *
 * Provides methods for querying trades, large trades, trade aggregates,
 * and hourly trade statistics used by the orderbook and analytics pages.
 */
interface TradeRepositoryInterface
{
    /**
     * Get recent trades for a trading pair ordered by most recent first.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of trades to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trade>
     */
    public function getRecentTrades(int $tradingPairId, int $limit = 50): Collection;

    /**
     * Get recent trades formatted for chart display.
     *
     * Results are returned in chronological order (oldest first) for chart rendering.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of trades to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trade>
     */
    public function getRecentTradesForChart(int $tradingPairId, int $limit = 100): Collection;

    /**
     * Get recent large trades that exceeded the size threshold.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of large trades to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\LargeTrade>
     */
    public function getRecentLargeTrades(int $tradingPairId, int $limit = 20): Collection;

    /**
     * Get recent trade aggregates in chronological order (oldest first).
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of aggregates to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradeAggregate>
     */
    public function getRecentAggregates(int $tradingPairId, int $limit = 30): Collection;

    /**
     * Get filtered and paginated trade aggregates.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\DateRangeFilter  $filter  Date range filter for period_start column.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\TradeAggregate>
     */
    public function getFilteredAggregates(int $tradingPairId, \App\DTOs\Filters\DateRangeFilter $filter, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get hourly trade statistics grouped by hour.
     *
     * Returns average volume and trade count for each hour of the day,
     * keyed by hour (0-23). Used for the hourly heatmap chart.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $hoursBack  Number of hours to look back from now.
     * @return \Illuminate\Support\Collection<int, object{hour: int, avg_volume: float, avg_trades: float}>
     */
    public function getHourlyTradeStats(int $tradingPairId, int $hoursBack = 24): \Illuminate\Support\Collection;

    /**
     * Get the cumulative CVD sum for all aggregates before a given timestamp.
     *
     * Used as the base value for computing running cumulative CVD in the visible window.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \Carbon\Carbon  $before  Sum CVD for all aggregates before this time.
     * @return float  Total CVD before the given time.
     */
    public function getCumulativeCvdBefore(int $tradingPairId, \Carbon\Carbon $before): float;
}
