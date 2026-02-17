@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' History')

@section('styles')
<style>
    [x-cloak] { display: none !important; }
    .depth-row { display: flex; font-size: 11px; line-height: 18px; }
    .depth-row:nth-child(even) { background: rgba(255,255,255,0.02); }
    .depth-row { border-bottom: 1px solid rgba(255,255,255,0.04); }
    .depth-row:last-child { border-bottom: none; }
</style>
@endsection

@section('content')
    {{-- Pill nav --}}
    <div class="mb-4 pill-nav inline-flex">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-gray-400">&larr; Dashboard</a>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-gray-400">Live Orderbook</a>
        <span class="pill-active">History</span>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-gray-400">Analytics</a>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-gray-400">Futures</a>
        <a href="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="text-gray-400">Klines</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold text-gray-100">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">History</span></h1>
        <span class="text-xs text-gray-600 font-mono">{{ number_format($history->total()) }} snapshots</span>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.trading-pairs.history', $tradingPair) }}" class="mb-6">
        <div class="glass-card p-4">
            <div class="flex items-end gap-4 flex-wrap">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input type="datetime-local" name="from" value="{{ request('from') }}"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input type="datetime-local" name="to" value="{{ request('to') }}"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30" step="1">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Min Spread</label>
                    <input type="number" name="min_spread" value="{{ request('min_spread') }}" step="0.00000001" placeholder="0.0000"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 w-32 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Max Spread</label>
                    <input type="number" name="max_spread" value="{{ request('max_spread') }}" step="0.00000001" placeholder="0.0000"
                        class="bg-white/[0.03] border border-white/[0.06] rounded-lg px-3 py-1.5 text-sm text-gray-200 w-32 focus:outline-none focus:ring-1 focus:ring-cyan-500/30 focus:border-cyan-500/30">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-1.5 bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 text-gray-900 font-semibold rounded-lg text-sm transition-colors shadow-lg shadow-cyan-500/20">
                        Filter
                    </button>
                    @if(request()->hasAny(['from', 'to', 'min_spread', 'max_spread']))
                        <a href="{{ route('admin.trading-pairs.history', $tradingPair) }}" class="px-4 py-1.5 border border-white/[0.1] text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg text-sm transition-colors">
                            Clear
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    @if($history->isEmpty())
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg mb-1">No history records found</p>
            <p class="text-sm">Try adjusting your filters or wait for data to accumulate.</p>
        </div>
    @else
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/[0.06] text-[11px] tracking-[0.15em] text-gray-500 uppercase">
                            <th class="text-left px-4 py-3 font-medium">Time</th>
                            <th class="text-right px-4 py-3 font-medium">Best Bid</th>
                            <th class="text-right px-4 py-3 font-medium">Best Ask</th>
                            <th class="text-right px-4 py-3 font-medium">Spread</th>
                            <th class="text-right px-4 py-3 font-medium">Spread %</th>
                            <th class="text-right px-4 py-3 font-medium">Update ID</th>
                            <th class="text-center px-4 py-3 font-medium">Depth</th>
                        </tr>
                    </thead>
                    <tbody x-data="{ openRows: {} }">
                        @foreach($history as $row)
                            <tr class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors cursor-pointer" @click="openRows[{{ $row->id }}] = !openRows[{{ $row->id }}]">
                                <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">
                                    {{ $row->received_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-emerald-400">
                                    {{ number_format((float) $row->best_bid_price, 4) }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-rose-400">
                                    {{ number_format((float) $row->best_ask_price, 4) }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">
                                    {{ number_format((float) $row->spread, 4) }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">
                                    @if((float) $row->best_bid_price > 0)
                                        {{ number_format(((float) $row->spread / (float) $row->best_bid_price) * 100, 3) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-600 text-xs">
                                    {{ $row->last_update_id }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <button class="text-xs text-cyan-500 hover:text-cyan-400 transition-colors">
                                        <span x-text="openRows[{{ $row->id }}] ? 'Hide' : 'Show'">Show</span>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="openRows[{{ $row->id }}]" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
                                <td colspan="7" class="px-4 py-3">
                                    <div class="grid grid-cols-2 gap-4">
                                        {{-- Bids --}}
                                        <div>
                                            <div class="text-xs text-gray-500 uppercase tracking-[0.15em] mb-2">Bids ({{ count($row->bids) }} levels)</div>
                                            <div class="glass-card overflow-hidden">
                                                <div class="depth-row text-xs text-gray-600 uppercase tracking-[0.15em] px-3 py-1 border-b border-white/[0.04]">
                                                    <div class="w-1/3 text-right pr-2">Price</div>
                                                    <div class="w-1/3 text-right pr-2">Qty</div>
                                                    <div class="w-1/3 text-right pr-2">Total</div>
                                                </div>
                                                @php $cumBid = 0; @endphp
                                                @foreach($row->bids as $bid)
                                                    @php $cumBid += (float) $bid[1]; @endphp
                                                    <div class="depth-row font-mono tabular-nums px-3">
                                                        <div class="w-1/3 text-right pr-2 text-emerald-400">{{ $bid[0] }}</div>
                                                        <div class="w-1/3 text-right pr-2 text-gray-400">{{ $bid[1] }}</div>
                                                        <div class="w-1/3 text-right pr-2 text-gray-600">{{ number_format($cumBid, 2) }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        {{-- Asks --}}
                                        <div>
                                            <div class="text-xs text-gray-500 uppercase tracking-[0.15em] mb-2">Asks ({{ count($row->asks) }} levels)</div>
                                            <div class="glass-card overflow-hidden">
                                                <div class="depth-row text-xs text-gray-600 uppercase tracking-[0.15em] px-3 py-1 border-b border-white/[0.04]">
                                                    <div class="w-1/3 text-right pr-2">Price</div>
                                                    <div class="w-1/3 text-right pr-2">Qty</div>
                                                    <div class="w-1/3 text-right pr-2">Total</div>
                                                </div>
                                                @php $cumAsk = 0; @endphp
                                                @foreach($row->asks as $ask)
                                                    @php $cumAsk += (float) $ask[1]; @endphp
                                                    <div class="depth-row font-mono tabular-nums px-3">
                                                        <div class="w-1/3 text-right pr-2 text-rose-400">{{ $ask[0] }}</div>
                                                        <div class="w-1/3 text-right pr-2 text-gray-400">{{ $ask[1] }}</div>
                                                        <div class="w-1/3 text-right pr-2 text-gray-600">{{ number_format($cumAsk, 2) }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $history->links('admin.trading-pairs.partials.pagination') }}
        </div>
    @endif
@endsection
