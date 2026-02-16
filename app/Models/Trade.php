<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trading_pair_id',
        'agg_trade_id',
        'price',
        'quantity',
        'first_trade_id',
        'last_trade_id',
        'is_buyer_maker',
        'traded_at',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'is_buyer_maker' => 'boolean',
        'traded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
