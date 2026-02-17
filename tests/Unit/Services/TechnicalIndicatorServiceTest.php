<?php

namespace Tests\Unit\Services;

use App\Models\Kline;
use App\Models\TradingPair;
use App\Services\Web\TechnicalIndicatorService;
use Tests\TestCase;

class TechnicalIndicatorServiceTest extends TestCase
{
    private TechnicalIndicatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TechnicalIndicatorService();
    }

    private function makeKlines(int $count, array $closes = []): \Illuminate\Support\Collection
    {
        $pair = TradingPair::factory()->create();
        $klines = collect();
        for ($i = 0; $i < $count; $i++) {
            $close = $closes[$i] ?? 0.2100 + ($i * 0.001);
            $klines->push(Kline::factory()->create([
                'trading_pair_id' => $pair->id,
                'open_time' => now()->subMinutes($count - $i),
                'open' => $close - 0.0005,
                'high' => $close + 0.001,
                'low' => $close - 0.001,
                'close' => $close,
                'volume' => 1000 + $i,
                'taker_buy_volume' => 600 + $i,
            ]));
        }
        return $klines;
    }

    public function test_rsi_returns_empty_for_insufficient_data(): void
    {
        $klines = $this->makeKlines(10);
        $result = $this->service->computeRsi($klines);
        $this->assertEmpty($result);
    }

    public function test_rsi_returns_values_for_sufficient_data(): void
    {
        $klines = $this->makeKlines(20);
        $result = $this->service->computeRsi($klines);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('time', $result[0]);
        $this->assertArrayHasKey('value', $result[0]);
        $this->assertGreaterThanOrEqual(0, $result[0]['value']);
        $this->assertLessThanOrEqual(100, $result[0]['value']);
    }

    public function test_rsi_100_for_all_gains(): void
    {
        $closes = array_map(fn($i) => 1.0 + ($i * 0.01), range(0, 19));
        $klines = $this->makeKlines(20, $closes);
        $result = $this->service->computeRsi($klines);
        $this->assertNotEmpty($result);
        $this->assertEquals(100.0, $result[0]['value']);
    }

    public function test_bollinger_bands_returns_empty_for_insufficient_data(): void
    {
        $klines = $this->makeKlines(15);
        $result = $this->service->computeBollingerBands($klines);
        $this->assertEmpty($result);
    }

    public function test_bollinger_bands_structure(): void
    {
        $klines = $this->makeKlines(25);
        $result = $this->service->computeBollingerBands($klines);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('upper', $result[0]);
        $this->assertArrayHasKey('middle', $result[0]);
        $this->assertArrayHasKey('lower', $result[0]);
        $this->assertGreaterThan($result[0]['middle'], $result[0]['upper']);
        $this->assertLessThan($result[0]['middle'], $result[0]['lower']);
    }

    public function test_ema_returns_empty_for_insufficient_data(): void
    {
        $klines = $this->makeKlines(10);
        $result = $this->service->computeEma($klines);
        $this->assertArrayHasKey('ema20', $result);
        $this->assertArrayHasKey('ema50', $result);
        $this->assertEmpty($result['ema20']);
        $this->assertEmpty($result['ema50']);
    }

    public function test_ema20_returns_values(): void
    {
        $klines = $this->makeKlines(25);
        $result = $this->service->computeEma($klines);
        $this->assertNotEmpty($result['ema20']);
        $this->assertArrayHasKey('time', $result['ema20'][0]);
        $this->assertArrayHasKey('value', $result['ema20'][0]);
    }

    public function test_macd_returns_empty_for_insufficient_data(): void
    {
        $klines = $this->makeKlines(30);
        $result = $this->service->computeMacd($klines);
        $this->assertEmpty($result);
    }

    public function test_macd_structure_for_sufficient_data(): void
    {
        $klines = $this->makeKlines(40);
        $result = $this->service->computeMacd($klines);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('macd', $result[0]);
        $this->assertArrayHasKey('signal', $result[0]);
        $this->assertArrayHasKey('histogram', $result[0]);
    }

    public function test_taker_ratio_returns_for_all_klines(): void
    {
        $klines = $this->makeKlines(5);
        $result = $this->service->computeTakerRatio($klines);
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('buy_ratio', $result[0]);
        $this->assertArrayHasKey('sell_ratio', $result[0]);
        $this->assertArrayHasKey('buy_volume', $result[0]);
        $this->assertArrayHasKey('sell_volume', $result[0]);
    }

    public function test_taker_ratio_sums_to_one(): void
    {
        $klines = $this->makeKlines(5);
        $result = $this->service->computeTakerRatio($klines);
        foreach ($result as $r) {
            $this->assertEqualsWithDelta(1.0, $r['buy_ratio'] + $r['sell_ratio'], 0.001);
        }
    }

    public function test_compute_dispatches_correctly(): void
    {
        $klines = $this->makeKlines(5);
        $result = $this->service->compute($klines, ['taker', 'rsi']);
        $this->assertArrayHasKey('taker', $result);
        $this->assertArrayHasKey('rsi', $result);
        $this->assertCount(5, $result['taker']);
        $this->assertEmpty($result['rsi']); // Not enough data for RSI
    }

    public function test_compute_unknown_indicator_returns_empty(): void
    {
        $klines = $this->makeKlines(5);
        $result = $this->service->compute($klines, ['unknown']);
        $this->assertArrayHasKey('unknown', $result);
        $this->assertEmpty($result['unknown']);
    }
}
