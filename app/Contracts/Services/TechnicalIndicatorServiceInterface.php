<?php

namespace App\Contracts\Services;

use Illuminate\Support\Collection;

/**
 * Contract for computing technical indicators from kline data.
 */
interface TechnicalIndicatorServiceInterface
{
    /**
     * Compute requested technical indicators from kline data.
     *
     * @param  \Illuminate\Support\Collection  $klines  Raw kline model instances.
     * @param  array<string>  $indicators  List of indicator names (rsi, bb, ema, macd, taker).
     * @return array<string, array>  Keyed indicator data arrays.
     */
    public function compute(Collection $klines, array $indicators): array;
}
