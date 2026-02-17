<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\OrderbookQueryServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\TradingPair;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Handles the live orderbook page and its data endpoint.
 *
 * Displays a professional 3-column orderbook layout (stats | book | trades).
 * Provides a JSON data endpoint for AJAX polling at 300ms intervals.
 */
class OrderbookController extends Controller
{
    /**
     * Create a new orderbook controller instance.
     *
     * @param  \App\Contracts\Services\OrderbookQueryServiceInterface  $orderbookQueryService  Service for assembling orderbook view data.
     */
    public function __construct(
        private OrderbookQueryServiceInterface $orderbookQueryService,
    ) {}

    /**
     * Display the live orderbook page.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\View\View
     */
    public function show(TradingPair $tradingPair): View
    {
        $tradingPair->load('snapshot');

        $recentTrades = \App\Models\Trade::where('trading_pair_id', $tradingPair->id)
            ->orderByDesc('traded_at')
            ->limit(50)
            ->get();

        $ticker = $tradingPair->ticker;

        return view('admin.trading-pairs.show', compact('tradingPair', 'recentTrades', 'ticker'));
    }

    /**
     * Return JSON data for the live orderbook.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(TradingPair $tradingPair): JsonResponse
    {
        $viewData = $this->orderbookQueryService->getOrderbookViewData($tradingPair);

        $trades = $viewData->trades->map(fn($t) => [
            'price' => (float) $t->price,
            'quantity' => (float) $t->quantity,
            'is_buyer_maker' => $t->is_buyer_maker,
            'time' => $t->traded_at->format('H:i:s'),
        ]);

        return response()->json([
            'snapshot' => $viewData->snapshot,
            'ticker' => $viewData->ticker,
            'trades' => $trades,
        ]);
    }
}
