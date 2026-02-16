<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield('meta')
    <title>@yield('title', 'Orderbook Monitor') - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .font-mono { font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
    @yield('styles')
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">
    <nav class="sticky top-0 z-50 bg-gray-900/80 backdrop-blur-xl border-b border-gray-800">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('admin.trading-pairs.index') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 8h18M3 12h12M3 16h8M3 20h4" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold text-gray-100">Orderbook Monitor</span>
                </a>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span>NIMA Research</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-screen-2xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-900/30 border border-green-800 rounded-lg text-green-400 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-gray-800/50 py-4">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between text-xs text-gray-600">
            <span>Orderbook Monitor &mdash; NIMA Research</span>
            <span>Data from Binance WebSocket API</span>
        </div>
    </footer>
    @yield('scripts')
</body>
</html>
