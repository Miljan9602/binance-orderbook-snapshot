<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Volume-Synchronized Probability of Informed Trading (VPIN) metric.
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $vpin
 * @property string $bucket_volume
 * @property int $bucket_count
 * @property int $window_size
 * @property \Illuminate\Support\Carbon|null $computed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class VpinMetric extends Model
{
    use HasFactory;

    protected $table = 'vpin_metrics';

    protected $fillable = [
        'trading_pair_id',
        'vpin',
        'bucket_volume',
        'bucket_count',
        'window_size',
        'computed_at',
    ];

    protected $casts = [
        'vpin' => 'decimal:6',
        'bucket_volume' => 'decimal:8',
        'computed_at' => 'datetime',
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
     * Get the trading pair that owns this VPIN metric.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
