<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticker extends Model
{
    protected $fillable = [
        'trading_pair_id',
        'price_change',
        'price_change_percent',
        'weighted_avg_price',
        'last_price',
        'last_quantity',
        'best_bid_price',
        'best_bid_quantity',
        'best_ask_price',
        'best_ask_quantity',
        'open_price',
        'high_price',
        'low_price',
        'volume',
        'quote_volume',
        'trade_count',
        'received_at',
    ];

    protected $casts = [
        'price_change' => 'decimal:8',
        'price_change_percent' => 'decimal:4',
        'weighted_avg_price' => 'decimal:8',
        'last_price' => 'decimal:8',
        'last_quantity' => 'decimal:8',
        'best_bid_price' => 'decimal:8',
        'best_bid_quantity' => 'decimal:8',
        'best_ask_price' => 'decimal:8',
        'best_ask_quantity' => 'decimal:8',
        'open_price' => 'decimal:8',
        'high_price' => 'decimal:8',
        'low_price' => 'decimal:8',
        'volume' => 'decimal:8',
        'quote_volume' => 'decimal:8',
        'trade_count' => 'integer',
        'received_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
