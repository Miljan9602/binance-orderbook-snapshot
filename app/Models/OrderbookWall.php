<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detected orderbook wall (large resting order) at a specific price level.
 *
 * @property int $id
 * @property int $trading_pair_id
 * @property string $side
 * @property string $price
 * @property string $quantity
 * @property string $avg_level_quantity
 * @property float $size_multiple
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $detected_at
 * @property \Illuminate\Support\Carbon|null $removed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TradingPair $tradingPair
 */
class OrderbookWall extends Model
{
    use HasFactory;

    protected $table = 'orderbook_walls';

    protected $fillable = [
        'trading_pair_id',
        'side',
        'price',
        'quantity',
        'avg_level_quantity',
        'size_multiple',
        'status',
        'detected_at',
        'removed_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'avg_level_quantity' => 'decimal:8',
        'size_multiple' => 'float',
        'detected_at' => 'datetime',
        'removed_at' => 'datetime',
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
     * Scope to only active (non-removed) walls.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Get the trading pair that owns this orderbook wall.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TradingPair, self>
     */
    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
