<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Computed orderbook analytics (sampled, append-only).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $bid_volume
 * @property string $ask_volume
 * @property string $imbalance
 * @property string $mid_price
 * @property string $weighted_mid_price
 * @property string $spread_bps
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class OrderbookMetric extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'bid_volume',
        'ask_volume',
        'imbalance',
        'mid_price',
        'weighted_mid_price',
        'spread_bps',
        'received_at',
    ];

    protected $casts = [
        'bid_volume' => 'decimal:8',
        'ask_volume' => 'decimal:8',
        'imbalance' => 'decimal:6',
        'mid_price' => 'decimal:8',
        'weighted_mid_price' => 'decimal:8',
        'spread_bps' => 'decimal:4',
        'received_at' => 'datetime',
    ];

    /**
     * Scope to a specific trading pair.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $tradingPairId  The trading pair ID to filter by.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTradingPair(Builder $query, int $tradingPairId): Builder
    {
        return $query->where('trading_pair_id', $tradingPairId);
    }

    /**
     * Get the trading pair that owns this metric.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
