<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeAggregate extends Model
{
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
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
