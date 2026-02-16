@extends('layouts.admin')

@section('title', $tradingPair->symbol . ' Klines')

@section('content')
    {{-- Back link --}}
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.trading-pairs.index') }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">&larr; Dashboard</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.show', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Live Orderbook</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.analytics', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Analytics</a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('admin.trading-pairs.futures', $tradingPair) }}" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Futures</a>
    </div>

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <h1 class="text-xl font-bold">{{ $tradingPair->symbol }} <span class="text-gray-500 font-normal">Klines</span></h1>
        <span class="text-xs text-gray-600">{{ number_format($klines->total()) }} candles</span>
    </div>

    {{-- Interval Tabs --}}
    <div class="flex items-center gap-1 mb-6">
        @foreach(['1m', '5m', '15m', '1h'] as $tab)
            <a href="{{ route('admin.trading-pairs.klines', array_merge(['tradingPair' => $tradingPair, 'interval' => $tab], request()->only(['from', 'to']))) }}"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $interval === $tab ? 'bg-yellow-500 text-gray-900' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800 border border-gray-700' }}">
                {{ $tab }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.trading-pairs.klines', $tradingPair) }}" class="mb-6">
        <input type="hidden" name="interval" value="{{ $interval }}">
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-4">
            <div class="flex items-end gap-4 flex-wrap">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From</label>
                    <input type="datetime-local" name="from" value="{{ request('from') }}"
                        class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To</label>
                    <input type="datetime-local" name="to" value="{{ request('to') }}"
                        class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" step="1">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg text-sm transition-colors">Filter</button>
                    @if(request()->hasAny(['from', 'to']))
                        <a href="{{ route('admin.trading-pairs.klines', ['tradingPair' => $tradingPair, 'interval' => $interval]) }}" class="px-4 py-1.5 border border-gray-700 text-gray-400 hover:text-gray-200 rounded-lg text-sm transition-colors">Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Klines Table --}}
    @if($klines->isEmpty())
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg mb-1">No klines for {{ $interval }} interval</p>
            <p class="text-sm">Data will appear once the WebSocket streams {{ $interval }} candles.</p>
        </div>
    @else
        <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
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
                        @foreach($klines as $kline)
                            @php
                                $isOpen = !$kline->is_closed;
                                $isGreen = (float) $kline->close >= (float) $kline->open;
                            @endphp
                            <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors {{ $isOpen ? 'border-l-2 border-l-yellow-500' : '' }}">
                                <td class="px-4 py-2.5 font-mono tabular-nums text-gray-300 whitespace-nowrap">{{ $kline->open_time->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-200">{{ number_format((float) $kline->open, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-green-400">{{ number_format((float) $kline->high, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-red-400">{{ number_format((float) $kline->low, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ $isGreen ? 'text-green-400' : 'text-red-400' }}">{{ number_format((float) $kline->close, 4) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-400">{{ number_format((float) $kline->volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format((float) $kline->quote_volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format($kline->trade_count) }}</td>
                                <td class="px-4 py-2.5 text-right font-mono tabular-nums text-gray-500">{{ number_format((float) $kline->taker_buy_volume, 2) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($isOpen)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">OPEN</span>
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
