<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historical futures metrics (append-only, sampled).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $mark_price
 * @property string $index_price
 * @property string $funding_rate
 * @property \Illuminate\Support\Carbon|null $next_funding_time
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class FuturesMetricHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'futures_metrics_history';

    protected $fillable = [
        'trading_pair_id',
        'mark_price',
        'index_price',
        'funding_rate',
        'next_funding_time',
        'received_at',
    ];

    protected $casts = [
        'mark_price' => 'decimal:8',
        'index_price' => 'decimal:8',
        'funding_rate' => 'decimal:10',
        'next_funding_time' => 'datetime',
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
     * Get the trading pair that owns this history record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
