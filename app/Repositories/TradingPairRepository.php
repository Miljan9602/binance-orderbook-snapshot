<?php

namespace App\Repositories;

use App\Contracts\Repositories\TradingPairRepositoryInterface;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for trading pair data access.
 *
 * Queries trading pairs with eager-loaded snapshot and ticker
 * relationships and manages the active streaming status.
 *
 * @see \App\Contracts\Repositories\TradingPairRepositoryInterface
 */
class TradingPairRepository implements TradingPairRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAllWithSnapshotAndTicker(): Collection
    {
        return TradingPair::with(['snapshot', 'ticker'])->orderBy('symbol')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstWithSnapshotAndTicker(): ?TradingPair
    {
        return TradingPair::with(['snapshot', 'ticker'])->orderBy('symbol')->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActive(): Collection
    {
        return TradingPair::active()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function toggleActive(TradingPair $tradingPair): void
    {
        $tradingPair->update(['is_active' => !$tradingPair->is_active]);
    }
}
