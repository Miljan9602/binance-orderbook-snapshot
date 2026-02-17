@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Klines')

@section('meta')
<script src="https://unpkg.com/lightweight-charts@4/dist/lightweight-charts.standalone.production.js"></script>
@endsection

@section('content')
    {{-- Pill Navigation --}}
    <div class="mb-4 pill-nav inline-flex">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-gray-400">&larr; Dashboard</a>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-gray-400">Live Orderbook</a>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-gray-400">Analytics</a>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-gray-400">Futures</a>
        <span class="pill-active">Klines</span>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">Klines</span></h1>
        <span class="text-xs text-gray-600">{{ number_format($klines->total()) }} candles</span>
    </div>

    {{-- Interval Tabs --}}
    <div class="pill-nav inline-flex mb-4">
        @foreach(['1m', '5m', '15m', '1h'] as $tab)
            <a href="{{ route('admin.trading-pairs.klines', array_merge(['tradingPair' => $tradingPair, 'interval' => $tab], request()->only(['from', 'to']))) }}"
                class="{{ $interval === $tab ? 'pill-active' : 'text-gray-400' }}">
                {{ $tab }}
            </a>
        @endforeach
    </div>

    {{-- Indicator Toggle Buttons --}}
    <div class="flex items-center gap-2 mb-6 flex-wrap">
        <span class="text-xs text-gray-500 uppercase tracking-wider mr-2">Indicators:</span>
        <button id="btn-ema" onclick="toggleIndicator('ema')" class="indicator-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05]">EMA 20/50</button>
        <button id="btn-bb" onclick="toggleIndicator('bb')" class="indicator-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05]">BB</button>
        <button id="btn-rsi" onclick="toggleIndicator('rsi')" class="indicator-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05]">RSI</button>
        <button id="btn-macd" onclick="toggleIndicator('macd')" class="indicator-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05]">MACD</button>
        <button id="btn-taker" onclick="toggleIndicator('taker')" class="indicator-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200 border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05]">Taker Buy/Sell</button>
    </div>

    {{-- Candlestick Chart --}}
    <div class="glass-card p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs text-gray-500 uppercase tracking-[0.15em]">{{ $interval }} Candlestick Chart</div>
            <div class="text-xs text-gray-600">Auto-refresh 2s</div>
        </div>
        <div id="candlestick-chart" style="height: 500px;"></div>
    </div>

    {{-- RSI Panel --}}
    <div id="rsi-panel" class="glass-card p-4 mb-4" style="display:none;">
        <div class="text-xs text-gray-500 uppercase tracking-[0.15em] mb-2">RSI (14)</div>
        <div id="rsi-chart" style="height: 150px;"></div>
    </div>

    {{-- MACD Panel --}}
    <div id="macd-panel" class="glass-card p-4 mb-4" style="display:none;">
        <div class="text-xs text-gray-500 uppercase tracking-[0.15em] mb-2">MACD (12, 26, 9)</div>
        <div id="macd-chart" style="height: 180px;"></div>
    </div>

    {{-- Taker Buy/Sell Panel --}}
    <div id="taker-panel" class="glass-card p-4 mb-4" style="display:none;">
        <div class="text-xs text-gray-500 uppercase tracking-[0.15em] mb-2">Taker Buy / Sell Volume</div>
        <div id="taker-chart" style="height: 150px;"></div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="mb-6">
        <input type="hidden" name="interval" value="{{ $interval }}">
        <div class="glass-card p-4">
            <div class="flex items-end gap-4 flex-wrap">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input type="datetime-local" name="from" value="{{ request('from') }}"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30 transition-colors" step="1">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input type="datetime-local" name="to" value="{{ request('to') }}"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30 transition-colors" step="1">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-cyan-500 to-cyan-400 shadow-lg shadow-cyan-500/20 text-gray-900 font-semibold rounded-lg text-sm transition-colors hover:shadow-cyan-500/30">Filter</button>
                    @if(request()->hasAny(['from', 'to']))
                        <a href="{{ route('admin.trading-pairs.klines', ['tradingPair' => $tradingPair, 'interval' => $interval]) }}" class="px-4 py-1.5 border border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg text-sm transition-colors">Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Klines Table --}}
    @if($klines->isEmpty())
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg mb-1">No klines for <code class="px-1.5 py-0.5 rounded bg-white/[0.05] text-gray-400">{{ $interval }}</code> interval</p>
            <p class="text-sm">Data will appear once the WebSocket streams <code class="px-1.5 py-0.5 rounded bg-white/[0.05] text-gray-400">{{ $interval }}</code> candles.</p>
        </div>
    @else
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
                            <th class="text-left px-4 py-3 font-medium">Open Time</th>
                            <th class="text-right px-4 py-3 font-medium">Open</th>
                            <th class="text-right px-4 py-3 font-medium">High</th>
                            <th class="text-right px-4 py-3 font-medium">Low</th>
                            <th class="text-right px-4 py-3 font-medium">Close</th>
                            <th class="text-right px-4 py-3 font-medium">Volume</th>
                            <th class="text-right px-4 py-3 font-medium">Quote Vol</th>
                            <th class="text-right px-4 py-3 font-medium">Trades</th>
                            <th class="text-right px-4 py-3 font-medium">Taker Buy Vol</th>
                            <th class="text-center px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($klines as $index => $kline)
                            @php
                                $isOpen = !$kline->is_closed;
                                $isGreen = (float) $kline->close >= (float) $kline->open;
                            @endphp
                            <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors {{ $isOpen ? 'border-l-2 border-l-cyan-500' : '' }} {{ $index % 2 === 1 ? 'bg-white/[0.01]' : '' }}">
                                <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $kline->open_time->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $kline->open, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-emerald-400">{{ number_format((float) $kline->high, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-rose-400">{{ number_format((float) $kline->low, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ $isGreen ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format((float) $kline->close, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format((float) $kline->volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format((float) $kline->quote_volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format($kline->trade_count) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format((float) $kline->taker_buy_volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($isOpen)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-500/15 text-cyan-400 border border-cyan-500/20 inline-flex items-center gap-1.5">
                                            <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-cyan-500"></span></span>
                                            OPEN
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-600">closed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $klines->links('admin.trading-pairs.partials.pagination') }}
        </div>
    @endif
@endsection

@section('scripts')
<script>
var BASE_URL = '{{ route("admin.trading-pairs.klines-data", $tradingPair) }}?interval={{ $interval }}';
var activeIndicators = {};
var chartContainer = document.getElementById('candlestick-chart');

// Main chart
var chart = LightweightCharts.createChart(chartContainer, {
    layout: {
        background: { type: 'solid', color: 'transparent' },
        textColor: '#6b7280',
        fontFamily: "'JetBrains Mono', monospace",
        fontSize: 10,
    },
    grid: {
        vertLines: { color: 'rgba(255,255,255,0.03)' },
        horzLines: { color: 'rgba(255,255,255,0.03)' },
    },
    crosshair: {
        mode: LightweightCharts.CrosshairMode.Normal,
        vertLine: { color: 'rgba(6,182,212,0.3)', style: 0 },
        horzLine: { color: 'rgba(6,182,212,0.3)', style: 0 },
    },
    rightPriceScale: { borderColor: 'rgba(255,255,255,0.06)' },
    timeScale: { borderColor: 'rgba(255,255,255,0.06)', timeVisible: true, secondsVisible: false },
});

var candleSeries = chart.addCandlestickSeries({
    upColor: '#10b981', downColor: '#f43f5e',
    borderDownColor: '#f43f5e', borderUpColor: '#10b981',
    wickDownColor: '#f43f5e', wickUpColor: '#10b981',
});

var volumeSeries = chart.addHistogramSeries({
    priceFormat: { type: 'volume' },
    priceScaleId: 'volume',
});
chart.priceScale('volume').applyOptions({ scaleMargins: { top: 0.8, bottom: 0 } });

// Overlay series (lazy init)
var ema20Series = null, ema50Series = null;
var bbUpperSeries = null, bbMiddleSeries = null, bbLowerSeries = null;

// Sub-charts
var rsiChart = null, rsiSeries = null;
var macdChart = null, macdLineSeries = null, macdSignalSeries = null, macdHistSeries = null;
var takerChart = null, takerBuySeries = null, takerSellSeries = null;

function createSubChart(containerId, height) {
    return LightweightCharts.createChart(document.getElementById(containerId), {
        layout: { background: { type: 'solid', color: 'transparent' }, textColor: '#6b7280', fontFamily: "'JetBrains Mono', monospace", fontSize: 10 },
        grid: { vertLines: { color: 'rgba(255,255,255,0.03)' }, horzLines: { color: 'rgba(255,255,255,0.03)' } },
        crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
        rightPriceScale: { borderColor: 'rgba(255,255,255,0.06)' },
        timeScale: { borderColor: 'rgba(255,255,255,0.06)', timeVisible: true, secondsVisible: false },
        height: height,
    });
}

function syncTimeScales(charts) {
    charts.forEach(function(c, i) {
        c.timeScale().subscribeVisibleLogicalRangeChange(function(range) {
            if (!range) return;
            charts.forEach(function(other, j) {
                if (i !== j) other.timeScale().setVisibleLogicalRange(range);
            });
        });
    });
}

function toggleIndicator(name) {
    activeIndicators[name] = !activeIndicators[name];
    var btn = document.getElementById('btn-' + name);
    if (activeIndicators[name]) {
        btn.classList.add('bg-cyan-500/20', 'text-cyan-400', 'border-cyan-500/40');
        btn.classList.remove('text-gray-400', 'border-white/[0.1]');
    } else {
        btn.classList.remove('bg-cyan-500/20', 'text-cyan-400', 'border-cyan-500/40');
        btn.classList.add('text-gray-400', 'border-white/[0.1]');
    }

    // Show/hide panels
    var panelMap = { rsi: 'rsi-panel', macd: 'macd-panel', taker: 'taker-panel' };
    if (panelMap[name]) {
        document.getElementById(panelMap[name]).style.display = activeIndicators[name] ? 'block' : 'none';
    }

    // Hide overlays when toggled off
    if (!activeIndicators[name]) {
        if (name === 'ema') { if (ema20Series) { chart.removeSeries(ema20Series); ema20Series = null; } if (ema50Series) { chart.removeSeries(ema50Series); ema50Series = null; } }
        if (name === 'bb') { if (bbUpperSeries) { chart.removeSeries(bbUpperSeries); bbUpperSeries = null; } if (bbMiddleSeries) { chart.removeSeries(bbMiddleSeries); bbMiddleSeries = null; } if (bbLowerSeries) { chart.removeSeries(bbLowerSeries); bbLowerSeries = null; } }
    }

    fetchAndUpdate();
}

function getIndicatorParam() {
    var active = Object.keys(activeIndicators).filter(function(k) { return activeIndicators[k]; });
    return active.length > 0 ? '&indicators=' + active.join(',') : '';
}

var lastData = [];
var firstLoad = true;

function fetchAndUpdate() {
    var url = BASE_URL + getIndicatorParam();
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            var data = resp.klines;
            var indicators = resp.indicators || {};

            if (!data || data.length === 0) return;

            var candles = data.map(function(k) {
                return { time: k.time, open: k.open, high: k.high, low: k.low, close: k.close };
            });

            var volumes = data.map(function(k) {
                var buyRatio = k.volume > 0 ? k.taker_buy_volume / k.volume : 0.5;
                return { time: k.time, value: k.volume, color: buyRatio > 0.5 ? 'rgba(16, 185, 129, 0.4)' : 'rgba(244, 63, 94, 0.4)' };
            });

            candleSeries.setData(candles);
            volumeSeries.setData(volumes);

            // EMA overlays
            if (activeIndicators.ema && indicators.ema) {
                if (!ema20Series) { ema20Series = chart.addLineSeries({ color: '#f59e0b', lineWidth: 1, priceLineVisible: false, lastValueVisible: false }); }
                if (!ema50Series) { ema50Series = chart.addLineSeries({ color: '#8b5cf6', lineWidth: 1, priceLineVisible: false, lastValueVisible: false }); }
                ema20Series.setData(indicators.ema.ema20 || []);
                ema50Series.setData(indicators.ema.ema50 || []);
            }

            // BB overlays
            if (activeIndicators.bb && indicators.bb) {
                if (!bbUpperSeries) { bbUpperSeries = chart.addLineSeries({ color: '#3b82f6', lineWidth: 1, lineStyle: 2, priceLineVisible: false, lastValueVisible: false }); }
                if (!bbMiddleSeries) { bbMiddleSeries = chart.addLineSeries({ color: '#3b82f6', lineWidth: 1, lineStyle: 1, priceLineVisible: false, lastValueVisible: false }); }
                if (!bbLowerSeries) { bbLowerSeries = chart.addLineSeries({ color: '#3b82f6', lineWidth: 1, lineStyle: 2, priceLineVisible: false, lastValueVisible: false }); }
                var bbUpper = indicators.bb.map(function(b) { return { time: b.time, value: b.upper }; });
                var bbMiddle = indicators.bb.map(function(b) { return { time: b.time, value: b.middle }; });
                var bbLower = indicators.bb.map(function(b) { return { time: b.time, value: b.lower }; });
                bbUpperSeries.setData(bbUpper);
                bbMiddleSeries.setData(bbMiddle);
                bbLowerSeries.setData(bbLower);
            }

            // RSI sub-chart
            if (activeIndicators.rsi && indicators.rsi && indicators.rsi.length > 0) {
                if (!rsiChart) {
                    rsiChart = createSubChart('rsi-chart', 150);
                    rsiSeries = rsiChart.addLineSeries({ color: '#a78bfa', lineWidth: 2, priceLineVisible: false, lastValueVisible: true });
                    // 70/30 reference lines
                    rsiSeries.createPriceLine({ price: 70, color: 'rgba(244, 63, 94, 0.4)', lineWidth: 1, lineStyle: 2 });
                    rsiSeries.createPriceLine({ price: 30, color: 'rgba(16, 185, 129, 0.4)', lineWidth: 1, lineStyle: 2 });
                }
                rsiSeries.setData(indicators.rsi);
            }

            // MACD sub-chart
            if (activeIndicators.macd && indicators.macd && indicators.macd.length > 0) {
                if (!macdChart) {
                    macdChart = createSubChart('macd-chart', 180);
                    macdLineSeries = macdChart.addLineSeries({ color: '#06b6d4', lineWidth: 2, priceLineVisible: false, lastValueVisible: false });
                    macdSignalSeries = macdChart.addLineSeries({ color: '#f59e0b', lineWidth: 1, priceLineVisible: false, lastValueVisible: false });
                    macdHistSeries = macdChart.addHistogramSeries({ priceLineVisible: false, lastValueVisible: false });
                }
                var macdLine = indicators.macd.map(function(m) { return { time: m.time, value: m.macd }; });
                var signalLine = indicators.macd.map(function(m) { return { time: m.time, value: m.signal }; });
                var histData = indicators.macd.map(function(m) {
                    return { time: m.time, value: m.histogram, color: m.histogram >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(244, 63, 94, 0.7)' };
                });
                macdLineSeries.setData(macdLine);
                macdSignalSeries.setData(signalLine);
                macdHistSeries.setData(histData);
            }

            // Taker Buy/Sell sub-chart
            if (activeIndicators.taker && indicators.taker && indicators.taker.length > 0) {
                if (!takerChart) {
                    takerChart = createSubChart('taker-chart', 150);
                    takerBuySeries = takerChart.addHistogramSeries({ color: 'rgba(16, 185, 129, 0.7)', priceLineVisible: false, lastValueVisible: false });
                    takerSellSeries = takerChart.addHistogramSeries({ color: 'rgba(244, 63, 94, 0.7)', priceLineVisible: false, lastValueVisible: false, priceScaleId: 'sell' });
                }
                var buyData = indicators.taker.map(function(t) { return { time: t.time, value: t.buy_volume }; });
                var sellData = indicators.taker.map(function(t) { return { time: t.time, value: -t.sell_volume }; });
                takerBuySeries.setData(buyData);
                takerSellSeries.setData(sellData);
            }

            // Sync time scales
            var allCharts = [chart];
            if (rsiChart && activeIndicators.rsi) allCharts.push(rsiChart);
            if (macdChart && activeIndicators.macd) allCharts.push(macdChart);
            if (takerChart && activeIndicators.taker) allCharts.push(takerChart);
            if (allCharts.length > 1 && firstLoad) {
                syncTimeScales(allCharts);
            }

            if (firstLoad) {
                chart.timeScale().scrollToRealTime();
                firstLoad = false;
            }
            lastData = data;
        })
        .catch(function() {});
}

fetchAndUpdate();
setInterval(fetchAndUpdate, 2000);

// Resize handler
new ResizeObserver(function() {
    chart.applyOptions({ width: chartContainer.clientWidth });
    if (rsiChart) rsiChart.applyOptions({ width: chartContainer.clientWidth });
    if (macdChart) macdChart.applyOptions({ width: chartContainer.clientWidth });
    if (takerChart) takerChart.applyOptions({ width: chartContainer.clientWidth });
}).observe(chartContainer);
</script>
@endsection
