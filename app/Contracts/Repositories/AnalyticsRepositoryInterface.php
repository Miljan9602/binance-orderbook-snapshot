<?php

namespace App\Contracts\Repositories;

use App\DTOs\Filters\AnalyticsFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for analytics-specific data access operations.
 *
 * Provides methods for querying orderbook metrics with filtering and pagination,
 * used by the analytics page to display historical metric tables.
 */
interface AnalyticsRepositoryInterface
{
    /**
     * Get filtered and paginated orderbook metrics.
     *
     * @param  int  $tradingPairId  The trading pair to query.
     * @param  \App\DTOs\Filters\AnalyticsFilter  $filter  Filter criteria for metric and aggregate date ranges.
     * @param  int  $perPage  Number of records per page.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\OrderbookMetric>
     */
    public function getFilteredMetrics(int $tradingPairId, AnalyticsFilter $filter, int $perPage = 50): LengthAwarePaginator;
}
