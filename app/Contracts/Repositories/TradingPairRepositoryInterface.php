<?php

namespace App\Contracts\Repositories;

use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for trading pair data access operations.
 *
 * Provides methods for querying trading pairs with eager-loaded relationships
 * and managing the active streaming status.
 */
interface TradingPairRepositoryInterface
{
    /**
     * Get all trading pairs with their snapshot and ticker relationships.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradingPair>
     */
    public function getAllWithSnapshotAndTicker(): Collection;

    /**
     * Get the first trading pair with snapshot and ticker relationships.
     *
     * @return \App\Models\TradingPair|null
     */
    public function getFirstWithSnapshotAndTicker(): ?TradingPair;

    /**
     * Get all active trading pairs.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradingPair>
     */
    public function getActive(): Collection;

    /**
     * Toggle the active status of a trading pair.
     *
     * @param  \App\Models\TradingPair  $tradingPair
     * @return void
     */
    public function toggleActive(TradingPair $tradingPair): void;
}
