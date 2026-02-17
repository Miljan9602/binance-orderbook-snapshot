<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Candlestick/OHLCV data for all intervals.
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $interval
 * @property \Illuminate\Support\Carbon|null $open_time
 * @property \Illuminate\Support\Carbon|null $close_time
 * @property string $open
 * @property string $high
 * @property string $low
 * @property string $close
 * @property string $volume
 * @property string $quote_volume
 * @property string $taker_buy_volume
 * @property string $taker_buy_quote_volume
 * @property int $trade_count
 * @property bool $is_closed
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class Kline extends Model
{
    use HasFactory;

    protected $fillable = [
        'trading_pair_id',
        'interval',
        'open_time',
        'close_time',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'quote_volume',
        'taker_buy_volume',
        'taker_buy_quote_volume',
        'trade_count',
        'is_closed',
        'received_at',
    ];

    protected $casts = [
        'open_time' => 'datetime',
        'close_time' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
        'quote_volume' => 'decimal:8',
        'taker_buy_volume' => 'decimal:8',
        'taker_buy_quote_volume' => 'decimal:8',
        'trade_count' => 'integer',
        'is_closed' => 'boolean',
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
     * Get the trading pair that owns this kline.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
