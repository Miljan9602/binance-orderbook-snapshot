<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\KlineRepositoryInterface;
use App\Contracts\Services\KlineQueryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KlineFilterRequest;
use App\Http\Requests\Admin\KlineDataRequest;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Handles the klines page and its data endpoint.
 *
 * Displays TradingView Lightweight Charts candlestick chart with volume
 * overlay, interval tab selector (1m/5m/15m/1h), and paginated OHLCV table.
 */
class KlineController extends Controller
{
    /**
     * Create a new kline controller instance.
     *
     * @param  \App\Contracts\Services\KlineQueryServiceInterface  $klineQueryService  Service for assembling kline chart data.
     * @param  \App\Contracts\Repositories\KlineRepositoryInterface  $klineRepository  Repository for kline data queries.
     */
    public function __construct(
        private KlineQueryServiceInterface $klineQueryService,
        private KlineRepositoryInterface $klineRepository,
    ) {}

    /**
     * Display the klines page with candlestick chart and table.
     *
     * @param  \App\Http\Requests\Admin\KlineFilterRequest  $request
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\View\View
     */
    public function index(KlineFilterRequest $request, TradingPair $tradingPair): View
    {
        $filter = $request->toFilter();
        $interval = $filter->interval;

        $klines = $this->klineRepository->getFilteredKlines($tradingPair->id, $filter);

        return view('admin.trading-pairs.klines', compact('tradingPair', 'klines', 'interval'));
    }

    /**
     * Return JSON kline data for chart display.
     *
     * @param  \App\Http\Requests\Admin\KlineDataRequest  $request
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(KlineDataRequest $request, TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->klineQueryService->getChartKlines($tradingPair->id, $request->interval(), $request->indicators())
        );
    }
}
