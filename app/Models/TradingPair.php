<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trading pair configuration and relationships.
 *
 * @property int $id
 * @property string $symbol
 * @property string $base_asset
 * @property string $quote_asset
 * @property string $stream_name
 * @property string|null $futures_symbol
 * @property bool $is_active
 * @property int $depth_level
 * @property \Illuminate\Support\Carbon|null $last_update_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrderbookSnapshot|null $snapshot
 * @property-read \App\Models\Ticker|null $ticker
 * @property-read \App\Models\FuturesMetric|null $futuresMetric
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookHistory> $history
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Trade> $trades
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kline> $klines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookMetric> $orderbookMetrics
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TradeAggregate> $tradeAggregates
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LargeTrade> $largeTrades
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FuturesMetricHistory> $futuresMetricsHistory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Liquidation> $liquidations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OpenInterest> $openInterest
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderbookWall> $walls
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VpinMetric> $vpinMetrics
 */
class TradingPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'base_asset',
        'quote_asset',
        'stream_name',
        'futures_symbol',
        'is_active',
        'depth_level',
        'last_update_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_update_at' => 'datetime',
    ];

    /**
     * Scope to only active trading pairs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to trading pairs that have a configured futures symbol.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasFuturesSymbol(Builder $query): Builder
    {
        return $query->whereNotNull('futures_symbol');
    }

    /**
     * Get the current orderbook snapshot for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\OrderbookSnapshot>
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(OrderbookSnapshot::class);
    }

    /**
     * Get the orderbook history records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OrderbookHistory>
     */
    public function history(): HasMany
    {
        return $this->hasMany(OrderbookHistory::class);
    }

    /**
     * Get the trade records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Trade>
     */
    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Get the 24-hour rolling ticker for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\Ticker>
     */
    public function ticker(): HasOne
    {
        return $this->hasOne(Ticker::class);
    }

    /**
     * Get the kline (candlestick) records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Kline>
     */
    public function klines(): HasMany
    {
        return $this->hasMany(Kline::class);
    }

    /**
     * Get the computed orderbook metrics for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OrderbookMetric>
     */
    public function orderbookMetrics(): HasMany
    {
        return $this->hasMany(OrderbookMetric::class);
    }

    /**
     * Get the trade aggregates for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TradeAggregate>
     */
    public function tradeAggregates(): HasMany
    {
        return $this->hasMany(TradeAggregate::class);
    }

    /**
     * Get the latest futures metric for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\FuturesMetric>
     */
    public function futuresMetric(): HasOne
    {
        return $this->hasOne(FuturesMetric::class);
    }

    /**
     * Get the futures metric history records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\FuturesMetricHistory>
     */
    public function futuresMetricsHistory(): HasMany
    {
        return $this->hasMany(FuturesMetricHistory::class);
    }

    /**
     * Get the liquidation records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Liquidation>
     */
    public function liquidations(): HasMany
    {
        return $this->hasMany(Liquidation::class);
    }

    /**
     * Get the open interest records for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OpenInterest>
     */
    public function openInterest(): HasMany
    {
        return $this->hasMany(OpenInterest::class);
    }

    /**
     * Get the detected large trades for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\LargeTrade>
     */
    public function largeTrades(): HasMany
    {
        return $this->hasMany(LargeTrade::class);
    }

    /**
     * Get the detected orderbook walls for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\OrderbookWall>
     */
    public function walls(): HasMany
    {
        return $this->hasMany(OrderbookWall::class);
    }

    /**
     * Get the VPIN metrics for this trading pair.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\VpinMetric>
     */
    public function vpinMetrics(): HasMany
    {
        return $this->hasMany(VpinMetric::class);
    }
}
