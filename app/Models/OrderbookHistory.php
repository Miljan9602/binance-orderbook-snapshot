<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderbookHistory extends Model
{
    protected $table = 'orderbook_history';

    protected $fillable = [
        'trading_pair_id',
        'last_update_id',
        'bids',
        'asks',
        'best_bid_price',
        'best_ask_price',
        'spread',
        'received_at',
    ];

    protected $casts = [
        'bids' => 'array',
        'asks' => 'array',
        'best_bid_price' => 'decimal:8',
        'best_ask_price' => 'decimal:8',
        'spread' => 'decimal:8',
        'received_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
