<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesMetric extends Model
{
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

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
