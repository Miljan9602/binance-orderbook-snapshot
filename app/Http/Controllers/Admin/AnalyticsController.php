<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AnalyticsRepositoryInterface;
use App\Contracts\Repositories\TradeRepositoryInterface;
use App\Contracts\Services\AnalyticsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsFilterRequest;
use App\DTOs\Filters\DateRangeFilter;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Handles the analytics page and its data endpoints.
 *
 * Displays 10 Chart.js charts, live metric cards, large trades feed,
 * and paginated tables. Provides three JSON data endpoints for AJAX polling.
 */
class AnalyticsController extends Controller
{
    /**
     * Create a new analytics controller instance.
     *
     * @param  \App\Contracts\Services\AnalyticsServiceInterface  $analyticsService  Service for assembling analytics chart data.
     * @param  \App\Contracts\Repositories\AnalyticsRepositoryInterface  $analyticsRepository  Repository for analytics metric queries.
     * @param  \App\Contracts\Repositories\TradeRepositoryInterface  $tradeRepository  Repository for trade aggregate queries.
     */
    public function __construct(
        private AnalyticsServiceInterface $analyticsService,
        private AnalyticsRepositoryInterface $analyticsRepository,
        private TradeRepositoryInterface $tradeRepository,
    ) {}

    /**
     * Display the analytics page with metrics and aggregates tables.
     *
     * @param  \App\Http\Requests\Admin\AnalyticsFilterRequest  $request
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\View\View
     */
    public function index(AnalyticsFilterRequest $request, TradingPair $tradingPair): View
    {
        $filter = $request->toFilter();

        $metrics = $this->analyticsRepository->getFilteredMetrics($tradingPair->id, $filter);

        $aggFilter = new DateRangeFilter(
            from: $filter->aggFrom,
            to: $filter->aggTo,
        );
        $aggregates = $this->tradeRepository->getFilteredAggregates($tradingPair->id, $aggFilter);

        return view('admin.trading-pairs.analytics', compact('tradingPair', 'metrics', 'aggregates'));
    }

    /**
     * Return JSON chart data for analytics.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(TradingPair $tradingPair): JsonResponse
    {
        $chartData = $this->analyticsService->getChartData($tradingPair->id);

        return response()->json($chartData?->toArray());
    }

    /**
     * Return JSON depth heatmap data.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function depthData(TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->analyticsService->getDepthHeatmap($tradingPair->id)->toArray()
        );
    }

    /**
     * Return JSON distribution data (spread histogram + hourly stats).
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function distributions(TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->analyticsService->getDistributions($tradingPair->id)->toArray()
        );
    }

    /**
     * Return JSON market regime classification.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function regime(TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->analyticsService->getMarketRegime($tradingPair->id)->toArray()
        );
    }

    /**
     * Return JSON cross-metric correlation scatter data.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function correlations(TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->analyticsService->getCorrelations($tradingPair->id)->toArray()
        );
    }
}
