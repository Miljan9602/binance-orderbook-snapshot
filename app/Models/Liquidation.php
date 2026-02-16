<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liquidation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'side',
        'order_type',
        'price',
        'quantity',
        'avg_price',
        'order_status',
        'order_time',
        'received_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'avg_price' => 'decimal:8',
        'order_time' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
