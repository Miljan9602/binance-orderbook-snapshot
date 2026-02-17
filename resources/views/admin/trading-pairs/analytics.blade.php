@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Analytics')

@section('meta')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('content')
    {{-- Pill nav --}}
    <div class="mb-4 pill-nav inline-flex">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-gray-400">&larr; Dashboard</a>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-gray-400">Live Orderbook</a>
        <span class="pill-active">Analytics</span>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-gray-400">Futures</a>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-gray-400">Klines</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-2">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">Analytics</span></h1>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
            <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
            LIVE
        </span>
    </div>

    {{-- Market Regime Badge --}}
    <div class="flex items-center gap-3 mb-6">
        <span id="regime-badge" class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-800/50 text-gray-500 border border-white/[0.06] transition-all duration-300">
            ANALYZING...
        </span>
        <span id="regime-confidence" class="text-[10px] text-gray-600 font-mono"></span>
        <span class="text-[10px] text-gray-600">10s</span>
    </div>

    {{-- Section 1: Live Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-7 gap-4 mb-6">
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Bid Volume</div>
            <div id="bid-volume" class="text-lg font-mono tabular-nums text-emerald-400">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Ask Volume</div>
            <div id="ask-volume" class="text-lg font-mono tabular-nums text-rose-400">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Imbalance</div>
            <div id="imbalance" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Mid Price</div>
            <div id="mid-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Weighted Mid</div>
            <div id="weighted-mid" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">Spread (bps)</div>
            <div id="spread-bps" class="text-lg font-mono tabular-nums text-gray-200">-</div>
        </div>
        <div class="glass-card accent-bar-cyan p-4 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-2">VPIN Toxicity</div>
            <div id="vpin-value" class="text-lg font-mono tabular-nums text-gray-500">-</div>
        </div>
    </div>

    {{-- Imbalance Bar --}}
    <div class="glass-card p-4 mb-6">
        <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-3">Orderbook Imbalance</div>
        <div class="mb-2">
            <div id="imbalance-bar" class="h-1.5 rounded-full" style="background: #374151;"></div>
        </div>
        <div class="flex justify-between text-xs">
            <span id="imb-ask" class="text-rose-400">-</span>
            <span id="imb-val" class="text-gray-500">-</span>
            <span id="imb-bid" class="text-emerald-400">-</span>
        </div>
    </div>

    {{-- Section: Real-Time Charts (Bento Grid) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        {{-- Mid Price Chart - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Mid Price</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-mid-price"></canvas></div>
        </div>

        {{-- Imbalance Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Orderbook Imbalance</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-imbalance"></canvas></div>
        </div>

        {{-- Spread BPS Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Spread (BPS)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-spread"></canvas></div>
        </div>

        {{-- Bid/Ask Volume Chart - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Bid vs Ask Volume</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-volume"></canvas></div>
        </div>

        {{-- CVD Chart - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Cumulative Volume Delta (CVD)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-cvd"></canvas></div>
        </div>

        {{-- Running Cumulative CVD - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Running Cumulative CVD</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-cumulative-cvd"></canvas></div>
        </div>

        {{-- Buy/Sell Volume Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Buy vs Sell Volume (1m)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-buysell"></canvas></div>
        </div>

        {{-- Trade Flow Scatter --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Trade Flow (last 100 trades)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-trade-flow"></canvas></div>
        </div>

        {{-- Trade Pressure Bar --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Trade Pressure (1m buy vs sell)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-trade-pressure"></canvas></div>
        </div>

        {{-- Realized Volatility Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Realized Volatility (5-period)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-volatility"></canvas></div>
        </div>

        {{-- Buy/Sell Ratio Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Buy/Sell Ratio</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-buy-sell-ratio"></canvas></div>
        </div>

        {{-- VPIN Time Series - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">VPIN Order Flow Toxicity</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-vpin"></canvas></div>
        </div>

        {{-- Per-Minute Returns Chart --}}
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Per-Minute Returns (%)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-returns"></canvas></div>
        </div>

        {{-- 1m Close Price Chart - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">1m Close Price</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-close-price"></canvas></div>
        </div>

        {{-- Trade Stats Chart - FULL WIDTH --}}
        <div class="lg:col-span-2 glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Trade Stats (trades/min, avg size, max size)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">1s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-trade-stats"></canvas></div>
        </div>
    </div>

    {{-- Cross-Metric Correlations --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="text-lg font-semibold">Cross-Metric Correlations</h2>
            <span class="text-xs text-gray-600">30s</span>
            <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="glass-card p-4">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-3">OI Change (%) vs Price Change (%)</div>
                <div style="height: 200px;"><canvas id="chart-corr-oi-price"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-3">Funding Rate (%) vs Premium (%)</div>
                <div style="height: 200px;"><canvas id="chart-corr-funding-premium"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-3">Volume vs Volatility</div>
                <div style="height: 200px;"><canvas id="chart-corr-vol-volatility"></canvas></div>
            </div>
            <div class="glass-card p-4">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em] mb-3">Imbalance vs Next-Period Price Change (%)</div>
                <div style="height: 200px;"><canvas id="chart-corr-imb-price"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Orderbook Depth Heatmap --}}
    <div class="glass-card p-4 mb-8">
        <div class="flex items-center justify-between mb-3">
            <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Orderbook Depth Heatmap</div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-gray-600">5s</span>
                <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
            </div>
        </div>
        <canvas id="depth-heatmap" style="width: 100%; height: 300px;"></canvas>
    </div>

    {{-- Spread Distribution & Hourly Patterns --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Spread Distribution (BPS)</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">30s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <div style="height: 200px;"><canvas id="chart-spread-dist"></canvas></div>
        </div>
        <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="text-[11px] text-gray-500 uppercase tracking-[0.15em]">Time-of-Day Patterns</div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-600">30s</span>
                    <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                </div>
            </div>
            <canvas id="hourly-heatmap" style="width: 100%; height: 200px;"></canvas>
        </div>
    </div>

    {{-- Large Trades Feed --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="text-lg font-semibold">Large Trades</h2>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                LIVE
            </span>
            <span class="text-xs text-gray-600">2s, last 20</span>
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
                            <th class="text-right px-4 py-3 font-medium">Size Multiple</th>
                        </tr>
                    </thead>
                    <tbody id="large-trades-body">
                        <tr><td colspan="5" class="text-center py-8 text-xs text-gray-600">Waiting for large trades...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Orderbook Walls Feed --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <h2 class="text-lg font-semibold">Orderbook Walls</h2>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                LIVE
            </span>
            <span class="text-xs text-gray-600">2s, last 20</span>
        </div>
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
                            <th class="text-left px-4 py-3 font-medium">Detected</th>
                            <th class="text-left px-4 py-3 font-medium">Side</th>
                            <th class="text-right px-4 py-3 font-medium">Price</th>
                            <th class="text-right px-4 py-3 font-medium">Quantity</th>
                            <th class="text-right px-4 py-3 font-medium">Size Multiple</th>
                            <th class="text-left px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody id="walls-body">
                        <tr><td colspan="6" class="text-center py-8 text-xs text-gray-600">No walls detected yet</td></tr>
                    </tbody>
                </table>
            </div>
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
            <div class="glass-card p-4">
                <div class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input type="datetime-local" name="metrics_from" value="{{ request('metrics_from') }}"
                            class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input type="datetime-local" name="metrics_to" value="{{ request('metrics_to') }}"
                            class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 shadow-lg shadow-cyan-500/20 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                        @if(request()->hasAny(['metrics_from', 'metrics_to']))
                            <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="px-4 py-1.5 border border-white/[0.1] hover:bg-white/[0.05] text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @if($metrics->isEmpty())
            <div class="text-center py-10 text-gray-600 text-sm">No orderbook metrics yet. Data appears after the WebSocket starts streaming.</div>
        @else
            <div class="glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
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
                                <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">
                                    <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->received_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-emerald-400">{{ number_format((float) $row->bid_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-rose-400">{{ number_format((float) $row->ask_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->imbalance >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
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
            <div class="glass-card p-4">
                <div class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From</label>
                        <input type="datetime-local" name="agg_from" value="{{ request('agg_from') }}"
                            class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">To</label>
                        <input type="datetime-local" name="agg_to" value="{{ request('agg_to') }}"
                            class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 shadow-lg shadow-cyan-500/20 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                        @if(request()->hasAny(['agg_from', 'agg_to']))
                            <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="px-4 py-1.5 border border-white/[0.1] hover:bg-white/[0.05] text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @if($aggregates->isEmpty())
            <div class="text-center py-10 text-gray-600 text-sm">No trade aggregates yet. Run <code class="text-gray-400 bg-white/[0.03] px-2 py-0.5 rounded">php artisan trades:aggregate</code> or wait for the scheduler.</div>
        @else
            <div class="glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06] text-[11px] text-gray-500 uppercase tracking-[0.15em]">
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
                                <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">
                                    <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->period_start->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->vwap, 4) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-emerald-400">{{ number_format((float) $row->buy_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums text-rose-400">{{ number_format((float) $row->sell_volume, 2) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->cvd >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
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

// Chart.js defaults for Deep Space theme
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.font.size = 10;

// Global tooltip styling
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(13,19,33,0.95)';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.padding = 10;

var MAX_POINTS = 60;

// Gradient helper for line chart fills
function makeGradient(canvasId, r, g, b) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var grad = ctx.createLinearGradient(0, 0, 0, 200);
    grad.addColorStop(0, 'rgba(' + r + ',' + g + ',' + b + ',0.15)');
    grad.addColorStop(1, 'rgba(' + r + ',' + g + ',' + b + ',0)');
    return grad;
}

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
                borderWidth: 2,
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
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
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
                { label: label1, data: [], borderColor: color1, borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false },
                { label: label2, data: [], borderColor: color2, borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false }
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
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' }, stacked: false }
            }
        }
    });
}

// Initialize charts with gradient fills
var chartMidPrice = makeLineChart('chart-mid-price', 'Mid Price', '#06b6d4', makeGradient('chart-mid-price', 6, 182, 212));
var chartImbalance = makeLineChart('chart-imbalance', 'Imbalance', '#60a5fa', makeGradient('chart-imbalance', 96, 165, 250));
var chartSpread = makeLineChart('chart-spread', 'Spread BPS', '#f97316', makeGradient('chart-spread', 249, 115, 22));
var chartVolume = makeDualLineChart('chart-volume', 'Bid Vol', '#10b981', 'Ask Vol', '#f43f5e');
var chartCvd = makeLineChart('chart-cvd', 'CVD', '#a78bfa', makeGradient('chart-cvd', 167, 139, 250));
var chartBuySell = makeBarChart('chart-buysell', 'Buy Vol', 'rgba(16, 185, 129, 0.7)', 'Sell Vol', 'rgba(244, 63, 94, 0.7)');

// Trade Flow Scatter chart (bubble)
var chartTradeFlow = new Chart(document.getElementById('chart-trade-flow'), {
    type: 'bubble',
    data: {
        datasets: [
            { label: 'Buy', data: [], backgroundColor: 'rgba(16, 185, 129, 0.5)', borderColor: '#10b981', borderWidth: 1 },
            { label: 'Sell', data: [], backgroundColor: 'rgba(244, 63, 94, 0.5)', borderColor: '#f43f5e', borderWidth: 1 }
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

// Trade Pressure stacked bar
var chartTradePressure = new Chart(document.getElementById('chart-trade-pressure'), {
    type: 'bar',
    data: {
        labels: [],
        datasets: [
            { label: 'Buy Vol', data: [], backgroundColor: 'rgba(16, 185, 129, 0.7)', borderWidth: 0 },
            { label: 'Sell Vol', data: [], backgroundColor: 'rgba(244, 63, 94, 0.7)', borderWidth: 0 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false }, stacked: true },
            y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' }, stacked: true }
        }
    }
});

// Realized Volatility line chart
var chartVolatility = makeLineChart('chart-volatility', 'Realized Vol', '#f472b6', makeGradient('chart-volatility', 244, 114, 182));

// Running Cumulative CVD line chart with green/red segments
var chartCumulativeCvd = makeLineChart('chart-cumulative-cvd', 'Cumulative CVD', '#a78bfa', makeGradient('chart-cumulative-cvd', 167, 139, 250));

// VPIN line chart with threshold reference lines (0.3 and 0.6)
var chartVpin = new Chart(document.getElementById('chart-vpin'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'VPIN',
            data: [],
            borderColor: '#f97316',
            backgroundColor: makeGradient('chart-vpin', 249, 115, 22),
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: false } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { display: true, min: 0, max: 1, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
        }
    },
    plugins: [{
        id: 'vpinThresholds',
        afterDraw: function(chart) {
            var yScale = chart.scales.y;
            var ctx = chart.ctx;
            ctx.save();
            // 0.3 threshold (green/amber boundary)
            var y03 = yScale.getPixelForValue(0.3);
            ctx.strokeStyle = 'rgba(16, 185, 129, 0.4)';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(chart.chartArea.left, y03);
            ctx.lineTo(chart.chartArea.right, y03);
            ctx.stroke();
            // 0.6 threshold (amber/red boundary)
            var y06 = yScale.getPixelForValue(0.6);
            ctx.strokeStyle = 'rgba(244, 63, 94, 0.4)';
            ctx.beginPath();
            ctx.moveTo(chart.chartArea.left, y06);
            ctx.lineTo(chart.chartArea.right, y06);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.restore();
        }
    }]
});

// Buy/Sell Ratio line chart with y=1.0 reference line
var chartBuySellRatio = new Chart(document.getElementById('chart-buy-sell-ratio'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Buy/Sell Ratio',
            data: [],
            borderColor: '#06b6d4',
            backgroundColor: makeGradient('chart-buy-sell-ratio', 6, 182, 212),
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: {
            legend: { display: false },
            annotation: undefined
        },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: {
                display: true,
                ticks: { maxTicksLimit: 5 },
                grid: { color: 'rgba(255,255,255,0.03)' }
            }
        }
    },
    plugins: [{
        id: 'referenceLine',
        afterDraw: function(chart) {
            var yScale = chart.scales.y;
            if (yScale.min <= 1 && yScale.max >= 1) {
                var ctx = chart.ctx;
                var y = yScale.getPixelForValue(1);
                ctx.save();
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                ctx.lineWidth = 1;
                ctx.setLineDash([4, 4]);
                ctx.beginPath();
                ctx.moveTo(chart.chartArea.left, y);
                ctx.lineTo(chart.chartArea.right, y);
                ctx.stroke();
                ctx.restore();
            }
        }
    }]
});

// Per-Minute Returns bar chart (green/red per bar)
var chartReturns = new Chart(document.getElementById('chart-returns'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Return %', data: [], backgroundColor: [], borderWidth: 0 }] },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: false } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { display: true, ticks: { maxTicksLimit: 5, callback: function(v) { return v.toFixed(2) + '%'; } }, grid: { color: 'rgba(255,255,255,0.03)' } }
        }
    }
});

// 1m Close Price line chart with gradient fill
var chartClosePrice = makeLineChart('chart-close-price', 'Close Price', '#06b6d4', makeGradient('chart-close-price', 6, 182, 212));

// Trade Stats chart — 3 lines with dual axis
var chartTradeStats = new Chart(document.getElementById('chart-trade-stats'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            { label: 'Trades/min', data: [], borderColor: '#60a5fa', borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false, yAxisID: 'y' },
            { label: 'Avg Size', data: [], borderColor: '#10b981', borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false, yAxisID: 'y1' },
            { label: 'Max Size', data: [], borderColor: '#f97316', borderWidth: 2, pointRadius: 0, tension: 0.3, fill: false, yAxisID: 'y1' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } } },
        scales: {
            x: { display: true, ticks: { maxTicksLimit: 8, maxRotation: 0 }, grid: { display: false } },
            y: { display: true, position: 'left', ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' }, title: { display: true, text: 'Trades', color: '#6b7280', font: { size: 9 } } },
            y1: { display: true, position: 'right', ticks: { maxTicksLimit: 5 }, grid: { drawOnChartArea: false }, title: { display: true, text: 'Size', color: '#6b7280', font: { size: 9 } } }
        }
    }
});

// Spread Distribution bar chart
var chartSpreadDist = new Chart(document.getElementById('chart-spread-dist'), {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Count',
            data: [],
            backgroundColor: 'rgba(249, 115, 22, 0.6)',
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: { legend: { display: false } },
        scales: {
            x: { display: true, ticks: { maxRotation: 0 }, grid: { display: false } },
            y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
        }
    }
});

function updateChart(chart, labels, data, datasetIndex) {
    chart.data.labels = labels;
    if (datasetIndex !== undefined) {
        chart.data.datasets[datasetIndex].data = data;
    } else {
        chart.data.datasets[0].data = data;
    }
    chart.update('none');
}

// Track previous large trades count for flash animation
var prevLargeTradesCount = 0;

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
            imbEl.className = 'text-lg font-mono tabular-nums ' + (d.imbalance >= 0 ? 'text-emerald-400' : 'text-rose-400');

            document.getElementById('mid-price').textContent = fmt(d.mid_price, 4);
            document.getElementById('weighted-mid').textContent = fmt(d.weighted_mid_price, 4);
            document.getElementById('spread-bps').textContent = fmt(d.spread_bps, 2);

            // Imbalance bar
            var total = d.bid_volume + d.ask_volume;
            if (total > 0) {
                var askPct = (d.ask_volume / total) * 100;
                var bidPct = (d.bid_volume / total) * 100;
                var bar = document.getElementById('imbalance-bar');
                bar.style.background = 'linear-gradient(90deg, #f43f5e 0%, #f43f5e ' + askPct.toFixed(1) + '%, #374151 ' + askPct.toFixed(1) + '%, #374151 ' + (100 - bidPct).toFixed(1) + '%, #10b981 ' + (100 - bidPct).toFixed(1) + '%, #10b981 100%)';
                document.getElementById('imb-ask').textContent = fmt(askPct, 1) + '% Asks';
                document.getElementById('imb-bid').textContent = fmt(bidPct, 1) + '% Bids';
                var imbValEl = document.getElementById('imb-val');
                imbValEl.textContent = (d.imbalance >= 0 ? '+' : '') + fmt(d.imbalance * 100, 1) + '%';
                imbValEl.className = d.imbalance >= 0 ? 'text-emerald-400' : 'text-rose-400';
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
                chartCvd.data.labels = aggLabels;
                chartCvd.data.datasets[0].data = cvdData;
                chartCvd.data.datasets[0].segment = {
                    borderColor: function(ctx) {
                        return ctx.p0.parsed.y >= 0 && ctx.p1.parsed.y >= 0 ? '#10b981' : '#f43f5e';
                    }
                };
                chartCvd.data.datasets[0].borderColor = '#a78bfa';
                chartCvd.data.datasets[0].backgroundColor = makeGradient('chart-cvd', 167, 139, 250);
                chartCvd.update('none');

                chartBuySell.data.labels = aggLabels;
                chartBuySell.data.datasets[0].data = buyData;
                chartBuySell.data.datasets[1].data = sellData;
                chartBuySell.update('none');

                // Trade Pressure (stacked buy vs sell from aggregates)
                chartTradePressure.data.labels = aggLabels;
                chartTradePressure.data.datasets[0].data = buyData;
                chartTradePressure.data.datasets[1].data = sellData;
                chartTradePressure.update('none');
            }

            // Per-Minute Returns, Close Price, Trade Stats from aggregates
            if (d.chart_aggregates && d.chart_aggregates.length > 0) {
                // Per-Minute Returns
                var retLabels = d.chart_aggregates.map(function(a) { return a.time; });
                var retData = d.chart_aggregates.map(function(a) { return a.price_change_pct; });
                var retColors = retData.map(function(v) { return v !== null && v >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(244, 63, 94, 0.7)'; });
                chartReturns.data.labels = retLabels;
                chartReturns.data.datasets[0].data = retData;
                chartReturns.data.datasets[0].backgroundColor = retColors;
                chartReturns.update('none');

                // 1m Close Price
                var closeLabels = d.chart_aggregates.filter(function(a) { return a.close_price !== null; }).map(function(a) { return a.time; });
                var closeData = d.chart_aggregates.filter(function(a) { return a.close_price !== null; }).map(function(a) { return a.close_price; });
                updateChart(chartClosePrice, closeLabels, closeData);

                // Trade Stats
                chartTradeStats.data.labels = retLabels;
                chartTradeStats.data.datasets[0].data = d.chart_aggregates.map(function(a) { return a.trade_count; });
                chartTradeStats.data.datasets[1].data = d.chart_aggregates.map(function(a) { return a.avg_trade_size; });
                chartTradeStats.data.datasets[2].data = d.chart_aggregates.map(function(a) { return a.max_trade_size; });
                chartTradeStats.update('none');
            }

            // Trade Flow Scatter
            if (d.chart_trades && d.chart_trades.length > 0) {
                var buyBubbles = [];
                var sellBubbles = [];
                for (var i = 0; i < d.chart_trades.length; i++) {
                    var t = d.chart_trades[i];
                    var bubble = { x: i, y: t.price, r: Math.min(Math.max(Math.sqrt(t.quantity) * 3, 2), 15) };
                    if (t.is_buyer_maker) {
                        sellBubbles.push(bubble);
                    } else {
                        buyBubbles.push(bubble);
                    }
                }
                chartTradeFlow.data.datasets[0].data = buyBubbles;
                chartTradeFlow.data.datasets[1].data = sellBubbles;
                chartTradeFlow.update('none');
            }

            // Volatility chart
            if (d.chart_volatility && d.chart_volatility.length > 0) {
                var volLabels = d.chart_volatility.map(function(v) { return v.time; });
                var volData = d.chart_volatility.map(function(v) { return v.realized_vol; });
                updateChart(chartVolatility, volLabels, volData);
            }

            // Running Cumulative CVD chart with green/red segment coloring
            if (d.chart_cumulative_cvd && d.chart_cumulative_cvd.length > 0) {
                var ccvdLabels = d.chart_cumulative_cvd.map(function(v) { return v.time; });
                var ccvdData = d.chart_cumulative_cvd.map(function(v) { return v.value; });
                chartCumulativeCvd.data.labels = ccvdLabels;
                chartCumulativeCvd.data.datasets[0].data = ccvdData;
                chartCumulativeCvd.data.datasets[0].segment = {
                    borderColor: function(ctx) {
                        return ctx.p0.parsed.y >= 0 && ctx.p1.parsed.y >= 0 ? '#10b981' : '#f43f5e';
                    }
                };
                chartCumulativeCvd.update('none');
            }

            // Buy/Sell Ratio chart
            if (d.chart_buy_sell_ratio && d.chart_buy_sell_ratio.length > 0) {
                var bsrLabels = d.chart_buy_sell_ratio.filter(function(v) { return v.value !== null; }).map(function(v) { return v.time; });
                var bsrData = d.chart_buy_sell_ratio.filter(function(v) { return v.value !== null; }).map(function(v) { return v.value; });
                updateChart(chartBuySellRatio, bsrLabels, bsrData);
            }

            // Large Trades feed
            var ltBody = document.getElementById('large-trades-body');
            if (d.large_trades && d.large_trades.length > 0) {
                var newCount = d.large_trades.length;
                var hasNewTrades = newCount > prevLargeTradesCount;
                var newTradeCount = hasNewTrades ? newCount - prevLargeTradesCount : 0;
                prevLargeTradesCount = newCount;

                var ltHtml = '';
                for (var i = 0; i < d.large_trades.length; i++) {
                    var lt = d.large_trades[i];
                    var sideBadge = lt.side === 'BUY'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400">' + lt.side + '</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400">' + lt.side + '</span>';
                    var multBadge = lt.size_multiple >= 5
                        ? '<span class="bg-cyan-500/15 text-cyan-400 px-2 py-0.5 rounded-full text-[10px]">' + fmt(lt.size_multiple, 1) + 'x</span>'
                        : '<span class="text-gray-200">' + fmt(lt.size_multiple, 1) + 'x</span>';
                    var flashClass = (hasNewTrades && i < newTradeCount) ? ' flash-new-row' : '';
                    ltHtml += '<tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors' + flashClass + '">' +
                        '<td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">' + lt.time + '</td>' +
                        '<td class="px-4 py-2.5">' + sideBadge + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(lt.price, 4) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(lt.quantity, 2) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums">' + multBadge + '</td>' +
                    '</tr>';
                }
                ltBody.innerHTML = ltHtml;
            } else {
                prevLargeTradesCount = 0;
                ltBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-xs text-gray-600">No large trades detected yet</td></tr>';
            }

            // Orderbook Walls feed
            var wallsBody = document.getElementById('walls-body');
            if (d.orderbook_walls && d.orderbook_walls.length > 0) {
                var wallsHtml = '';
                for (var i = 0; i < d.orderbook_walls.length; i++) {
                    var w = d.orderbook_walls[i];
                    var sideBadge = w.side === 'BID'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400">' + w.side + '</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400">' + w.side + '</span>';
                    var statusBadge = w.status === 'ACTIVE'
                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-500/15 text-cyan-400">ACTIVE</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-500/15 text-gray-500">REMOVED ' + (w.removed_at || '') + '</span>';
                    var wallMultBadge = w.size_multiple >= 5
                        ? '<span class="bg-cyan-500/15 text-cyan-400 px-2 py-0.5 rounded-full text-[10px]">' + fmt(w.size_multiple, 1) + 'x</span>'
                        : '<span class="text-gray-200">' + fmt(w.size_multiple, 1) + 'x</span>';
                    wallsHtml += '<tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors">' +
                        '<td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">' + w.time + '</td>' +
                        '<td class="px-4 py-2.5">' + sideBadge + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(w.price, 4) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(w.quantity, 2) + '</td>' +
                        '<td class="px-4 py-2.5 text-right font-mono tabular-nums">' + wallMultBadge + '</td>' +
                        '<td class="px-4 py-2.5">' + statusBadge + '</td>' +
                    '</tr>';
                }
                wallsBody.innerHTML = wallsHtml;
            } else {
                wallsBody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-xs text-gray-600">No walls detected yet</td></tr>';
            }

            // VPIN card + chart
            var vpinEl = document.getElementById('vpin-value');
            if (d.vpin_value !== null && d.vpin_value !== undefined) {
                vpinEl.textContent = fmt(d.vpin_value, 4);
                if (d.vpin_value < 0.3) {
                    vpinEl.className = 'text-lg font-mono tabular-nums text-emerald-400';
                } else if (d.vpin_value < 0.6) {
                    vpinEl.className = 'text-lg font-mono tabular-nums text-amber-400';
                } else {
                    vpinEl.className = 'text-lg font-mono tabular-nums text-rose-400';
                }
            } else {
                vpinEl.textContent = '-';
                vpinEl.className = 'text-lg font-mono tabular-nums text-gray-500';
            }

            if (d.chart_vpin && d.chart_vpin.length > 0) {
                var vpinLabels = d.chart_vpin.map(function(v) { return v.time; });
                var vpinData = d.chart_vpin.map(function(v) { return v.value; });
                updateChart(chartVpin, vpinLabels, vpinData);
            }
        })
        .catch(function() {});
}

refresh();
setInterval(refresh, 1000);

// Depth heatmap (separate endpoint, refresh 5s)
var DEPTH_URL = '{{ route("admin.trading-pairs.analytics-depth-data", $tradingPair) }}';
var depthCanvas = document.getElementById('depth-heatmap');
var depthCtx = depthCanvas.getContext('2d');

function drawDepthHeatmap() {
    fetch(DEPTH_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d || !d.timestamps || d.timestamps.length === 0) return;

            var canvas = depthCanvas;
            var rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width - 32;
            canvas.height = 300;
            var ctx = depthCtx;
            var cols = d.timestamps.length;
            var rows = d.price_levels.length;
            if (rows === 0 || cols === 0) return;

            var cellW = Math.floor(canvas.width / cols);
            var cellH = Math.floor(canvas.height / rows);

            // Find max volume for normalization
            var maxVol = 0;
            for (var r = 0; r < rows; r++) {
                for (var c = 0; c < cols; c++) {
                    var bv = d.bid_heat[r] ? (d.bid_heat[r][c] || 0) : 0;
                    var av = d.ask_heat[r] ? (d.ask_heat[r][c] || 0) : 0;
                    if (bv > maxVol) maxVol = bv;
                    if (av > maxVol) maxVol = av;
                }
            }
            if (maxVol === 0) maxVol = 1;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (var row = 0; row < rows; row++) {
                for (var col = 0; col < cols; col++) {
                    var bv = d.bid_heat[row] ? (d.bid_heat[row][col] || 0) : 0;
                    var av = d.ask_heat[row] ? (d.ask_heat[row][col] || 0) : 0;
                    var x = col * cellW;
                    var y = (rows - 1 - row) * cellH;

                    if (bv > av) {
                        var intensity = Math.min(bv / maxVol, 1);
                        ctx.fillStyle = 'rgba(16, 185, 129, ' + (intensity * 0.8) + ')';
                    } else if (av > 0) {
                        var intensity = Math.min(av / maxVol, 1);
                        ctx.fillStyle = 'rgba(244, 63, 94, ' + (intensity * 0.8) + ')';
                    } else {
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.02)';
                    }
                    ctx.fillRect(x, y, cellW - 1, cellH - 1);
                }
            }

            // Draw current price line
            if (d.current_price) {
                var priceIdx = -1;
                var minDiff = Infinity;
                for (var i = 0; i < rows; i++) {
                    var diff = Math.abs(d.price_levels[i] - d.current_price);
                    if (diff < minDiff) { minDiff = diff; priceIdx = i; }
                }
                if (priceIdx >= 0) {
                    var lineY = (rows - 1 - priceIdx) * cellH + cellH / 2;
                    ctx.strokeStyle = '#06b6d4';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([4, 4]);
                    ctx.beginPath();
                    ctx.moveTo(0, lineY);
                    ctx.lineTo(canvas.width, lineY);
                    ctx.stroke();
                    ctx.setLineDash([]);
                }
            }
        })
        .catch(function() {});
}

drawDepthHeatmap();
setInterval(drawDepthHeatmap, 5000);

// Distributions & hourly heatmap (separate endpoint, refresh 30s)
var DIST_URL = '{{ route("admin.trading-pairs.analytics-distributions", $tradingPair) }}';

function refreshDistributions() {
    fetch(DIST_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d) return;

            // Spread histogram
            if (d.spread_histogram && d.spread_histogram.length > 0) {
                chartSpreadDist.data.labels = d.spread_histogram.map(function(b) { return b.bucket; });
                chartSpreadDist.data.datasets[0].data = d.spread_histogram.map(function(b) { return b.count; });
                chartSpreadDist.update('none');
            }

            // Hourly heatmap
            if (d.hourly_stats && d.hourly_stats.length > 0) {
                drawHourlyHeatmap(d.hourly_stats);
            }
        })
        .catch(function() {});
}

function drawHourlyHeatmap(stats) {
    var canvas = document.getElementById('hourly-heatmap');
    var rect = canvas.parentElement.getBoundingClientRect();
    canvas.width = rect.width - 32;
    canvas.height = 200;
    var ctx = canvas.getContext('2d');

    var metrics = ['avg_volume', 'avg_spread', 'avg_trades', 'avg_imbalance'];
    var labels = ['Volume', 'Spread', 'Trades', 'Imbalance'];
    var colors = ['#10b981', '#f97316', '#60a5fa', '#a78bfa'];
    var hours = 24;
    var rows = metrics.length;

    var cellW = Math.floor((canvas.width - 60) / hours);
    var cellH = Math.floor((canvas.height - 25) / rows);
    var offsetX = 60;

    // Find max per metric for normalization
    var maxVals = {};
    metrics.forEach(function(m) { maxVals[m] = 0; });
    stats.forEach(function(s) {
        metrics.forEach(function(m) {
            var v = Math.abs(s[m] || 0);
            if (v > maxVals[m]) maxVals[m] = v;
        });
    });

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw hour labels
    ctx.fillStyle = '#6b7280';
    ctx.font = '9px monospace';
    ctx.textAlign = 'center';
    for (var h = 0; h < hours; h++) {
        if (h % 3 === 0) {
            ctx.fillText(h + ':00', offsetX + h * cellW + cellW / 2, canvas.height - 2);
        }
    }

    // Draw row labels and cells
    for (var r = 0; r < rows; r++) {
        ctx.fillStyle = '#6b7280';
        ctx.textAlign = 'right';
        ctx.font = '9px monospace';
        ctx.fillText(labels[r], offsetX - 6, r * cellH + cellH / 2 + 4);

        for (var h = 0; h < hours; h++) {
            var stat = stats.find(function(s) { return s.hour === h; });
            var val = stat ? Math.abs(stat[metrics[r]] || 0) : 0;
            var maxV = maxVals[metrics[r]] || 1;
            var intensity = Math.min(val / maxV, 1);

            var x = offsetX + h * cellW;
            var y = r * cellH;

            ctx.fillStyle = colors[r].replace(')', ', ' + (intensity * 0.7 + 0.05) + ')').replace('rgb', 'rgba');
            ctx.fillRect(x, y, cellW - 1, cellH - 2);
        }
    }
}

refreshDistributions();
setInterval(refreshDistributions, 30000);

// Market Regime (separate endpoint, refresh 10s)
var REGIME_URL = '{{ route("admin.trading-pairs.analytics-regime", $tradingPair) }}';

var regimeStyles = {
    TRENDING_UP: { bg: 'bg-emerald-500/15', text: 'text-emerald-400', border: 'border-emerald-500/30', label: 'TRENDING UP' },
    TRENDING_DOWN: { bg: 'bg-rose-500/15', text: 'text-rose-400', border: 'border-rose-500/30', label: 'TRENDING DOWN' },
    RANGING: { bg: 'bg-amber-500/15', text: 'text-amber-400', border: 'border-amber-500/30', label: 'RANGING' },
    VOLATILE: { bg: 'bg-violet-500/15', text: 'text-violet-400', border: 'border-violet-500/30', label: 'VOLATILE' }
};

function refreshRegime() {
    fetch(REGIME_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d || !d.regime) return;
            var badge = document.getElementById('regime-badge');
            var conf = document.getElementById('regime-confidence');
            var style = regimeStyles[d.regime] || regimeStyles.RANGING;
            badge.className = 'text-xs font-semibold px-3 py-1 rounded-full transition-all duration-300 border ' + style.bg + ' ' + style.text + ' ' + style.border;
            badge.textContent = style.label;
            conf.textContent = 'Confidence: ' + (d.confidence * 100).toFixed(0) + '%';
        })
        .catch(function() {});
}

refreshRegime();
setInterval(refreshRegime, 10000);

// Correlation scatter charts (separate endpoint, refresh 30s)
var CORR_URL = '{{ route("admin.trading-pairs.analytics-correlations", $tradingPair) }}';

function makeScatterChart(canvasId, color) {
    return new Chart(document.getElementById(canvasId), {
        type: 'scatter',
        data: {
            datasets: [{
                label: 'Data',
                data: [],
                backgroundColor: color.replace(')', ', 0.5)').replace('rgb', 'rgba'),
                borderColor: color,
                borderWidth: 1,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 0 },
            plugins: { legend: { display: false } },
            scales: {
                x: { display: true, ticks: { maxTicksLimit: 6 }, grid: { color: 'rgba(255,255,255,0.03)' } },
                y: { display: true, ticks: { maxTicksLimit: 5 }, grid: { color: 'rgba(255,255,255,0.03)' } }
            }
        }
    });
}

var chartCorrOiPrice = makeScatterChart('chart-corr-oi-price', 'rgb(6, 182, 212)');
var chartCorrFundingPremium = makeScatterChart('chart-corr-funding-premium', 'rgb(139, 92, 246)');
var chartCorrVolVolatility = makeScatterChart('chart-corr-vol-volatility', 'rgb(16, 185, 129)');
var chartCorrImbPrice = makeScatterChart('chart-corr-imb-price', 'rgb(244, 63, 94)');

function refreshCorrelations() {
    fetch(CORR_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d) return;
            if (d.oi_vs_price) {
                chartCorrOiPrice.data.datasets[0].data = d.oi_vs_price;
                chartCorrOiPrice.update('none');
            }
            if (d.funding_vs_premium) {
                chartCorrFundingPremium.data.datasets[0].data = d.funding_vs_premium;
                chartCorrFundingPremium.update('none');
            }
            if (d.volume_vs_volatility) {
                chartCorrVolVolatility.data.datasets[0].data = d.volume_vs_volatility;
                chartCorrVolVolatility.update('none');
            }
            if (d.imbalance_vs_price) {
                chartCorrImbPrice.data.datasets[0].data = d.imbalance_vs_price;
                chartCorrImbPrice.update('none');
            }
        })
        .catch(function() {});
}

refreshCorrelations();
setInterval(refreshCorrelations, 30000);
</script>
@endsection
