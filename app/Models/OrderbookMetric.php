<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderbookMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'bid_volume',
        'ask_volume',
        'imbalance',
        'mid_price',
        'weighted_mid_price',
        'spread_bps',
        'received_at',
    ];

    protected $casts = [
        'bid_volume' => 'decimal:8',
        'ask_volume' => 'decimal:8',
        'imbalance' => 'decimal:6',
        'mid_price' => 'decimal:8',
        'weighted_mid_price' => 'decimal:8',
        'spread_bps' => 'decimal:4',
        'received_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
