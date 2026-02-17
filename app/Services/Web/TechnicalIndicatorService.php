<?php

namespace App\Services\Web;

use App\Contracts\Services\TechnicalIndicatorServiceInterface;
use Illuminate\Support\Collection;

/**
 * Computes technical indicators from kline data.
 *
 * Supports RSI (14-period, Wilder's smoothing), Bollinger Bands (20-period, 2 stddev),
 * EMA 20/50, MACD (12, 26, 9), and taker buy/sell ratio.
 */
class TechnicalIndicatorService implements TechnicalIndicatorServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function compute(Collection $klines, array $indicators): array
    {
        $result = [];

        foreach ($indicators as $indicator) {
            $result[$indicator] = match ($indicator) {
                'rsi' => $this->computeRsi($klines),
                'bb' => $this->computeBollingerBands($klines),
                'ema' => $this->computeEma($klines),
                'macd' => $this->computeMacd($klines),
                'taker' => $this->computeTakerRatio($klines),
                default => [],
            };
        }

        return $result;
    }

    /**
     * Compute RSI using 14-period Wilder's smoothing.
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @return array<int, array{time: int, value: float}>
     */
    public function computeRsi(Collection $klines, int $period = 14): array
    {
        $closes = $klines->pluck('close')->map(fn($v) => (float) $v)->values()->all();
        $times = $klines->pluck('open_time')->values()->all();

        if (count($closes) < $period + 1) {
            return [];
        }

        $gains = [];
        $losses = [];
        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        // Initial average gain/loss
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        $result = [];
        for ($i = $period; $i < count($closes); $i++) {
            if ($avgLoss == 0) {
                $rsi = 100.0;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi = 100 - (100 / (1 + $rs));
            }

            $result[] = [
                'time' => $times[$i]->timestamp,
                'value' => round($rsi, 2),
            ];

            // Wilder's smoothing for subsequent periods
            if ($i < count($closes) - 1) {
                $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
            }
        }

        return $result;
    }

    /**
     * Compute Bollinger Bands (20-period SMA, 2 standard deviations).
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @return array<int, array{time: int, upper: float, middle: float, lower: float}>
     */
    public function computeBollingerBands(Collection $klines, int $period = 20, float $multiplier = 2.0): array
    {
        $closes = $klines->pluck('close')->map(fn($v) => (float) $v)->values()->all();
        $times = $klines->pluck('open_time')->values()->all();

        if (count($closes) < $period) {
            return [];
        }

        $result = [];
        for ($i = $period - 1; $i < count($closes); $i++) {
            $slice = array_slice($closes, $i - $period + 1, $period);
            $sma = array_sum($slice) / $period;

            $variance = 0;
            foreach ($slice as $val) {
                $variance += ($val - $sma) ** 2;
            }
            $stdDev = sqrt($variance / $period);

            $result[] = [
                'time' => $times[$i]->timestamp,
                'upper' => round($sma + ($multiplier * $stdDev), 8),
                'middle' => round($sma, 8),
                'lower' => round($sma - ($multiplier * $stdDev), 8),
            ];
        }

        return $result;
    }

    /**
     * Compute EMA 20 and EMA 50.
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @return array{ema20: array, ema50: array}
     */
    public function computeEma(Collection $klines): array
    {
        return [
            'ema20' => $this->computeEmaSeries($klines, 20),
            'ema50' => $this->computeEmaSeries($klines, 50),
        ];
    }

    /**
     * Compute a single EMA series.
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @param  int  $period
     * @return array<int, array{time: int, value: float}>
     */
    private function computeEmaSeries(Collection $klines, int $period): array
    {
        $closes = $klines->pluck('close')->map(fn($v) => (float) $v)->values()->all();
        $times = $klines->pluck('open_time')->values()->all();

        if (count($closes) < $period) {
            return [];
        }

        $multiplier = 2.0 / ($period + 1);

        // Start with SMA as seed
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        $result = [];
        $result[] = [
            'time' => $times[$period - 1]->timestamp,
            'value' => round($ema, 8),
        ];

        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] - $ema) * $multiplier + $ema;
            $result[] = [
                'time' => $times[$i]->timestamp,
                'value' => round($ema, 8),
            ];
        }

        return $result;
    }

    /**
     * Compute MACD (12, 26, 9).
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @return array<int, array{time: int, macd: float, signal: float, histogram: float}>
     */
    public function computeMacd(Collection $klines, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $closes = $klines->pluck('close')->map(fn($v) => (float) $v)->values()->all();
        $times = $klines->pluck('open_time')->values()->all();

        if (count($closes) < $slow + $signal) {
            return [];
        }

        // Calculate EMA fast and slow
        $emaFast = $this->computeEmaValues($closes, $fast);
        $emaSlow = $this->computeEmaValues($closes, $slow);

        // MACD line = fast EMA - slow EMA (aligned from index $slow-1)
        $macdLine = [];
        $macdStart = $slow - 1;
        for ($i = $macdStart; $i < count($closes); $i++) {
            $macdLine[] = $emaFast[$i] - $emaSlow[$i];
        }

        if (count($macdLine) < $signal) {
            return [];
        }

        // Signal line = EMA of MACD line
        $signalLine = $this->computeEmaValues($macdLine, $signal);

        $result = [];
        $signalStart = $signal - 1;
        for ($i = $signalStart; $i < count($macdLine); $i++) {
            $timeIdx = $macdStart + $i;
            if ($timeIdx >= count($times)) break;

            $macdVal = $macdLine[$i];
            $signalVal = $signalLine[$i];
            $result[] = [
                'time' => $times[$timeIdx]->timestamp,
                'macd' => round($macdVal, 8),
                'signal' => round($signalVal, 8),
                'histogram' => round($macdVal - $signalVal, 8),
            ];
        }

        return $result;
    }

    /**
     * Compute raw EMA values for all positions.
     *
     * @param  array<float>  $values
     * @param  int  $period
     * @return array<float>
     */
    private function computeEmaValues(array $values, int $period): array
    {
        $multiplier = 2.0 / ($period + 1);
        $result = array_fill(0, count($values), 0.0);

        // SMA seed
        $ema = array_sum(array_slice($values, 0, $period)) / $period;
        $result[$period - 1] = $ema;

        for ($i = $period; $i < count($values); $i++) {
            $ema = ($values[$i] - $ema) * $multiplier + $ema;
            $result[$i] = $ema;
        }

        return $result;
    }

    /**
     * Compute taker buy/sell ratio.
     *
     * @param  \Illuminate\Support\Collection  $klines
     * @return array<int, array{time: int, buy_ratio: float, sell_ratio: float, buy_volume: float, sell_volume: float}>
     */
    public function computeTakerRatio(Collection $klines): array
    {
        $result = [];
        foreach ($klines as $k) {
            $volume = (float) $k->volume;
            $buyVol = (float) $k->taker_buy_volume;
            $sellVol = $volume > 0 ? $volume - $buyVol : 0;

            $buyRatio = $volume > 0 ? round($buyVol / $volume, 4) : 0.5;
            $sellRatio = $volume > 0 ? round($sellVol / $volume, 4) : 0.5;

            $result[] = [
                'time' => $k->open_time->timestamp,
                'buy_ratio' => $buyRatio,
                'sell_ratio' => $sellRatio,
                'buy_volume' => round($buyVol, 8),
                'sell_volume' => round($sellVol, 8),
            ];
        }

        return $result;
    }
}
