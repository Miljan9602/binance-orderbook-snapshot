@extends('layouts.admin')

@section('title', 'Dashboard')

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
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-500/20 text-green-400 border border-green-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    LIVE
                </span>
                @if (!$pair->is_active)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 border border-red-500/30">INACTIVE</span>
                @endif
            </div>
            <p class="text-sm text-gray-500">{{ $pair->base_asset }} / {{ $pair->quote_asset }} &middot; Depth {{ $pair->depth_level }} Levels</p>
        </div>

        {{-- Current Price --}}
        <div class="text-center mb-8 py-6">
            <div id="price-display" class="text-5xl font-bold font-mono tabular-nums {{ $isPositive ? 'text-green-400' : 'text-red-400' }}">
                {{ $lastPrice ? number_format($lastPrice, 4) : '' }}
            </div>
            <div id="change-display" class="mt-2 text-lg font-mono tabular-nums {{ $isPositive ? 'text-green-500' : 'text-red-500' }}">
                @if ($priceChange !== null)
                    {{ $isPositive ? '+' : '' }}{{ number_format($priceChange, 2) }}%
                    @if ($ticker)
                        <span class="text-gray-500 text-sm ml-2">({{ $isPositive ? '+' : '' }}{{ number_format((float) $ticker->price_change, 4) }})</span>
                    @endif
                @endif
            </div>
            <div id="no-data" class="text-4xl text-gray-600 {{ $lastPrice ? 'hidden' : '' }}">Awaiting data...</div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-5">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Best Bid</div>
                <div id="best-bid" class="text-xl font-mono tabular-nums text-green-400">
                    {{ $snapshot ? number_format((float) $snapshot->best_bid_price, 4) : '-' }}
                </div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-5">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Best Ask</div>
                <div id="best-ask" class="text-xl font-mono tabular-nums text-red-400">
                    {{ $snapshot ? number_format((float) $snapshot->best_ask_price, 4) : '-' }}
                </div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-5">
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
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-5">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Depth Level</div>
                <div class="text-xl font-mono tabular-nums text-gray-200">{{ $pair->depth_level }}</div>
            </div>
        </div>

        {{-- 24h Stats --}}
        <div id="ticker-stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 {{ $ticker ? '' : 'hidden' }}">
            <div class="bg-gray-900/60 rounded-xl border border-gray-800/60 p-4">
                <div class="text-xs text-gray-500 mb-1">24h High</div>
                <div id="high-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->high_price, 4) : '-' }}</div>
            </div>
            <div class="bg-gray-900/60 rounded-xl border border-gray-800/60 p-4">
                <div class="text-xs text-gray-500 mb-1">24h Low</div>
                <div id="low-price" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->low_price, 4) : '-' }}</div>
            </div>
            <div class="bg-gray-900/60 rounded-xl border border-gray-800/60 p-4">
                <div class="text-xs text-gray-500 mb-1">24h Volume ({{ $pair->base_asset }})</div>
                <div id="volume" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format((float) $ticker->volume, 2) : '-' }}</div>
            </div>
            <div class="bg-gray-900/60 rounded-xl border border-gray-800/60 p-4">
                <div class="text-xs text-gray-500 mb-1">24h Trades</div>
                <div id="trade-count" class="text-base font-mono tabular-nums text-gray-200">{{ $ticker ? number_format($ticker->trade_count) : '-' }}</div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('admin.trading-pairs.show', $pair) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg transition-colors text-sm">
                View Live Orderbook
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </a>
            <form action="{{ route('admin.trading-pairs.toggle', $pair) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2.5 text-sm rounded-lg border {{ $pair->is_active ? 'border-red-800 text-red-400 hover:bg-red-900/30' : 'border-green-800 text-green-400 hover:bg-green-900/30' }} transition-colors">
                    {{ $pair->is_active ? 'Stop Streaming' : 'Start Streaming' }}
                </button>
            </form>
        </div>

        {{-- Metadata Bar --}}
        <div class="flex items-center gap-6 text-xs text-gray-600 border-t border-gray-800/50 pt-4">
            <span id="last-update">Last update: {{ $pair->last_update_at ? $pair->last_update_at->diffForHumans() : 'Never' }}</span>
            <span id="update-id">{{ $snapshot ? 'Update ID: ' . $snapshot->last_update_id : '' }}</span>
            <span id="received-at">{{ $snapshot ? 'Snapshot at: ' . $snapshot->received_at->format('H:i:s') : '' }}</span>
            <span>Auto-refresh: 300ms</span>
        </div>
    @else
        <div class="text-center py-20 text-gray-500">
            <p class="text-lg mb-2">No trading pairs configured</p>
            <p class="text-sm">Run <code class="text-gray-400 bg-gray-800 px-2 py-0.5 rounded">php artisan db:seed --class=TradingPairSeeder</code> to get started.</p>
        </div>
    @endif
@endsection

@section('scripts')
@if ($pair)
<script>
function fmt(n, d) { return n != null ? Number(n).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d}) : '-'; }
function cls(el, add, remove) { el.classList.remove(remove); el.classList.add(add); }

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
                priceEl.textContent = fmt(d.last_price, 4);
                cls(priceEl, pos ? 'text-green-400' : 'text-red-400', pos ? 'text-red-400' : 'text-green-400');
                priceEl.classList.remove('hidden');
                noData.classList.add('hidden');
            }

            if (d.price_change_percent !== null) {
                var sign = pos ? '+' : '';
                changeEl.innerHTML = sign + fmt(d.price_change_percent, 2) + '%' +
                    (d.price_change !== null ? ' <span class="text-gray-500 text-sm ml-2">(' + sign + fmt(d.price_change, 4) + ')</span>' : '');
                cls(changeEl, pos ? 'text-green-500' : 'text-red-500', pos ? 'text-red-500' : 'text-green-500');
            }

            document.getElementById('best-bid').textContent = fmt(d.best_bid, 4);
            document.getElementById('best-ask').textContent = fmt(d.best_ask, 4);

            if (d.spread !== null && d.spread_pct !== null) {
                document.getElementById('spread-val').innerHTML = fmt(d.spread, 4) + ' <span class="text-sm text-gray-500">(' + fmt(d.spread_pct, 3) + '%)</span>';
            }

            if (d.high_price !== null) {
                document.getElementById('ticker-stats').classList.remove('hidden');
                document.getElementById('high-price').textContent = fmt(d.high_price, 4);
                document.getElementById('low-price').textContent = fmt(d.low_price, 4);
                document.getElementById('volume').textContent = fmt(d.volume, 2);
                document.getElementById('trade-count').textContent = d.trade_count !== null ? Number(d.trade_count).toLocaleString() : '-';
            }

            document.getElementById('last-update').textContent = 'Last update: ' + (d.last_update_at || 'Never');
            document.getElementById('update-id').textContent = d.update_id ? 'Update ID: ' + d.update_id : '';
            document.getElementById('received-at').textContent = d.received_at ? 'Snapshot at: ' + d.received_at : '';
        })
        .catch(() => {});
}

setInterval(refresh, 300);
</script>
@endif
@endsection
