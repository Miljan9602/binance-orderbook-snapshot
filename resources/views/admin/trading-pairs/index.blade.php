@extends('layouts.admin')

@section('title', 'Dashboard')

@section('meta')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('content')
    @php
        $pair = $pairs->first();
        $snapshot = $pair?->snapshot;
        $ticker = $pair?->ticker;
        $lastPrice = $ticker ? (float) $ticker->last_price : ($snapshot ? (float) $snapshot->best_bid_price : null);
        $priceChange = $ticker ? (float) $ticker->price_change_percent : null;
        $isPositive = $priceChange !== null && $priceChange >= 0;
    @endphp

    @if ($pair)
        {{-- Hero Section --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <h1 class="text-3xl font-bold tracking-tight">{{ $pair->symbol }}</h1>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    LIVE
                </span>
                @if (!$pair->is_active)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 border border-rose-500/30">INACTIVE</span>
                @endif
            </div>
            <p class="text-sm text-gray-500">{{ $pair->base_asset }} / {{ $pair->quote_asset }} &middot; Depth {{ $pair->depth_level }} Levels</p>
        </div>

        {{-- Current Price --}}
        <div class="relative text-center mb-8 py-6">
            <div class="hero-glow">
                <div id="price-display" data-last-price="{{ $lastPrice ?? '0' }}" class="text-5xl font-bold font-mono tabular-nums {{ $isPositive ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $lastPrice ? number_format($lastPrice, 4) : '' }}
                </div>
                <div id="change-display" class="mt-2 text-lg font-mono tabular-nums {{ $isPositive ? 'text-emerald-500' : 'text-rose-500' }}">
                    @if ($priceChange !== null)
                        {{ $isPositive ? '+' : '' }}{{ number_format($priceChange, 2) }}%
                        @if ($ticker)
                            <span class="text-gray-500 text-sm ml-2">({{ $isPositive ? '+' : '' }}{{ number_format((float) $ticker->price_change, 4) }})</span>
                        @endif
                    @endif
                </div>
                <div id="no-data" class="text-4xl text-gray-600 {{ $lastPrice ? 'hidden' : '' }}">Awaiting data...</div>
                <div class="mt-4 mx-auto" style="width: 280px; height: 60px;">
                    <canvas id="sparkline-chart"></canvas>
                </div>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="glass-card accent-bar-cyan p-5 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Best Bid</div>
                <div id="best-bid" class="text-xl font-mono tabular-nums text-emerald-400">
                    {{ $snapshot ? number_format((float) $snapshot->best_bid_price, 4) : '-' }}
                </div>
            </div>
            <div class="glass-card accent-bar-cyan p-5 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Best Ask</div>
                <div id="best-ask" class="text-xl font-mono tabular-nums text-rose-400">
                    {{ $snapshot ? number_format((float) $snapshot->best_ask_price, 4) : '-' }}
                </div>
            </div>
            <div class="glass-card accent-bar-cyan p-5 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Spread</div>
                <div id="spread-val" class="text-xl font-mono tabular-nums text-gray-200">
                    @if ($snapshot && (float) $snapshot->best_bid_price > 0)
                        {{ number_format((float) $snapshot->spread, 4) }}
                        <span class="text-sm text-gray-500">({{ number_format(((float) $snapshot->spread / (float) $snapshot->best_bid_price) * 100, 3) }}%)</span>
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="glass-card accent-bar-cyan p-5 pl-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Depth Level</div>
                <div class="text-xl font-mono tabular-nums text-gray-200">{{ $pair->depth_level }}</div>
            </div>
        </div>

        {{-- 24h Stats --}}
        <div id="ticker-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 {{ $ticker ? '' : 'hidden' }}">
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h High</div>
                <div id="high-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->high_price, 4) : '-' }}</div>
            </div>
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h Low</div>
                <div id="low-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->low_price, 4) : '-' }}</div>
            </div>
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h Volume ({{ $pair->base_asset }})</div>
                <div id="volume" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->volume, 2) : '-' }}</div>
            </div>
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h Trades</div>
                <div id="trade-count" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format($ticker->trade_count) : '-' }}</div>
            </div>
        </div>

        {{-- Extended 24h Stats --}}
        <div id="ticker-stats-extended" class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8 {{ $ticker ? '' : 'hidden' }}">
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h Quote Vol ({{ $pair->quote_asset }})</div>
                <div id="quote-volume" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->quote_volume, 2) : '-' }}</div>
            </div>
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">VWAP 24h</div>
                <div id="weighted-avg-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->weighted_avg_price, 4) : '-' }}</div>
            </div>
            <div class="glass-card p-4 transition-all duration-200 hover:-translate-y-0.5 glass-card-hover">
                <div class="text-xs text-gray-500 mb-1">24h Open</div>
                <div id="open-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->open_price, 4) : '-' }}</div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('admin.trading-pairs.show', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 text-gray-900 font-semibold rounded-xl shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30 hover:-translate-y-0.5 transition-all duration-200 text-sm">
                View Live Orderbook
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </a>
            <a href="{{ route('admin.trading-pairs.history', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-white/[0.1] text-gray-300 hover:text-gray-100 hover:bg-white/[0.05] hover:border-white/[0.15] rounded-xl transition-all duration-200 text-sm">
                Browse History
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </a>
            <a href="{{ route('admin.trading-pairs.analytics', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-white/[0.1] text-gray-300 hover:text-gray-100 hover:bg-white/[0.05] hover:border-white/[0.15] rounded-xl transition-all duration-200 text-sm">
                Analytics
            </a>
            <a href="{{ route('admin.trading-pairs.futures', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-violet-500/30 text-violet-400 hover:text-violet-300 hover:bg-violet-500/10 hover:border-violet-500/40 rounded-xl transition-all duration-200 text-sm">
                Futures
            </a>
            <a href="{{ route('admin.trading-pairs.klines', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 border border-white/[0.1] text-gray-300 hover:text-gray-100 hover:bg-white/[0.05] hover:border-white/[0.15] rounded-xl transition-all duration-200 text-sm">
                Klines
            </a>
            <form action="{{ route('admin.trading-pairs.toggle', $pair) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 text-sm rounded-xl border {{ $pair->is_active ? 'border-rose-500/30 text-rose-400 hover:bg-rose-500/10' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10' }} transition-all duration-200">
                    {{ $pair->is_active ? 'Stop Streaming' : 'Start Streaming' }}
                </button>
            </form>
        </div>

        {{-- Metadata Bar --}}
        <div class="flex items-center gap-6 text-xs text-gray-600 border-t border-white/[0.04] pt-4">
            <span id="last-update">Last update: {{ $pair->last_update_at ? $pair->last_update_at->diffForHumans() : 'Never' }}</span>
            <span id="update-id">{{ $snapshot ? 'Update ID: ' . $snapshot->last_update_id : '' }}</span>
            <span id="received-at">{{ $snapshot ? 'Snapshot at: ' . $snapshot->received_at->format('H:i:s') : '' }}</span>
            <span>Auto-refresh: 300ms</span>
        </div>
    @else
        <div class="text-center py-20 text-gray-500">
            <p class="text-lg mb-2">No trading pairs configured</p>
            <p class="text-sm">Run <code class="text-gray-400 bg-white/[0.05] px-2 py-0.5 rounded">php artisan db:seed --class=TradingPairSeeder</code> to get started.</p>
        </div>
    @endif
@endsection

@section('scripts')
@if ($pair)
<script>
function fmt(n, d) { return n != null ? Number(n).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d}) : '-'; }
function cls(el, add, remove) { el.classList.remove(remove); el.classList.add(add); }

var sparkCtx = document.getElementById('sparkline-chart').getContext('2d');
var sparkGrad = sparkCtx.createLinearGradient(0, 0, 0, 60);
sparkGrad.addColorStop(0, 'rgba(6, 182, 212, 0.15)');
sparkGrad.addColorStop(1, 'rgba(6, 182, 212, 0)');

var sparkChart = new Chart(document.getElementById('sparkline-chart'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            data: [],
            borderColor: '#06b6d4',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
            backgroundColor: sparkGrad,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: {
            x: { display: false },
            y: { display: false }
        },
        animation: false,
    }
});

function refresh() {
    fetch('{{ route("admin.trading-pairs.index-data") }}')
        .then(r => r.json())
        .then(d => {
            if (!d) return;
            var pos = d.price_change_percent !== null && d.price_change_percent >= 0;
            var priceEl = document.getElementById('price-display');
            var changeEl = document.getElementById('change-display');
            var noData = document.getElementById('no-data');

            if (d.last_price !== null) {
                var oldPrice = parseFloat(priceEl.dataset.lastPrice || '0');
                var newPrice = d.last_price;

                priceEl.textContent = fmt(d.last_price, 4);
                cls(priceEl, pos ? 'text-emerald-400' : 'text-rose-400', pos ? 'text-rose-400' : 'text-emerald-400');
                priceEl.classList.remove('hidden');
                noData.classList.add('hidden');

                if (oldPrice > 0 && newPrice !== oldPrice) {
                    priceEl.classList.add(newPrice > oldPrice ? 'flash-green' : 'flash-red');
                    priceEl.classList.add('number-pop');
                    setTimeout(function() { priceEl.classList.remove('flash-green', 'flash-red', 'number-pop'); }, 600);
                }
                priceEl.dataset.lastPrice = newPrice;
            }

            if (d.price_change_percent !== null) {
                var sign = pos ? '+' : '';
                changeEl.innerHTML = sign + fmt(d.price_change_percent, 2) + '%' +
                    (d.price_change !== null ? ' <span class="text-gray-500 text-sm ml-2">(' + sign + fmt(d.price_change, 4) + ')</span>' : '');
                cls(changeEl, pos ? 'text-emerald-500' : 'text-rose-500', pos ? 'text-rose-500' : 'text-emerald-500');
            }

            document.getElementById('best-bid').textContent = fmt(d.best_bid, 4);
            document.getElementById('best-ask').textContent = fmt(d.best_ask, 4);

            if (d.spread !== null && d.spread_pct !== null) {
                document.getElementById('spread-val').innerHTML = fmt(d.spread, 4) + ' <span class="text-sm text-gray-500">(' + fmt(d.spread_pct, 3) + '%)</span>';
            }

            if (d.high_price !== null) {
                document.getElementById('ticker-stats').classList.remove('hidden');
                document.getElementById('ticker-stats-extended').classList.remove('hidden');
                document.getElementById('high-price').textContent = fmt(d.high_price, 4);
                document.getElementById('low-price').textContent = fmt(d.low_price, 4);
                document.getElementById('volume').textContent = fmt(d.volume, 2);
                document.getElementById('trade-count').textContent = d.trade_count !== null ? Number(d.trade_count).toLocaleString() : '-';
                document.getElementById('quote-volume').textContent = fmt(d.quote_volume, 2);
                document.getElementById('weighted-avg-price').textContent = fmt(d.weighted_avg_price, 4);
                document.getElementById('open-price').textContent = fmt(d.open_price, 4);
            }

            document.getElementById('last-update').textContent = 'Last update: ' + (d.last_update_at || 'Never');
            document.getElementById('update-id').textContent = d.update_id ? 'Update ID: ' + d.update_id : '';
            document.getElementById('received-at').textContent = d.received_at ? 'Snapshot at: ' + d.received_at : '';

            if (d.sparkline && d.sparkline.length > 0) {
                sparkChart.data.labels = d.sparkline.map(function(_, i) { return i; });
                sparkChart.data.datasets[0].data = d.sparkline;
                sparkChart.update('none');
            }
        })
        .catch(() => {});
}

setInterval(refresh, 300);
</script>
@endif
@endsection
