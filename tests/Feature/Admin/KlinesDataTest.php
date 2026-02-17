<?php

namespace Tests\Feature\Admin;

use App\Models\Kline;
use App\Models\TradingPair;
use Tests\TestCase;

class KlinesDataTest extends TestCase
{
    public function test_returns_ohlcv_structure(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('klines', $data);
        $this->assertArrayHasKey('indicators', $data);
        $this->assertNotEmpty($data['klines']);
        $kline = $data['klines'][0];
        $this->assertArrayHasKey('time', $kline);
        $this->assertArrayHasKey('open', $kline);
        $this->assertArrayHasKey('high', $kline);
        $this->assertArrayHasKey('low', $kline);
        $this->assertArrayHasKey('close', $kline);
        $this->assertArrayHasKey('volume', $kline);
        $this->assertArrayHasKey('quote_volume', $kline);
        $this->assertArrayHasKey('trade_count', $kline);
        $this->assertArrayHasKey('taker_buy_volume', $kline);
        $this->assertArrayHasKey('taker_sell_volume', $kline);
    }

    public function test_limits_to_200(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->count(210)->sequence(
            fn($seq) => ['open_time' => now()->subMinutes($seq->index)]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $this->assertCount(200, $response->json('klines'));
    }

    public function test_filters_by_interval(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'interval' => '1m']);
        Kline::factory()->create(['trading_pair_id' => $pair->id, 'interval' => '5m']);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', [$pair, 'interval' => '5m']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('klines'));
    }

    public function test_invalid_interval_returns_422(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.klines-data', [$pair, 'interval' => 'bad']));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('interval');
    }

    public function test_empty_response(): void
    {
        $pair = TradingPair::factory()->create();

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('klines'));
        $this->assertEmpty($response->json('indicators'));
    }

    public function test_timestamp_is_integer(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $this->assertIsInt($response->json('klines.0.time'));
    }

    public function test_values_are_floats(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $kline = $response->json('klines.0');
        $this->assertIsFloat($kline['open']);
        $this->assertIsFloat($kline['high']);
        $this->assertIsFloat($kline['low']);
        $this->assertIsFloat($kline['close']);
        $this->assertIsFloat($kline['volume']);
        $this->assertIsFloat($kline['quote_volume']);
        $this->assertIsInt($kline['trade_count']);
        $this->assertIsFloat($kline['taker_buy_volume']);
        $this->assertIsFloat($kline['taker_sell_volume']);
    }

    public function test_indicators_returned_when_requested(): void
    {
        $pair = TradingPair::factory()->create();
        // Need enough klines for RSI (15+)
        Kline::factory()->count(30)->sequence(
            fn($seq) => [
                'open_time' => now()->subMinutes(30 - $seq->index),
                'close' => 0.2100 + ($seq->index * 0.001),
            ]
        )->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', [$pair, 'indicators' => 'rsi,taker']));
        $response->assertStatus(200);
        $this->assertArrayHasKey('rsi', $response->json('indicators'));
        $this->assertArrayHasKey('taker', $response->json('indicators'));
        $this->assertNotEmpty($response->json('indicators.rsi'));
        $this->assertNotEmpty($response->json('indicators.taker'));
    }

    public function test_empty_indicators_when_not_requested(): void
    {
        $pair = TradingPair::factory()->create();
        Kline::factory()->create(['trading_pair_id' => $pair->id]);

        $response = $this->getJson(route('admin.trading-pairs.klines-data', $pair));
        $response->assertStatus(200);
        $this->assertEmpty($response->json('indicators'));
    }
}
