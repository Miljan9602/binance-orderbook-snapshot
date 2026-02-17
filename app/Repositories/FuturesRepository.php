<?php

namespace App\Repositories;

use App\Contracts\Repositories\FuturesRepositoryInterface;
use App\DTOs\Filters\DateRangeFilter;
use App\Models\FuturesMetricHistory;
use App\Models\Liquidation;
use App\Models\OpenInterest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Repository for futures data access.
 *
 * Queries futures metric history, open interest, and liquidation records
 * with filtering, pagination, and time-based retrieval.
 *
 * @see \App\Contracts\Repositories\FuturesRepositoryInterface
 */
class FuturesRepository implements FuturesRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilteredHistory(int $tradingPairId, DateRangeFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = FuturesMetricHistory::forTradingPair($tradingPairId);

        if ($filter->from !== null) {
            $query->where('received_at', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('received_at', '<=', $filter->to);
        }

        return $query->orderByDesc('received_at')
            ->paginate($perPage, ['*'], 'history_page')
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredOpenInterest(int $tradingPairId, DateRangeFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = OpenInterest::forTradingPair($tradingPairId);

        if ($filter->from !== null) {
            $query->where('timestamp', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('timestamp', '<=', $filter->to);
        }

        return $query->orderByDesc('timestamp')
            ->paginate($perPage, ['*'], 'oi_page')
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentFuturesHistory(int $tradingPairId, int $limit = 60): Collection
    {
        return FuturesMetricHistory::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentOpenInterest(int $tradingPairId, int $limit = 60): Collection
    {
        return OpenInterest::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestOpenInterest(int $tradingPairId): ?OpenInterest
    {
        return OpenInterest::forTradingPair($tradingPairId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentLiquidations(int $tradingPairId, int $limit = 50): Collection
    {
        return Liquidation::forTradingPair($tradingPairId)
            ->orderByDesc('order_time')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLiquidationsSince(int $tradingPairId, Carbon $since, int $limit = 500): Collection
    {
        return Liquidation::forTradingPair($tradingPairId)
            ->where('order_time', '>=', $since)
            ->orderBy('order_time')
            ->limit($limit)
            ->get();
    }
}
