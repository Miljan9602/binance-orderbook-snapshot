<?php

namespace App\Repositories;

use App\Contracts\Repositories\KlineRepositoryInterface;
use App\DTOs\Filters\KlineFilter;
use App\Models\Kline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for kline/candlestick data access.
 *
 * Queries kline records with interval filtering, date range support,
 * and chart-optimized retrieval with chronological ordering.
 *
 * @see \App\Contracts\Repositories\KlineRepositoryInterface
 */
class KlineRepository implements KlineRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilteredKlines(int $tradingPairId, KlineFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        $query = Kline::forTradingPair($tradingPairId)
            ->where('interval', $filter->interval);

        if ($filter->from !== null) {
            $query->where('open_time', '>=', $filter->from);
        }

        if ($filter->to !== null) {
            $query->where('open_time', '<=', $filter->to);
        }

        return $query->orderByDesc('open_time')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentKlinesForChart(int $tradingPairId, string $interval = '1m', int $limit = 200): Collection
    {
        return Kline::forTradingPair($tradingPairId)
            ->where('interval', $interval)
            ->orderByDesc('open_time')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
