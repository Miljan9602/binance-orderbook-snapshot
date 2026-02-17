<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Current orderbook state per trading pair (upserted).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property int $last_update_id
 * @property array $bids
 * @property array $asks
 * @property string $best_bid_price
 * @property string $best_ask_price
 * @property string $spread
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class OrderbookSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'last_update_id',
        'bids',
        'asks',
        'best_bid_price',
        'best_ask_price',
        'spread',
        'received_at',
    ];

    protected $casts = [
        'bids' => 'array',
        'asks' => 'array',
        'best_bid_price' => 'decimal:8',
        'best_ask_price' => 'decimal:8',
        'spread' => 'decimal:8',
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
     * Get the trading pair that owns this snapshot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
