@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Orderbook')

@section('meta')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('styles')
<style>
    .ob-row { height: 22px; position: relative; display: flex; align-items: center; font-size: 12px; }
    .ob-row:hover { background: rgba(255,255,255,0.03); }
    .depth-bar { position: absolute; top: 0; right: 0; bottom: 0; pointer-events: none; transition: width 0.15s ease; }
    .ob-price { width: 35%; text-align: right; padding-right: 8px; position: relative; z-index: 1; }
    .ob-qty { width: 35%; text-align: right; padding-right: 8px; position: relative; z-index: 1; }
    .ob-total { width: 30%; text-align: right; padding-right: 8px; position: relative; z-index: 1; color: #9ca3af; }
    .ob-header { height: 28px; display: flex; align-items: center; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .spread-row { height: 32px; display: flex; align-items: center; justify-content: center; background: linear-gradient(90deg, rgba(6,182,212,0.03), rgba(6,182,212,0.06), rgba(6,182,212,0.03)); border-top: 1px solid rgba(6,182,212,0.15); border-bottom: 1px solid rgba(6,182,212,0.15); font-size: 13px; }
    .trades-row { height: 22px; display: flex; align-items: center; font-size: 12px; }
    .trades-row:hover { background: rgba(255,255,255,0.03); }
    .imbalance-bar { height: 6px; border-radius: 9999px; transition: background 0.15s ease; }
</style>
@endsection

@section('content')
    {{-- Pill-style tab bar --}}
    <div class="mb-4 pill-nav inline-flex">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-gray-400">&larr; Dashboard</a>
        <span class="pill-active">Live Orderbook</span>
        <a href="{{ route('admin.trading-pairs.history', $tradingPair) }}" class="text-gray-400">History</a>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-gray-400">Analytics</a>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-gray-400">Futures</a>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-gray-400">Klines</a>
    </div>

    {{-- Header Bar --}}
    <div class="flex items-center gap-6 mb-5 pb-4 border-b border-white/[0.04] flex-wrap">
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold">{{ $tradingPair->symbol }}</h1>
            <span class="text-xs text-gray-500">{{ $tradingPair->base_asset }}/{{ $tradingPair->quote_asset }}</span>
        </div>

        <div class="font-mono tabular-nums">
            <span id="hdr-price" class="text-2xl font-bold text-gray-400">-</span>
            <span id="hdr-change" class="text-sm ml-2 text-gray-500"></span>
        </div>

        <div class="flex items-center gap-5 text-xs text-gray-500 ml-auto">
            <div><span class="text-gray-600">24h H</span> <span id="hdr-high" class="text-gray-300 font-mono tabular-nums ml-1">-</span></div>
            <div><span class="text-gray-600">24h L</span> <span id="hdr-low" class="text-gray-300 font-mono tabular-nums ml-1">-</span></div>
            <div><span class="text-gray-600">24h Vol</span> <span id="hdr-vol" class="text-gray-300 font-mono tabular-nums ml-1">-</span></div>
        </div>

        <div class="text-xs text-gray-600">Auto-refresh 300ms</div>
    </div>

    <div class="grid grid-cols-12 gap-4">

        {{-- LEFT COLUMN: Market Stats --}}
        <div class="col-span-12 lg:col-span-3 space-y-4">
            <div class="glass-card p-4">
                <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-3">Market Stats</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-500">Spread</span>
                        <span id="stat-spread" class="font-mono tabular-nums text-sm text-gray-200">-</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-500">Mid Price</span>
                        <span id="stat-mid" class="font-mono tabular-nums text-sm text-gray-200">-</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-500">Bid Volume</span>
                        <span id="stat-bid-vol" class="font-mono tabular-nums text-sm text-emerald-400">-</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-500">Ask Volume</span>
                        <span id="stat-ask-vol" class="font-mono tabular-nums text-sm text-rose-400">-</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-500">Bid/Ask Ratio</span>
                        <span id="stat-ratio" class="font-mono tabular-nums text-sm text-gray-200">-</span>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4">
                <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-3">Orderbook Imbalance</h3>
                <div class="mb-2">
                    <div id="imbalance-bar" class="imbalance-bar" style="background: #374151;"></div>
                </div>
                <div class="flex justify-between text-xs">
                    <span id="imb-ask" class="text-rose-400">-</span>
                    <span id="imb-val" class="text-gray-500">-</span>
                    <span id="imb-bid" class="text-emerald-400">-</span>
                </div>
            </div>

            <div class="glass-card p-4">
                <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-3">Info</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pair</span>
                        <span class="text-gray-300">{{ $tradingPair->base_asset }}/{{ $tradingPair->quote_asset }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Depth</span>
                        <span class="text-gray-300">{{ $tradingPair->depth_level }} levels</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Update ID</span>
                        <span id="info-uid" class="text-gray-300 font-mono">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Timestamp</span>
                        <span id="info-ts" class="text-gray-300 font-mono">-</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- CENTER COLUMN: Orderbook --}}
        <div class="col-span-12 lg:col-span-6">
            <div class="glass-card overflow-hidden">
                <div class="ob-header px-3">
                    <div class="ob-price">Price ({{ $tradingPair->quote_asset }})</div>
                    <div class="ob-qty">Qty ({{ $tradingPair->base_asset }})</div>
                    <div class="ob-total">Total</div>
                </div>
                <div id="asks-container" class="px-1"></div>
                <div id="spread-row" class="spread-row font-mono tabular-nums">
                    <span id="spread-mid" class="text-gray-200 font-semibold">-</span>
                    <span id="spread-detail" class="text-gray-500 text-xs ml-3"></span>
                </div>
                <div id="bids-container" class="px-1"></div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Recent Trades --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="glass-card overflow-hidden">
                <div class="px-4 py-2.5 border-b border-white/[0.06]">
                    <h3 class="text-xs text-gray-500 uppercase tracking-wider">Recent Trades</h3>
                </div>
                <div class="flex items-center px-3 py-1.5 text-xs text-gray-600 uppercase tracking-wider border-b border-white/[0.04]">
                    <div class="w-2/5 text-right pr-2">Price</div>
                    <div class="w-2/5 text-right pr-2">Qty</div>
                    <div class="w-1/5 text-right">Time</div>
                </div>
                <div id="trades-container" class="overflow-y-auto" style="max-height: 520px;">
                    <div class="text-center py-8 text-xs text-gray-600">Loading...</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Depth Area Chart --}}
    <div class="glass-card p-4 mt-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-xs text-gray-500 uppercase tracking-[0.15em]">Order Book Depth</div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-gray-600">300ms</span>
                <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
            </div>
        </div>
        <div style="height: 300px;"><canvas id="depth-chart"></canvas></div>
    </div>
@endsection

@section('scripts')
<script>
var DATA_URL = '{{ route("admin.trading-pairs.show-data", $tradingPair) }}';
var prevTradeCount = 0;

function fmt(n, d) { return n != null ? Number(n).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d}) : '-'; }

function buildRow(price, qty, cum, barWidth, type) {
    return '<div class="ob-row font-mono tabular-nums">' +
        '<div class="depth-bar depth-bar-' + type + '" style="width:' + barWidth.toFixed(1) + '%"></div>' +
        '<div class="ob-price ' + (type === 'bid' ? 'text-emerald-400' : 'text-rose-400') + '">' + price + '</div>' +
        '<div class="ob-qty text-gray-300">' + qty + '</div>' +
        '<div class="ob-total">' + fmt(cum, 2) + '</div>' +
    '</div>';
}

function refresh() {
    fetch(DATA_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var s = d.snapshot;
            var t = d.ticker;
            var trades = d.trades;

            // Header ticker
            if (t) {
                var pos = t.price_change_percent >= 0;
                var priceEl = document.getElementById('hdr-price');
                priceEl.textContent = fmt(t.last_price, 4);
                priceEl.className = 'text-2xl font-bold ' + (pos ? 'text-emerald-400' : 'text-rose-400');
                var chEl = document.getElementById('hdr-change');
                chEl.textContent = (pos ? '+' : '') + fmt(t.price_change_percent, 2) + '%';
                chEl.className = 'text-sm ml-2 ' + (pos ? 'text-emerald-500' : 'text-rose-500');
                document.getElementById('hdr-high').textContent = fmt(t.high_price, 4);
                document.getElementById('hdr-low').textContent = fmt(t.low_price, 4);
                document.getElementById('hdr-vol').textContent = fmt(t.volume, 0);
            }

            if (!s) return;

            var bids = s.bids;
            var asks = s.asks;
            var bestBid = s.best_bid_price;
            var bestAsk = s.best_ask_price;
            var spread = s.spread;
            var mid = (bestBid + bestAsk) / 2;
            var spreadPct = bestBid > 0 ? (spread / mid) * 100 : 0;

            // Cumulative bids
            var bidCum = [], cumT = 0;
            for (var i = 0; i < bids.length; i++) { cumT += parseFloat(bids[i][1]); bidCum.push(cumT); }
            var maxBidCum = cumT || 1;

            // Asks reversed + cumulative
            var asksRev = asks.slice().reverse();
            var askCumRev = []; cumT = 0;
            for (var i = 0; i < asksRev.length; i++) { cumT += parseFloat(asksRev[i][1]); askCumRev.push(cumT); }
            var maxAskCum = cumT || 1;
            askCumRev.reverse();
            var maxCum = Math.max(maxBidCum, maxAskCum);

            // Volume stats
            var totalBidVol = 0, totalAskVol = 0;
            for (var i = 0; i < bids.length; i++) totalBidVol += parseFloat(bids[i][1]);
            for (var i = 0; i < asks.length; i++) totalAskVol += parseFloat(asks[i][1]);
            var totalVol = totalBidVol + totalAskVol || 1;
            var bidPct = (totalBidVol / totalVol) * 100;
            var askPct = (totalAskVol / totalVol) * 100;
            var imbalance = ((totalBidVol - totalAskVol) / totalVol) * 100;

            // Market stats
            document.getElementById('stat-spread').innerHTML = fmt(spread, 4) + ' <span class="text-gray-500 text-xs">(' + fmt(spreadPct, 3) + '%)</span>';
            document.getElementById('stat-mid').textContent = fmt(mid, 4);
            document.getElementById('stat-bid-vol').textContent = fmt(totalBidVol, 2);
            document.getElementById('stat-ask-vol').textContent = fmt(totalAskVol, 2);
            var ratioEl = document.getElementById('stat-ratio');
            ratioEl.textContent = totalAskVol > 0 ? fmt(totalBidVol / totalAskVol, 2) : '-';
            ratioEl.className = 'font-mono tabular-nums text-sm ' + (totalBidVol >= totalAskVol ? 'text-emerald-400' : 'text-rose-400');

            // Imbalance bar
            var bar = document.getElementById('imbalance-bar');
            bar.style.background = 'linear-gradient(90deg, #f43f5e 0%, #f43f5e ' + askPct.toFixed(1) + '%, #374151 ' + askPct.toFixed(1) + '%, #374151 ' + (100 - bidPct).toFixed(1) + '%, #10b981 ' + (100 - bidPct).toFixed(1) + '%, #10b981 100%)';
            document.getElementById('imb-ask').textContent = fmt(askPct, 1) + '% Asks';
            document.getElementById('imb-bid').textContent = fmt(bidPct, 1) + '% Bids';
            var imbVal = document.getElementById('imb-val');
            imbVal.textContent = (imbalance >= 0 ? '+' : '') + fmt(imbalance, 1) + '%';
            imbVal.className = imbalance >= 0 ? 'text-emerald-400' : 'text-rose-400';

            // Info
            document.getElementById('info-uid').textContent = s.last_update_id;
            document.getElementById('info-ts').textContent = s.received_at;

            // Asks
            var html = '';
            for (var i = 0; i < asksRev.length; i++) {
                html += buildRow(asksRev[i][0], asksRev[i][1], askCumRev[i], (askCumRev[i] / maxCum) * 100, 'ask');
            }
            document.getElementById('asks-container').innerHTML = html;

            // Spread
            document.getElementById('spread-mid').textContent = fmt(mid, 4);
            document.getElementById('spread-detail').textContent = 'Spread: ' + fmt(spread, 4) + ' (' + fmt(spreadPct, 3) + '%)';

            // Bids
            html = '';
            for (var i = 0; i < bids.length; i++) {
                html += buildRow(bids[i][0], bids[i][1], bidCum[i], (bidCum[i] / maxCum) * 100, 'bid');
            }
            document.getElementById('bids-container').innerHTML = html;

            // Trades
            if (trades.length === 0) {
                document.getElementById('trades-container').innerHTML = '<div class="text-center py-8 text-xs text-gray-600">No trades yet</div>';
                prevTradeCount = 0;
            } else {
                var newCount = trades.length - prevTradeCount;
                if (newCount < 0) newCount = 0;
                html = '';
                for (var i = 0; i < trades.length; i++) {
                    var tr = trades[i];
                    var color = tr.is_buyer_maker ? 'text-rose-400' : 'text-emerald-400';
                    var flashClass = (i < newCount && prevTradeCount > 0) ? ' flash-new-row' : '';
                    html += '<div class="trades-row font-mono tabular-nums px-3' + flashClass + '">' +
                        '<div class="w-2/5 text-right pr-2 ' + color + '">' + fmt(tr.price, 4) + '</div>' +
                        '<div class="w-2/5 text-right pr-2 text-gray-400">' + fmt(tr.quantity, 2) + '</div>' +
                        '<div class="w-1/5 text-right text-gray-600 text-xs">' + tr.time + '</div>' +
                    '</div>';
                }
                document.getElementById('trades-container').innerHTML = html;
                prevTradeCount = trades.length;
            }

            // Update depth chart
            updateDepthChart(s);
        })
        .catch(function() {});
}

// Depth Chart Setup
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.font.size = 10;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(13,19,33,0.95)';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.padding = 10;

var depthCtx = document.getElementById('depth-chart').getContext('2d');
var bidGrad = depthCtx.createLinearGradient(0, 0, 0, 300);
bidGrad.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
bidGrad.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
var askGrad = depthCtx.createLinearGradient(0, 0, 0, 300);
askGrad.addColorStop(0, 'rgba(244, 63, 94, 0.25)');
askGrad.addColorStop(1, 'rgba(244, 63, 94, 0.02)');

var depthChart = new Chart(depthCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Bids',
                data: [],
                borderColor: '#10b981',
                backgroundColor: bidGrad,
                borderWidth: 2,
                pointRadius: 0,
                fill: 'origin',
                stepped: 'after',
                tension: 0,
            },
            {
                label: 'Asks',
                data: [],
                borderColor: '#f43f5e',
                backgroundColor: askGrad,
                borderWidth: 2,
                pointRadius: 0,
                fill: 'origin',
                stepped: 'before',
                tension: 0,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 0 },
        plugins: {
            legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 8 } },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    title: function(items) { return 'Price: ' + items[0].label; },
                    label: function(ctx) { return ctx.dataset.label + ': ' + fmt(ctx.parsed.y, 2); }
                }
            }
        },
        scales: {
            x: {
                display: true,
                ticks: { maxTicksLimit: 10, maxRotation: 0 },
                grid: { display: false },
                title: { display: true, text: 'Price', color: '#6b7280', font: { size: 9 } }
            },
            y: {
                display: true,
                ticks: { maxTicksLimit: 6 },
                grid: { color: 'rgba(255,255,255,0.03)' },
                title: { display: true, text: 'Cumulative Volume', color: '#6b7280', font: { size: 9 } }
            }
        },
        interaction: {
            mode: 'index',
            intersect: false,
        }
    }
});

function updateDepthChart(snapshot) {
    if (!snapshot || !snapshot.bids || !snapshot.asks) return;

    var bids = snapshot.bids;
    var asks = snapshot.asks;
    if (bids.length === 0 && asks.length === 0) return;

    // Build cumulative bid data (sorted descending price -> ascending for chart left-to-right)
    var bidData = [];
    var cumBid = 0;
    for (var i = 0; i < bids.length; i++) {
        cumBid += parseFloat(bids[i][1]);
        bidData.push({ price: parseFloat(bids[i][0]), cum: cumBid });
    }
    bidData.reverse(); // Now ascending price order

    // Build cumulative ask data (already ascending price)
    var askData = [];
    var cumAsk = 0;
    for (var i = 0; i < asks.length; i++) {
        cumAsk += parseFloat(asks[i][1]);
        askData.push({ price: parseFloat(asks[i][0]), cum: cumAsk });
    }

    // Combine price labels (bids ascending + asks ascending)
    var allPrices = bidData.map(function(d) { return d.price; }).concat(askData.map(function(d) { return d.price; }));
    var bidValues = bidData.map(function(d) { return d.cum; });
    var askValues = askData.map(function(d) { return d.cum; });

    // Pad bid/ask arrays with nulls to align with combined price axis
    var bidPadded = bidValues.concat(new Array(askData.length).fill(null));
    var askPadded = new Array(bidData.length).fill(null).concat(askValues);

    depthChart.data.labels = allPrices.map(function(p) { return p.toFixed(4); });
    depthChart.data.datasets[0].data = bidPadded;
    depthChart.data.datasets[1].data = askPadded;
    depthChart.update('none');
}

refresh();
setInterval(refresh, 300);
</script>
@endsection
