<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Periodic trade rollups with VWAP, CVD, and realized volatility.
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $interval
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property string $vwap
 * @property string $buy_volume
 * @property string $sell_volume
 * @property string $cvd
 * @property int $trade_count
 * @property string $avg_trade_size
 * @property string $max_trade_size
 * @property string $close_price
 * @property string $price_change_pct
 * @property string $realized_vol_5m
 * @property-read \App\Models\TradingPair $tradingPair
 */
class TradeAggregate extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'interval',
        'period_start',
        'vwap',
        'buy_volume',
        'sell_volume',
        'cvd',
        'trade_count',
        'avg_trade_size',
        'max_trade_size',
        'close_price',
        'price_change_pct',
        'realized_vol_5m',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'vwap' => 'decimal:8',
        'buy_volume' => 'decimal:8',
        'sell_volume' => 'decimal:8',
        'cvd' => 'decimal:8',
        'trade_count' => 'integer',
        'avg_trade_size' => 'decimal:8',
        'max_trade_size' => 'decimal:8',
        'close_price' => 'decimal:8',
        'price_change_pct' => 'decimal:6',
        'realized_vol_5m' => 'decimal:6',
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
     * Get the trading pair that owns this aggregate.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
