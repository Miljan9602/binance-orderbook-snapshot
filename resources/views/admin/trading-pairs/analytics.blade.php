@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Analytics')

@section('meta')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('content')
    {{-- Back link --}}
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">&larr; Dashboard</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Live Orderbook</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Futures</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Klines</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">Analytics</span></h1>
        <div class="text-xs text-gray-600">Auto-refresh 1s</div>
    </div>

    {{-- Section 1: Live Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Bid Volume</div>
            <div id="bid-volume" class="text-lg font-mono tabular-nums text-green-400">-</div>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Ask Volume</div>
            <div id="ask-volume" class="text-lg font-mono tabular-nums text-red-400">-</div>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Imbalance</div>
            <div id="imbalance" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Mid Price</div>
            <div id="mid-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Weighted Mid</div>
            <div id="weighted-mid" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Spread (bps)</div>
            <div id="spread-bps" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
    </div>

    {{-- Imbalance Bar --}}
    <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 mb-6">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Orderbook Imbalance</div>
        <div class="mb-2">
            <div id="imbalance-bar" class="h-1.5 rounded-full" style="background: #374151;"></div>
        </div>
        <div class="flex justify-between text-xs">
            <span id="imb-ask" class="text-red-400">-</span>
            <span id="imb-val" class="text-gray-500">-</span>
            <span id="imb-bid" class="text-green-400">-</span>
        </div>
    </div>

    {{-- Section: Real-Time Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        {{-- Mid Price Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Mid Price</div>
            <div style="height: 200px;"><canvas id="chart-mid-price"></canvas></div>
        </div>

        {{-- Imbalance Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Orderbook Imbalance</div>
            <div style="height: 200px;"><canvas id="chart-imbalance"></canvas></div>
        </div>

        {{-- Spread BPS Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Spread (BPS)</div>
            <div style="height: 200px;"><canvas id="chart-spread"></canvas></div>
        </div>

        {{-- Bid/Ask Volume Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Bid vs Ask Volume</div>
            <div style="height: 200px;"><canvas id="chart-volume"></canvas></div>
        </div>

        {{-- CVD Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Cumulative Volume Delta (CVD)</div>
            <div style="height: 200px;"><canvas id="chart-cvd"></canvas></div>
        </div>

        {{-- Buy/Sell Volume Chart --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Buy vs Sell Volume (1m)</div>
            <div style="height: 200px;"><canvas id="chart-buysell"></canvas></div>
        </div>
    </div>

    {{-- Section 2: Orderbook Metrics History --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="text-lg font-semibold">Orderbook Metrics History</h2>
            <span class="text-xs text-gray-600">{{ number_format($metrics->total()) }} records</span>
        </div>

        <form method="GET" action="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="mb-4">
            @if(request('agg_from'))<input type="hidden" name="agg_from" value="{{ request('agg_from') }}">@endif
            @if(request('agg_to'))<input type="hidden" name="agg_to" value="{{ request('agg_to') }}">@endif
            @if(request('agg_page'))<input type="hidden" name="agg_page" value="{{ request('agg_page') }}">@endif
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input type="datetime-local" name="metrics_from" value="{{ request('metrics_from') }}"
                            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input type="datetime-local" name="metrics_to" value="{{ request('metrics_to') }}"
                            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                        @if(request()->hasAny(['metrics_from', 'metrics_to']))
                            <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="px-4 py-1.5 border border-gray-700 text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @if($metrics->isEmpty())
            <div class="text-center py-10 text-gray-600 text-sm">No orderbook metrics yet. Data appears after the WebSocket starts streaming.</div>
        @else
            <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-4 py-3 font-medium">Time</th>
                                <th class="text-right px-4 py-3 font-medium">Bid Vol</th>
                                <th class="text-right px-4 py-3 font-medium">Ask Vol</th>
                                <th class="text-right px-4 py-3 font-medium">Imbalance</th>
                                <th class="text-right px-4 py-3 font-medium">Mid Price</th>
                                <th class="text-right px-4 py-3 font-medium">Spread BPS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metrics as $row)
                                <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                                    <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->received_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-green-400">{{ number_format((float) $row->bid_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-red-400">{{ number_format((float) $row->ask_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->imbalance >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ number_format((float) $row->imbalance, 4) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->mid_price, 4) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format((float) $row->spread_bps, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">
                {{ $metrics->links('admin.trading-pairs.partials.pagination') }}
            </div>
        @endif
    </div>

    {{-- Section 3: Trade Aggregates --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="text-lg font-semibold">Trade Aggregates</h2>
            <span class="text-xs text-gray-600">{{ number_format($aggregates->total()) }} records</span>
        </div>

        <form method="GET" action="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="mb-4">
            @if(request('metrics_from'))<input type="hidden" name="metrics_from" value="{{ request('metrics_from') }}">@endif
            @if(request('metrics_to'))<input type="hidden" name="metrics_to" value="{{ request('metrics_to') }}">@endif
            @if(request('metrics_page'))<input type="hidden" name="metrics_page" value="{{ request('metrics_page') }}">@endif
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input type="datetime-local" name="agg_from" value="{{ request('agg_from') }}"
                            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input type="datetime-local" name="agg_to" value="{{ request('agg_to') }}"
                            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                        @if(request()->hasAny(['agg_from', 'agg_to']))
                            <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="px-4 py-1.5 border border-gray-700 text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @if($aggregates->isEmpty())
            <div class="text-center py-10 text-gray-600 text-sm">No trade aggregates yet. Run <code class="text-gray-400 bg-gray-800 px-2 py-0.5 rounded">php artisan trades:aggregate</code> or wait for the scheduler.</div>
        @else
            <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-4 py-3 font-medium">Period</th>
                                <th class="text-right px-4 py-3 font-medium">VWAP</th>
                                <th class="text-right px-4 py-3 font-medium">Buy Vol</th>
                                <th class="text-right px-4 py-3 font-medium">Sell Vol</th>
                                <th class="text-right px-4 py-3 font-medium">CVD</th>
                                <th class="text-right px-4 py-3 font-medium">Trades</th>
                                <th class="text-right px-4 py-3 font-medium">Avg Size</th>
                                <th class="text-right px-4 py-3 font-medium">Max Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aggregates as $row)
                                <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                                    <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->period_start->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->vwap, 4) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-green-400">{{ number_format((float) $row->buy_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-red-400">{{ number_format((float) $row->sell_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->cvd >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ number_format((float) $row->cvd, 2) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format($row->trade_count) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format((float) $row->avg_trade_size, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format((float) $row->max_trade_size, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">
                {{ $aggregates->links('admin.trading-pairs.partials.pagination') }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
var DATA_URL = '{{ route("admin.trading-pairs.analytics-data", $tradingPair) }}';
function fmt(n, d) { return n != null ? Number(n).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d}) : '-'; }

// Chart.js defaults for dark theme
Chart.defaults.color = '#9ca3af';
Chart.defaults.borderColor = 'rgba(75, 85, 99, 0.3)';
Chart.defaults.font.family = "'SF Mono', 'Fira Code', monospace";
Chart.defaults.font.size = 10;

var MAX_POINTS = 60;

function makeLineChart(canvasId, label, color, fillColor) {
    return new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: label,
                data: [],
                borderColor: color,
                backgroundColor: fillColor || 'transparent',
                borderWidth: 1.5,
                pointRadius: 0,
                tension: 0.3,
                fill: !!fillColor,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            plugins: { legend: { display: false } },
            scales: {
                x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(75, 85, 99, 0.2)' } }
            }
        }
    });
}

function makeDualLineChart(canvasId, label1, color1, label2, color2) {
    return new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: label1, data: [], borderColor: color1, borderWidth: 1.5, pointRadius: 0, tension: 0.3, fill: false },
                { label: label2, data: [], borderColor: color2, borderWidth: 1.5, pointRadius: 0, tension: 0.3, fill: false }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
            scales: {
                x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(75, 85, 99, 0.2)' } }
            }
        }
    });
}

function makeBarChart(canvasId, label1, color1, label2, color2) {
    return new Chart(document.getElementById(canvasId), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                { label: label1, data: [], backgroundColor: color1, borderWidth: 0 },
                { label: label2, data: [], backgroundColor: color2, borderWidth: 0 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
            scales: {
                x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false }, stacked: false },
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(75, 85, 99, 0.2)' }, stacked: false }
            }
        }
    });
}

// Initialize charts
var chartMidPrice = makeLineChart('chart-mid-price', 'Mid Price', '#facc15', 'rgba(250, 204, 21, 0.08)');
var chartImbalance = makeLineChart('chart-imbalance', 'Imbalance', '#60a5fa', 'rgba(96, 165, 250, 0.08)');
var chartSpread = makeLineChart('chart-spread', 'Spread BPS', '#f97316', 'rgba(249, 115, 22, 0.08)');
var chartVolume = makeDualLineChart('chart-volume', 'Bid Vol', '#22c55e', 'Ask Vol', '#ef4444');
var chartCvd = makeLineChart('chart-cvd', 'CVD', '#a78bfa', 'rgba(167, 139, 250, 0.08)');
var chartBuySell = makeBarChart('chart-buysell', 'Buy Vol', 'rgba(34, 197, 94, 0.7)', 'Sell Vol', 'rgba(239, 68, 68, 0.7)');

function updateChart(chart, labels, data, datasetIndex) {
    chart.data.labels = labels;
    if (datasetIndex !== undefined) {
        chart.data.datasets[datasetIndex].data = data;
    } else {
        chart.data.datasets[0].data = data;
    }
    chart.update('none');
}

function refresh() {
    fetch(DATA_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d) return;

            // Update cards
            document.getElementById('bid-volume').textContent = fmt(d.bid_volume, 2);
            document.getElementById('ask-volume').textContent = fmt(d.ask_volume, 2);

            var imbEl = document.getElementById('imbalance');
            imbEl.textContent = fmt(d.imbalance, 4);
            imbEl.className = 'text-lg font-mono tabular-nums ' + (d.imbalance >= 0 ? 'text-green-400' : 'text-red-400');

            document.getElementById('mid-price').textContent = fmt(d.mid_price, 4);
            document.getElementById('weighted-mid').textContent = fmt(d.weighted_mid_price, 4);
            document.getElementById('spread-bps').textContent = fmt(d.spread_bps, 2);

            // Imbalance bar
            var total = d.bid_volume + d.ask_volume;
            if (total > 0) {
                var askPct = (d.ask_volume / total) * 100;
                var bidPct = (d.bid_volume / total) * 100;
                var bar = document.getElementById('imbalance-bar');
                bar.style.background = 'linear-gradient(90deg, #ef4444 0%, #ef4444 ' + askPct.toFixed(1) + '%, #374151 ' + askPct.toFixed(1) + '%, #374151 ' + (100 - bidPct).toFixed(1) + '%, #22c55e ' + (100 - bidPct).toFixed(1) + '%, #22c55e 100%)';
                document.getElementById('imb-ask').textContent = fmt(askPct, 1) + '% Asks';
                document.getElementById('imb-bid').textContent = fmt(bidPct, 1) + '% Bids';
                var imbValEl = document.getElementById('imb-val');
                imbValEl.textContent = (d.imbalance >= 0 ? '+' : '') + fmt(d.imbalance * 100, 1) + '%';
                imbValEl.className = d.imbalance >= 0 ? 'text-green-400' : 'text-red-400';
            }

            // Update orderbook metric charts
            if (d.chart_metrics && d.chart_metrics.length > 0) {
                var labels = d.chart_metrics.map(function(m) { return m.time; });
                var midData = d.chart_metrics.map(function(m) { return m.mid_price; });
                var imbData = d.chart_metrics.map(function(m) { return m.imbalance; });
                var spreadData = d.chart_metrics.map(function(m) { return m.spread_bps; });
                var bidVolData = d.chart_metrics.map(function(m) { return m.bid_volume; });
                var askVolData = d.chart_metrics.map(function(m) { return m.ask_volume; });

                updateChart(chartMidPrice, labels, midData);
                updateChart(chartImbalance, labels, imbData);
                updateChart(chartSpread, labels, spreadData);

                chartVolume.data.labels = labels;
                chartVolume.data.datasets[0].data = bidVolData;
                chartVolume.data.datasets[1].data = askVolData;
                chartVolume.update('none');
            }

            // Update trade aggregate charts
            if (d.chart_aggregates && d.chart_aggregates.length > 0) {
                var aggLabels = d.chart_aggregates.map(function(a) { return a.time; });
                var cvdData = d.chart_aggregates.map(function(a) { return a.cvd; });
                var buyData = d.chart_aggregates.map(function(a) { return a.buy_volume; });
                var sellData = d.chart_aggregates.map(function(a) { return a.sell_volume; });

                // CVD chart - color segments
                var cvdColors = cvdData.map(function(v) { return v >= 0 ? '#22c55e' : '#ef4444'; });
                chartCvd.data.labels = aggLabels;
                chartCvd.data.datasets[0].data = cvdData;
                chartCvd.data.datasets[0].segment = {
                    borderColor: function(ctx) {
                        return ctx.p0.parsed.y >= 0 && ctx.p1.parsed.y >= 0 ? '#22c55e' : '#ef4444';
                    }
                };
                chartCvd.data.datasets[0].borderColor = '#a78bfa';
                chartCvd.data.datasets[0].backgroundColor = 'rgba(167, 139, 250, 0.08)';
                chartCvd.update('none');

                chartBuySell.data.labels = aggLabels;
                chartBuySell.data.datasets[0].data = buyData;
                chartBuySell.data.datasets[1].data = sellData;
                chartBuySell.update('none');
            }
        })
        .catch(function() {});
}

refresh();
setInterval(refresh, 1000);
</script>
@endsection
