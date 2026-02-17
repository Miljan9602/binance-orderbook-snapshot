<?php

namespace App\Contracts\Repositories;

use App\DTOs\Filters\KlineFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for kline (candlestick) data access operations.
 *
 * Provides methods for querying kline records with interval filtering,
 * date range support, and chart-optimized retrieval.
 */
interface KlineRepositoryInterface
{
    /**
     * Get filtered and paginated klines.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\KlineFilter  $filter  Filter criteria including interval and date range.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\Kline>
     */
    public function getFilteredKlines(int $tradingPairId, KlineFilter $filter, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get recent klines for chart display.
     *
     * Results are returned in chronological order (oldest first) for chart rendering.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  string  $interval  The kline interval (e.g. '1m', '5m', '15m', '1h').
     * @param  int  $limit  Maximum number of klines to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kline>
     */
    public function getRecentKlinesForChart(int $tradingPairId, string $interval = '1m', int $limit = 200): Collection;
}
