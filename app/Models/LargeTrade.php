<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detected large trades exceeding configurable size threshold.
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property int $trade_id
 * @property string $price
 * @property string $quantity
 * @property bool $is_buyer_maker
 * @property string $avg_trade_size
 * @property string $size_multiple
 * @property \Illuminate\Support\Carbon|null $traded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class LargeTrade extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'trade_id',
        'price',
        'quantity',
        'is_buyer_maker',
        'avg_trade_size',
        'size_multiple',
        'traded_at',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'is_buyer_maker' => 'boolean',
        'avg_trade_size' => 'decimal:8',
        'size_multiple' => 'decimal:2',
        'traded_at' => 'datetime',
        'created_at' => 'datetime',
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
     * Get the trading pair that owns this large trade.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
