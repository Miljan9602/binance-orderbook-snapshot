<?php

namespace App\Repositories;

use App\Contracts\Repositories\AnalyticsRepositoryInterface;
use App\DTOs\Filters\AnalyticsFilter;
use App\Models\OrderbookMetric;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository for analytics-specific data access.
 *
 * Queries orderbook metrics with date range filtering and pagination
 * for the analytics page metrics table.
 *
 * @see \App\Contracts\Repositories\AnalyticsRepositoryInterface
 */
class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilteredMetrics(int $tradingPairId, AnalyticsFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = OrderbookMetric::forTradingPair($tradingPairId);

        if ($filter->metricsFrom !== null) {
            $query->where('received_at', '>=', $filter->metricsFrom);
        }

        if ($filter->metricsTo !== null) {
            $query->where('received_at', '<=', $filter->metricsTo);
        }

        return $query->orderByDesc('received_at')
            ->paginate($perPage, ['*'], 'metrics_page')
            ->withQueryString();
    }
}
