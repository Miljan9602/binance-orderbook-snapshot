<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Forced liquidation orders (append-only).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $side
 * @property string $order_type
 * @property string $price
 * @property string $quantity
 * @property string $avg_price
 * @property string $order_status
 * @property \Illuminate\Support\Carbon|null $order_time
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class Liquidation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'side',
        'order_type',
        'price',
        'quantity',
        'avg_price',
        'order_status',
        'order_time',
        'received_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'avg_price' => 'decimal:8',
        'order_time' => 'datetime',
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
     * Get the trading pair that owns this liquidation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
