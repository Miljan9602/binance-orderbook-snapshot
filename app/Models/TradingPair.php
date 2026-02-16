<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingPair extends Model
{
    protected $fillable = [
        'symbol',
        'base_asset',
        'quote_asset',
        'stream_name',
        'is_active',
        'depth_level',
        'last_update_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_update_at' => 'datetime',
    ];

    public function snapshot(): HasOne
    {
        return $this->hasOne(OrderbookSnapshot::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderbookHistory::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function ticker(): HasOne
    {
        return $this->hasOne(Ticker::class);
    }

    public function klines(): HasMany
    {
        return $this->hasMany(Kline::class);
    }
}
