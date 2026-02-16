<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kline extends Model
{
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

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
