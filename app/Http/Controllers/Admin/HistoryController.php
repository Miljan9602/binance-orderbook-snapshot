<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\OrderbookRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HistoryFilterRequest;
use App\Models\TradingPair;
use Illuminate\View\View;

/**
 * Handles the orderbook history page.
 *
 * Displays paginated and filterable orderbook history records
 * with date range and spread bound filters.
 */
class HistoryController extends Controller
{
    /**
     * Create a new history controller instance.
     *
     * @param  \App\Contracts\Repositories\OrderbookRepositoryInterface  $orderbookRepository  Repository for orderbook data queries.
     */
    public function __construct(
        private OrderbookRepositoryInterface $orderbookRepository,
    ) {}

    /**
     * Display the filtered and paginated orderbook history.
     *
     * @param  \App\Http\Requests\Admin\HistoryFilterRequest  $request
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\View\View
     */
    public function index(HistoryFilterRequest $request, TradingPair $tradingPair): View
    {
        $history = $this->orderbookRepository->getFilteredHistory(
            $tradingPair->id,
            $request->toFilter(),
        );

        return view('admin.trading-pairs.history', compact('tradingPair', 'history'));
    }
}
