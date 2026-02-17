<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\DashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Handles the main dashboard page and its data endpoint.
 *
 * Displays all trading pairs with price, stats, and sparkline cards.
 * Provides a JSON data endpoint for AJAX polling of summary data.
 */
class DashboardController extends Controller
{
    /**
     * Create a new dashboard controller instance.
     *
     * @param  \App\Contracts\Services\DashboardServiceInterface  $dashboardService  Service for assembling dashboard data.
     */
    public function __construct(
        private DashboardServiceInterface $dashboardService,
    ) {}

    /**
     * Display the dashboard with all trading pairs.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $pairs = $this->dashboardService->getAllPairs();

        return view('admin.trading-pairs.index', compact('pairs'));
    }

    /**
     * Return JSON summary data for the primary trading pair.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(): JsonResponse
    {
        $summary = $this->dashboardService->getSummaryData();

        return response()->json($summary?->toArray());
    }
}
