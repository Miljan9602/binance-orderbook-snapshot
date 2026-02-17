<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Polled open interest snapshots (append-only).
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $open_interest
 * @property \Illuminate\Support\Carbon|null $timestamp
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class OpenInterest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'open_interest';

    protected $fillable = [
        'trading_pair_id',
        'open_interest',
        'timestamp',
        'received_at',
    ];

    protected $casts = [
        'open_interest' => 'decimal:8',
        'timestamp' => 'datetime',
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
     * Get the trading pair that owns this open interest record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
