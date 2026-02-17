<?php

namespace App\Contracts\Repositories;

use App\DTOs\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for futures-related data access operations.
 *
 * Provides methods for querying futures metric history, open interest,
 * and liquidation records with filtering, pagination, and time-based retrieval.
 */
interface FuturesRepositoryInterface
{
    /**
     * Get filtered and paginated futures metric history.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\DateRangeFilter  $filter  Date range filter for received_at column.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\FuturesMetricHistory>
     */
    public function getFilteredHistory(int $tradingPairId, DateRangeFilter $filter, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get filtered and paginated open interest history.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\DateRangeFilter  $filter  Date range filter for timestamp column.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\OpenInterest>
     */
    public function getFilteredOpenInterest(int $tradingPairId, DateRangeFilter $filter, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get recent futures metric history for charts.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of records to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\FuturesMetricHistory>
     */
    public function getRecentFuturesHistory(int $tradingPairId, int $limit = 60): Collection;

    /**
     * Get recent open interest records for charts.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of records to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\OpenInterest>
     */
    public function getRecentOpenInterest(int $tradingPairId, int $limit = 60): Collection;

    /**
     * Get the latest open interest record for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @return \App\Models\OpenInterest|null  The most recent open interest record, or null if none exist.
     */
    public function getLatestOpenInterest(int $tradingPairId): ?\App\Models\OpenInterest;

    /**
     * Get recent liquidations for a trading pair.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  int  $limit  Maximum number of liquidations to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Liquidation>
     */
    public function getRecentLiquidations(int $tradingPairId, int $limit = 50): Collection;

    /**
     * Get liquidations since a given cutoff time.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \Illuminate\Support\Carbon  $since  The cutoff timestamp; only liquidations at or after this time are returned.
     * @param  int  $limit  Maximum number of liquidations to return.
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Liquidation>
     */
    public function getLiquidationsSince(int $tradingPairId, \Illuminate\Support\Carbon $since, int $limit = 500): Collection;
}
