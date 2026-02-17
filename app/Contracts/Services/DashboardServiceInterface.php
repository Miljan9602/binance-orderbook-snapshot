<?php

namespace App\Contracts\Services;

use App\DTOs\Dashboard\DashboardSummaryData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for assembling dashboard data.
 *
 * Provides methods for retrieving all trading pairs and the primary
 * trading pair's summary data including price, ticker stats, and sparkline.
 */
interface DashboardServiceInterface
{
    /**
     * Get all trading pairs for dashboard display.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradingPair>
     */
    public function getAllPairs(): Collection;

    /**
     * Get summary data for the primary trading pair.
     *
     * @return \App\DTOs\Dashboard\DashboardSummaryData|null  Summary DTO, or null if no trading pairs exist.
     */
    public function getSummaryData(): ?DashboardSummaryData;
}
