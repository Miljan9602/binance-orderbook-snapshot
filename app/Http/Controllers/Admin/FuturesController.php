<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\FuturesRepositoryInterface;
use App\Contracts\Services\FuturesQueryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FuturesFilterRequest;
use App\DTOs\Filters\DateRangeFilter;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Handles the futures page and its data endpoint.
 *
 * Displays funding rate, open interest, spot-futures premium, and
 * liquidation charts with live metric cards and paginated history tables.
 */
class FuturesController extends Controller
{
    /**
     * Create a new futures controller instance.
     *
     * @param  \App\Contracts\Services\FuturesQueryServiceInterface  $futuresQueryService  Service for assembling futures chart data.
     * @param  \App\Contracts\Repositories\FuturesRepositoryInterface  $futuresRepository  Repository for futures data queries.
     */
    public function __construct(
        private FuturesQueryServiceInterface $futuresQueryService,
        private FuturesRepositoryInterface $futuresRepository,
    ) {}

    /**
     * Display the futures page with history and OI tables.
     *
     * @param  \App\Http\Requests\Admin\FuturesFilterRequest  $request
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\View\View
     */
    public function index(FuturesFilterRequest $request, TradingPair $tradingPair): View
    {
        $filter = $request->toFilter();

        $historyFilter = new DateRangeFilter(
            from: $filter->historyFrom,
            to: $filter->historyTo,
        );
        $history = $this->futuresRepository->getFilteredHistory($tradingPair->id, $historyFilter);

        $oiFilter = new DateRangeFilter(
            from: $filter->oiFrom,
            to: $filter->oiTo,
        );
        $oiHistory = $this->futuresRepository->getFilteredOpenInterest($tradingPair->id, $oiFilter);

        return view('admin.trading-pairs.futures', compact('tradingPair', 'history', 'oiHistory'));
    }

    /**
     * Return JSON chart data for futures.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(TradingPair $tradingPair): JsonResponse
    {
        return response()->json(
            $this->futuresQueryService->getChartData($tradingPair)->toArray()
        );
    }
}
