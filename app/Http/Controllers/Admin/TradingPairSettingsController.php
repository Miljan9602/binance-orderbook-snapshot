<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\TradingPairRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\TradingPair;
use Illuminate\Http\RedirectResponse;

/**
 * Handles trading pair settings like toggling active status.
 *
 * Provides an endpoint for activating or deactivating WebSocket
 * streaming for individual trading pairs.
 */
class TradingPairSettingsController extends Controller
{
    /**
     * Create a new trading pair settings controller instance.
     *
     * @param  \App\Contracts\Repositories\TradingPairRepositoryInterface  $tradingPairRepository  Repository for trading pair operations.
     */
    public function __construct(
        private TradingPairRepositoryInterface $tradingPairRepository,
    ) {}

    /**
     * Toggle the active status of a trading pair.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(TradingPair $tradingPair): RedirectResponse
    {
        $this->tradingPairRepository->toggleActive($tradingPair);

        return back()->with('status', "{$tradingPair->symbol} " . ($tradingPair->is_active ? 'activated' : 'deactivated'));
    }
}
