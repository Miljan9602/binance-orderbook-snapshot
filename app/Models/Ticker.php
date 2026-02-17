<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 24-hour rolling ticker statistics per trading pair (upserted).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $price_change
 * @property string $price_change_percent
 * @property string $weighted_avg_price
 * @property string $last_price
 * @property string $last_quantity
 * @property string $best_bid_price
 * @property string $best_bid_quantity
 * @property string $best_ask_price
 * @property string $best_ask_quantity
 * @property string $open_price
 * @property string $high_price
 * @property string $low_price
 * @property string $volume
 * @property string $quote_volume
 * @property int $trade_count
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class Ticker extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'price_change',
        'price_change_percent',
        'weighted_avg_price',
        'last_price',
        'last_quantity',
        'best_bid_price',
        'best_bid_quantity',
        'best_ask_price',
        'best_ask_quantity',
        'open_price',
        'high_price',
        'low_price',
        'volume',
        'quote_volume',
        'trade_count',
        'received_at',
    ];

    protected $casts = [
        'price_change' => 'decimal:8',
        'price_change_percent' => 'decimal:4',
        'weighted_avg_price' => 'decimal:8',
        'last_price' => 'decimal:8',
        'last_quantity' => 'decimal:8',
        'best_bid_price' => 'decimal:8',
        'best_bid_quantity' => 'decimal:8',
        'best_ask_price' => 'decimal:8',
        'best_ask_quantity' => 'decimal:8',
        'open_price' => 'decimal:8',
        'high_price' => 'decimal:8',
        'low_price' => 'decimal:8',
        'volume' => 'decimal:8',
        'quote_volume' => 'decimal:8',
        'trade_count' => 'integer',
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
     * Get the trading pair that owns this ticker.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
