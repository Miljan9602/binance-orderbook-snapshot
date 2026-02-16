<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenInterest extends Model
{
    public $timestamps = false;

    protected $table = 'open_interest';

    protected $fillable = [
        'trading_pair_id',
        'open_interest',
        'timestamp',
        'received_at',
    ];

    protected $casts = [
        'open_interest' => 'decimal:8',
        'timestamp' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function tradingPair(): BelongsTo
    {
        return $this->belongsTo(TradingPair::class);
    }
}
