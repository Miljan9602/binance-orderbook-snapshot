@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Futures')

@section('meta')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('content')
    {{-- Pill nav --}}
    <div class="mb-4 pill-nav inline-flex">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-gray-400">&larr; Dashboard</a>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-gray-400">Live Orderbook</a>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-gray-400">Analytics</a>
        <span class="pill-active-violet">Futures</span>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-gray-400">Klines</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-violet-400 font-normal">Futures</span></h1>
        @if($tradingPair->futures_symbol)
            <span class="text-xs text-gray-600 font-mono">{{ strtoupper($tradingPair->futures_symbol) }}</span>
        @endif
        <div class="text-xs text-gray-600">Auto-refresh 1s</div>
    </div>

    @if(!$tradingPair->futures_symbol)
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg mb-1">No futures symbol configured</p>
            <p class="text-sm">Set <code class="text-gray-400 bg-white/[0.05] px-2 py-0.5 rounded">futures_symbol</code> on this trading pair to enable futures data.</p>
        </div>
    @else
        {{-- Section 1: Live Futures Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Mark Price</div>
                <div id="mark-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Index Price</div>
                <div id="index-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Funding Rate</div>
                <div id="funding-rate" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Next Funding</div>
                <div id="next-funding" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Open Interest</div>
                <div id="open-interest" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="glass-card accent-bar-violet p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Ann. Funding</div>
                <div id="ann-funding" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
        </div>

        {{-- Spot vs Futures comparison --}}
        <div class="gradient-border mb-8" style="background: linear-gradient(135deg, rgba(139,92,246,0.2), transparent 50%, rgba(16,185,129,0.2));">
            <div class="p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Spot vs Futures</div>
                <div class="flex items-center gap-8">
                    <div>
                        <span class="text-xs text-gray-500 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Spot
                        </span>
                        <span id="spot-price" class="font-mono tabular-nums text-sm text-gray-200 ml-0 mt-1 block">-</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>Mark
                        </span>
                        <span id="mark-price-cmp" class="font-mono tabular-nums text-sm text-gray-200 ml-0 mt-1 block">-</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Premium</span>
                        <span id="premium" class="font-mono tabular-nums text-sm ml-0 mt-1 block">-</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Real-Time Futures Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Funding Rate History</div>
                <div style="height: 200px;"><canvas id="chart-funding"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Open Interest Trend</div>
                <div style="height: 200px;"><canvas id="chart-oi"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Spot vs Futures Premium (%)</div>
                <div style="height: 200px;"><canvas id="chart-premium"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Liquidation Volume (30m)</div>
                <div style="height: 200px;"><canvas id="chart-liq-volume"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Mark vs Index Price</div>
                <div style="height: 200px;"><canvas id="chart-mark-index"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">OI Delta</div>
                <div style="height: 200px;"><canvas id="chart-oi-delta"></canvas></div>
            </div>
        </div>

        {{-- Section 2: Recent Liquidations --}}
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-lg font-semibold">Recent Liquidations</h2>
                <span class="text-xs text-gray-600">Auto-refresh 2s, latest 50</span>
            </div>
            <div class="glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
                                <th class="text-left px-4 py-3 font-medium">Time</th>
                                <th class="text-left px-4 py-3 font-medium">Side</th>
                                <th class="text-right px-4 py-3 font-medium">Price</th>
                                <th class="text-right px-4 py-3 font-medium">Quantity</th>
                                <th class="text-right px-4 py-3 font-medium">Avg Price</th>
                                <th class="text-right px-4 py-3 font-medium">Notional</th>
                                <th class="text-left px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="liquidations-body">
                            <tr><td colspan="6" class="text-center py-8 text-xs text-gray-600">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section 3: Futures Metrics History --}}
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-lg font-semibold">Futures Metrics History</h2>
                <span class="text-xs text-gray-600">{{ number_format($history->total()) }} records</span>
            </div>

            <form method="GET" action="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="mb-4">
                @if(request('oi_from'))<input type="hidden" name="oi_from" value="{{ request('oi_from') }}">@endif
                @if(request('oi_to'))<input type="hidden" name="oi_to" value="{{ request('oi_to') }}">@endif
                @if(request('oi_page'))<input type="hidden" name="oi_page" value="{{ request('oi_page') }}">@endif
                <div class="glass-card p-4">
                    <div class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="datetime-local" name="history_from" value="{{ request('history_from') }}"
                                class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-violet-500/30 focus:border-violet-500/30" step="1">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="datetime-local" name="history_to" value="{{ request('history_to') }}"
                                class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-violet-500/30 focus:border-violet-500/30" step="1">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-violet-500 to-violet-400 hover:from-violet-400 hover:to-violet-300 text-gray-900 font-semibold rounded-lg text-sm transition-all shadow-lg shadow-violet-500/20">Filter</button>
                            @if(request()->hasAny(['history_from', 'history_to']))
                                <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="px-4 py-1.5 border border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg text-sm transition-colors">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if($history->isEmpty())
                <div class="text-center py-10 text-gray-600 text-sm">No futures metrics history yet.</div>
            @else
                <div class="glass-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
                                    <th class="text-left px-4 py-3 font-medium">Time</th>
                                    <th class="text-right px-4 py-3 font-medium">Mark Price</th>
                                    <th class="text-right px-4 py-3 font-medium">Index Price</th>
                                    <th class="text-right px-4 py-3 font-medium">Funding Rate</th>
                                    <th class="text-left px-4 py-3 font-medium">Next Funding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $row)
                                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">
                                        <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->received_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->mark_price, 4) }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->index_price, 4) }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->funding_rate >= 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                            {{ number_format((float) $row->funding_rate * 100, 4) }}%
                                        </td>
                                        <td class="px-4 py-2.5 font-mono tabular-nums text-gray-400 whitespace-nowrap">{{ $row->next_funding_time?->format('H:i:s') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $history->links('admin.trading-pairs.partials.pagination') }}
                </div>
            @endif
        </div>

        {{-- Section 4: Open Interest History --}}
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-lg font-semibold">Open Interest History</h2>
                <span class="text-xs text-gray-600">{{ number_format($oiHistory->total()) }} records</span>
            </div>

            <form method="GET" action="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="mb-4">
                @if(request('history_from'))<input type="hidden" name="history_from" value="{{ request('history_from') }}">@endif
                @if(request('history_to'))<input type="hidden" name="history_to" value="{{ request('history_to') }}">@endif
                @if(request('history_page'))<input type="hidden" name="history_page" value="{{ request('history_page') }}">@endif
                <div class="glass-card p-4">
                    <div class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="datetime-local" name="oi_from" value="{{ request('oi_from') }}"
                                class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-violet-500/30 focus:border-violet-500/30" step="1">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="datetime-local" name="oi_to" value="{{ request('oi_to') }}"
                                class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-violet-500/30 focus:border-violet-500/30" step="1">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-violet-500 to-violet-400 hover:from-violet-400 hover:to-violet-300 text-gray-900 font-semibold rounded-lg text-sm transition-all shadow-lg shadow-violet-500/20">Filter</button>
                            @if(request()->hasAny(['oi_from', 'oi_to']))
                                <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="px-4 py-1.5 border border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg text-sm transition-colors">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if($oiHistory->isEmpty())
                <div class="text-center py-10 text-gray-600 text-sm">No open interest data yet. Run <code class="text-gray-400 bg-white/[0.05] px-2 py-0.5 rounded">php artisan binance:fetch-open-interest</code> or wait for the scheduler.</div>
            @else
                <div class="glass-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
                                    <th class="text-left px-4 py-3 font-medium">Time</th>
                                    <th class="text-right px-4 py-3 font-medium">Open Interest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oiHistory as $row)
                                    <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">
                                        <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->timestamp->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->open_interest, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $oiHistory->links('admin.trading-pairs.partials.pagination') }}
                </div>
            @endif
        </div>
    @endif
@endsection

@section('scripts')
@if($tradingPair->futures_symbol)
<script>
var DATA_URL = '{{ route("admin.trading-pairs.futures-data", $tradingPair) }}';
function fmt(n, d) { return n != null ? Number(n).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d}) : '-'; }

// Chart.js defaults for Deep Space theme
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.font.size = 10;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(13,19,33,0.95)';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

var chartOpts = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 0 },
    plugins: { legend: { display: false } },
    scales: {
        x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
        y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
    }
};

// Funding Rate chart — per-point color: green when negative, red when positive
var chartFunding = new Chart(document.getElementById('chart-funding'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Funding Rate',
            data: [],
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: false,
            segment: {
                borderColor: function(ctx) {
                    var v0 = ctx.p0.parsed.y, v1 = ctx.p1.parsed.y;
                    return (v0 >= 0 && v1 >= 0) ? '#f43f5e' : (v0 < 0 && v1 < 0) ? '#10b981' : '#a78bfa';
                }
            },
            borderColor: '#a78bfa',
        }]
    },
    options: chartOpts
});

// OI Trend chart — line with gradient fill
var chartOi = new Chart(document.getElementById('chart-oi'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Open Interest',
            data: [],
            borderColor: '#60a5fa',
            backgroundColor: function(context) {
                var chart = context.chart;
                var ctx = chart.ctx;
                var area = chart.chartArea;
                if (!area) return 'rgba(96, 165, 250, 0.1)';
                var gradient = ctx.createLinearGradient(0, area.top, 0, area.bottom);
                gradient.addColorStop(0, 'rgba(96, 165, 250, 0.15)');
                gradient.addColorStop(1, 'rgba(96, 165, 250, 0.0)');
                return gradient;
            },
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        }]
    },
    options: chartOpts
});

// Premium chart — line with zero line
var premiumOpts = JSON.parse(JSON.stringify(chartOpts));
premiumOpts.plugins = {
    legend: { display: false },
    annotation: undefined
};
premiumOpts.scales.y.ticks = { maxTicksLimit: 5, callback: function(v) { return v.toFixed(4) + '%'; } };

var chartPremium = new Chart(document.getElementById('chart-premium'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Premium %',
            data: [],
            borderColor: '#a78bfa',
            backgroundColor: function(context) {
                var chart = context.chart;
                var ctx = chart.ctx;
                var area = chart.chartArea;
                if (!area) return 'rgba(167, 139, 250, 0.08)';
                var gradient = ctx.createLinearGradient(0, area.top, 0, area.bottom);
                gradient.addColorStop(0, 'rgba(167, 139, 250, 0.15)');
                gradient.addColorStop(1, 'rgba(167, 139, 250, 0.0)');
                return gradient;
            },
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        }]
    },
    options: premiumOpts
});

// Mark vs Index Price dual-line chart
var chartMarkIndex = new Chart(document.getElementById('chart-mark-index'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            { label: 'Mark Price', data: [], borderColor: '#a78bfa', borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false },
            { label: 'Index Price', data: [], borderColor: '#60a5fa', borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
        }
    }
});

// OI Delta bar chart (green positive, red negative)
var chartOiDelta = new Chart(document.getElementById('chart-oi-delta'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'OI Delta', data: [], backgroundColor: [], borderWidth: 0 }] },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: false } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
        }
    }
});

// Liquidation Volume bar chart
var chartLiqVol = new Chart(document.getElementById('chart-liq-volume'), {
    type: 'bar',
    data: {
        labels: [],
        datasets: [
            { label: 'Buy Liq', data: [], backgroundColor: 'rgba(16, 185, 129, 0.7)', borderWidth: 0 },
            { label: 'Sell Liq', data: [], backgroundColor: 'rgba(244, 63, 94, 0.7)', borderWidth: 0 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false }, stacked: false },
            y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' }, stacked: false }
        }
    }
});

function refreshAll() {
    fetch(DATA_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var f = d.futures;
            if (f) {
                document.getElementById('mark-price').textContent = fmt(f.mark_price, 4);
                document.getElementById('index-price').textContent = fmt(f.index_price, 4);

                var frEl = document.getElementById('funding-rate');
                var frVal = f.funding_rate * 100;
                frEl.textContent = fmt(frVal, 4) + '%';
                frEl.className = 'text-lg font-mono tabular-nums ' + (f.funding_rate >= 0 ? 'text-rose-400' : 'text-emerald-400');

                if (f.next_funding_time) {
                    var diff = Math.max(0, Math.floor((new Date(f.next_funding_time) - new Date()) / 1000));
                    var h = Math.floor(diff / 3600);
                    var m = Math.floor((diff % 3600) / 60);
                    var s = diff % 60;
                    document.getElementById('next-funding').textContent = h + 'h ' + m + 'm ' + s + 's';
                }

                document.getElementById('mark-price-cmp').textContent = fmt(f.mark_price, 4);
            }

            if (d.spot_price !== null) {
                document.getElementById('spot-price').textContent = fmt(d.spot_price, 4);
            }

            if (f && d.spot_price !== null && d.spot_price > 0) {
                var premium = ((f.mark_price - d.spot_price) / d.spot_price) * 100;
                var premEl = document.getElementById('premium');
                premEl.textContent = (premium >= 0 ? '+' : '') + fmt(premium, 4) + '%';
                premEl.className = 'font-mono tabular-nums text-sm ml-0 mt-1 block ' + (premium >= 0 ? 'text-emerald-400' : 'text-rose-400');
            }

            if (d.open_interest !== null) {
                document.getElementById('open-interest').textContent = fmt(d.open_interest, 2);
            }

            // Update liquidations table
            var liqs = d.liquidations;
            var body = document.getElementById('liquidations-body');
            if (!liqs || liqs.length === 0) {
                body.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-xs text-gray-600">No liquidations yet</td></tr>';
            } else {
                var html = '';
                for (var i = 0; i < liqs.length; i++) {
                    var l = liqs[i];
                    var sideBadge = l.side === 'BUY'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400">' + l.side + '</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400">' + l.side + '</span>';
                    html += '<tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">' +
                        '<td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">' + l.time + '</td>' +
                        '<td class="px-4 py-2.5">' + sideBadge + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(l.price, 4) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(l.quantity, 2) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">' + fmt(l.avg_price, 4) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(l.notional, 2) + '</td>' +
                        '<td class="px-4 py-2.5 text-xs text-gray-500">' + l.order_status + '</td>' +
                    '</tr>';
                }
                body.innerHTML = html;
            }

            // Update charts
            if (f) {
                var annFunding = f.funding_rate * 3 * 365 * 100;
                var annEl = document.getElementById('ann-funding');
                annEl.textContent = fmt(annFunding, 2) + '%';
                annEl.className = 'text-lg font-mono tabular-nums ' + (annFunding >= 0 ? 'text-rose-400' : 'text-emerald-400');
            }

            if (d.chart_funding && d.chart_funding.length > 0) {
                chartFunding.data.labels = d.chart_funding.map(function(r) { return r.time; });
                chartFunding.data.datasets[0].data = d.chart_funding.map(function(r) { return r.funding_rate * 100; });
                chartFunding.update('none');

                // Mark vs Index chart
                chartMarkIndex.data.labels = d.chart_funding.map(function(r) { return r.time; });
                chartMarkIndex.data.datasets[0].data = d.chart_funding.map(function(r) { return r.mark_price; });
                chartMarkIndex.data.datasets[1].data = d.chart_funding.map(function(r) { return r.index_price; });
                chartMarkIndex.update('none');
            }

            if (d.chart_oi && d.chart_oi.length > 0) {
                chartOi.data.labels = d.chart_oi.map(function(r) { return r.time; });
                chartOi.data.datasets[0].data = d.chart_oi.map(function(r) { return r.open_interest; });
                chartOi.update('none');

                // OI Delta chart
                chartOiDelta.data.labels = d.chart_oi.map(function(r) { return r.time; });
                var oiDeltaData = d.chart_oi.map(function(r) { return r.oi_delta; });
                chartOiDelta.data.datasets[0].data = oiDeltaData;
                chartOiDelta.data.datasets[0].backgroundColor = oiDeltaData.map(function(v) { return v >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(244, 63, 94, 0.7)'; });
                chartOiDelta.update('none');
            }

            if (d.chart_premium && d.chart_premium.length > 0) {
                chartPremium.data.labels = d.chart_premium.map(function(r) { return r.time; });
                chartPremium.data.datasets[0].data = d.chart_premium.map(function(r) { return r.premium; });
                chartPremium.update('none');
            }

            if (d.chart_liquidations && d.chart_liquidations.length > 0) {
                chartLiqVol.data.labels = d.chart_liquidations.map(function(r) { return r.time; });
                chartLiqVol.data.datasets[0].data = d.chart_liquidations.map(function(r) { return r.buy_qty; });
                chartLiqVol.data.datasets[1].data = d.chart_liquidations.map(function(r) { return r.sell_qty; });
                chartLiqVol.update('none');
            }
        })
        .catch(function() {});
}

refreshAll();
setInterval(refreshAll, 2000);
</script>
@endif
@endsection
