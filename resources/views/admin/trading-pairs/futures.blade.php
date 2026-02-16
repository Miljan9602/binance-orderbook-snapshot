@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Futures')

@section('content')
    {{-- Back link --}}
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">&larr; Dashboard</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Live Orderbook</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Analytics</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Klines</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">Futures</span></h1>
        @if($tradingPair->futures_symbol)
            <span class="text-xs text-gray-600 font-mono">{{ strtoupper($tradingPair->futures_symbol) }}</span>
        @endif
        <div class="text-xs text-gray-600">Auto-refresh 1s</div>
    </div>

    @if(!$tradingPair->futures_symbol)
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg mb-1">No futures symbol configured</p>
            <p class="text-sm">Set <code class="text-gray-400 bg-gray-800 px-2 py-0.5 rounded">futures_symbol</code> on this trading pair to enable futures data.</p>
        </div>
    @else
        {{-- Section 1: Live Futures Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Mark Price</div>
                <div id="mark-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Index Price</div>
                <div id="index-price" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Funding Rate</div>
                <div id="funding-rate" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Next Funding</div>
                <div id="next-funding" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Open Interest</div>
                <div id="open-interest" class="text-lg font-mono tabular-nums text-gray-200">-</div>
            </div>
        </div>

        {{-- Spot vs Futures comparison --}}
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 mb-8">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Spot vs Futures</div>
            <div class="flex items-center gap-8">
                <div>
                    <span class="text-xs text-gray-500">Spot</span>
                    <span id="spot-price" class="font-mono tabular-nums text-sm text-gray-200 ml-2">-</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Mark</span>
                    <span id="mark-price-cmp" class="font-mono tabular-nums text-sm text-gray-200 ml-2">-</span>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Premium</span>
                    <span id="premium" class="font-mono tabular-nums text-sm ml-2">-</span>
                </div>
            </div>
        </div>

        {{-- Section 2: Recent Liquidations --}}
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <h2 class="text-lg font-semibold">Recent Liquidations</h2>
                <span class="text-xs text-gray-600">Auto-refresh 2s, latest 50</span>
            </div>
            <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-4 py-3 font-medium">Time</th>
                                <th class="text-left px-4 py-3 font-medium">Side</th>
                                <th class="text-right px-4 py-3 font-medium">Price</th>
                                <th class="text-right px-4 py-3 font-medium">Quantity</th>
                                <th class="text-right px-4 py-3 font-medium">Avg Price</th>
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
                <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                    <div class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="datetime-local" name="history_from" value="{{ request('history_from') }}"
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="datetime-local" name="history_to" value="{{ request('history_to') }}"
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                            @if(request()->hasAny(['history_from', 'history_to']))
                                <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="px-4 py-1.5 border border-gray-700 text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if($history->isEmpty())
                <div class="text-center py-10 text-gray-600 text-sm">No futures metrics history yet.</div>
            @else
                <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-3 font-medium">Time</th>
                                    <th class="text-right px-4 py-3 font-medium">Mark Price</th>
                                    <th class="text-right px-4 py-3 font-medium">Index Price</th>
                                    <th class="text-right px-4 py-3 font-medium">Funding Rate</th>
                                    <th class="text-left px-4 py-3 font-medium">Next Funding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $row)
                                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                                        <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $row->received_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->mark_price, 4) }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $row->index_price, 4) }}</td>
                                        <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ (float) $row->funding_rate >= 0 ? 'text-red-400' : 'text-green-400' }}">
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
                <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
                    <div class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="datetime-local" name="oi_from" value="{{ request('oi_from') }}"
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="datetime-local" name="oi_to" value="{{ request('oi_to') }}"
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                            @if(request()->hasAny(['oi_from', 'oi_to']))
                                <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="px-4 py-1.5 border border-gray-700 text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if($oiHistory->isEmpty())
                <div class="text-center py-10 text-gray-600 text-sm">No open interest data yet. Run <code class="text-gray-400 bg-gray-800 px-2 py-0.5 rounded">php artisan binance:fetch-open-interest</code> or wait for the scheduler.</div>
            @else
                <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-3 font-medium">Time</th>
                                    <th class="text-right px-4 py-3 font-medium">Open Interest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($oiHistory as $row)
                                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
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

function refreshCards() {
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
                frEl.className = 'text-lg font-mono tabular-nums ' + (f.funding_rate >= 0 ? 'text-red-400' : 'text-green-400');

                // Countdown to next funding
                if (f.next_funding_time) {
                    var diff = Math.max(0, Math.floor((new Date(f.next_funding_time) - new Date()) / 1000));
                    var h = Math.floor(diff / 3600);
                    var m = Math.floor((diff % 3600) / 60);
                    var s = diff % 60;
                    document.getElementById('next-funding').textContent = h + 'h ' + m + 'm ' + s + 's';
                }

                // Spot vs Futures
                document.getElementById('mark-price-cmp').textContent = fmt(f.mark_price, 4);
            }

            if (d.spot_price !== null) {
                document.getElementById('spot-price').textContent = fmt(d.spot_price, 4);
            }

            if (f && d.spot_price !== null && d.spot_price > 0) {
                var premium = ((f.mark_price - d.spot_price) / d.spot_price) * 100;
                var premEl = document.getElementById('premium');
                premEl.textContent = (premium >= 0 ? '+' : '') + fmt(premium, 4) + '%';
                premEl.className = 'font-mono tabular-nums text-sm ml-2 ' + (premium >= 0 ? 'text-green-400' : 'text-red-400');
            }

            if (d.open_interest !== null) {
                document.getElementById('open-interest').textContent = fmt(d.open_interest, 2);
            }
        })
        .catch(function() {});
}

function refreshLiquidations() {
    fetch(DATA_URL)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            var liqs = d.liquidations;
            var body = document.getElementById('liquidations-body');

            if (!liqs || liqs.length === 0) {
                body.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-xs text-gray-600">No liquidations yet</td></tr>';
                return;
            }

            var html = '';
            for (var i = 0; i < liqs.length; i++) {
                var l = liqs[i];
                var sideColor = l.side === 'BUY' ? 'text-green-400' : 'text-red-400';
                html += '<tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">' +
                    '<td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">' + l.time + '</td>' +
                    '<td class="px-4 py-2.5 font-semibold ' + sideColor + '">' + l.side + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(l.price, 4) + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">' + fmt(l.quantity, 2) + '</td>' +
                    '<td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">' + fmt(l.avg_price, 4) + '</td>' +
                    '<td class="px-4 py-2.5 text-xs text-gray-500">' + l.order_status + '</td>' +
                '</tr>';
            }
            body.innerHTML = html;
        })
        .catch(function() {});
}

refreshCards();
refreshLiquidations();
setInterval(refreshCards, 1000);
setInterval(refreshLiquidations, 2000);
</script>
@endif
@endsection
