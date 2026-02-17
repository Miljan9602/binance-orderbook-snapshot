<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @yield('meta')
    <title>@yield('title', 'Orderbook Monitor') - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #060a13 0%, #0a0e17 30%, #0d1321 100%);
            background-attachment: fixed;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.015;
            pointer-events: none;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            background-repeat: repeat;
        }
        body > * { position: relative; z-index: 1; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .tabular-nums { font-variant-numeric: tabular-nums; }

        /* Price flash animations */
        @keyframes flashGreen { 0% { background-color: rgba(16,185,129,0.2); } 100% { background-color: transparent; } }
        @keyframes flashRed { 0% { background-color: rgba(244,63,94,0.2); } 100% { background-color: transparent; } }
        @keyframes breathe { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.5; transform:scale(0.85); } }
        @keyframes newRowFlash { 0% { background-color: rgba(6,182,212,0.1); } 100% { background-color: transparent; } }
        @keyframes newRowFlashViolet { 0% { background-color: rgba(139,92,246,0.1); } 100% { background-color: transparent; } }
        @keyframes numberPop { 0% { transform: translateY(-2px); opacity: 0.7; } 100% { transform: translateY(0); opacity: 1; } }

        .flash-green { animation: flashGreen 0.6s ease-out; }
        .flash-red { animation: flashRed 0.6s ease-out; }
        .flash-new-row { animation: newRowFlash 1s ease-out; }
        .flash-new-row-violet { animation: newRowFlashViolet 1s ease-out; }
        .number-pop { animation: numberPop 0.2s ease-out; }

        /* Glow effects */
        .glow-cyan { box-shadow: 0 0 30px -5px rgba(6,182,212,0.2); }
        .glow-violet { box-shadow: 0 0 30px -5px rgba(139,92,246,0.2); }
        .glow-cyan-sm { box-shadow: 0 0 20px -5px rgba(6,182,212,0.15); }
        .glow-violet-sm { box-shadow: 0 0 20px -5px rgba(139,92,246,0.15); }

        /* Hero glow */
        .hero-glow { position: relative; }
        .hero-glow::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 300px;
            background: radial-gradient(ellipse, rgba(6,182,212,0.08), transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* Glass card */
        .glass-card {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem;
        }
        .glass-card-hover:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }

        /* Gradient accent bar */
        .accent-bar-cyan { position: relative; }
        .accent-bar-cyan::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: linear-gradient(180deg, #06b6d4, #3b82f6);
            border-radius: 1px;
        }
        .accent-bar-violet { position: relative; }
        .accent-bar-violet::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: linear-gradient(180deg, #8b5cf6, #6366f1);
            border-radius: 1px;
        }

        /* Gradient depth bars */
        .depth-bar-bid { background: linear-gradient(90deg, transparent, rgba(16,185,129,0.15)); }
        .depth-bar-ask { background: linear-gradient(90deg, transparent, rgba(244,63,94,0.15)); }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.12); }

        /* Pill nav */
        .pill-nav {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 3px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 0.75rem;
        }
        .pill-nav a, .pill-nav span {
            padding: 6px 14px;
            border-radius: 0.5rem;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .pill-nav a:hover {
            background: rgba(255,255,255,0.05);
            color: #e5e7eb;
        }
        .pill-nav .pill-active {
            background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(59,130,246,0.15));
            color: #67e8f9;
            box-shadow: 0 0 20px -5px rgba(6,182,212,0.15);
        }
        .pill-nav .pill-active-violet {
            background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(99,102,241,0.15));
            color: #c4b5fd;
            box-shadow: 0 0 20px -5px rgba(139,92,246,0.15);
        }

        /* Gradient border wrapper */
        .gradient-border {
            padding: 1px;
            background: linear-gradient(135deg, rgba(6,182,212,0.2), transparent 50%, rgba(59,130,246,0.2));
            border-radius: 1rem;
        }
        .gradient-border > div {
            background: rgba(13,19,33,0.95);
            border-radius: calc(1rem - 1px);
        }

        /* Breathe animation for live dot */
        .breathe { animation: breathe 2s ease-in-out infinite; }
    </style>
    @yield('styles')
</head>
<body class="text-gray-100 min-h-screen flex flex-col">
    <nav class="sticky top-0 z-50 bg-[#0a0e17]/80 backdrop-blur-2xl border-b border-white/[0.06]">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('admin.trading-pairs.index') }}" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/30 transition-shadow">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 8h18M3 12h12M3 16h8M3 20h4" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold text-gray-100">Orderbook Monitor</span>
                </a>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span>NIMA Research</span>
                    <span class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    @yield('nav-context')

    <main class="flex-1 max-w-screen-2xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
        @if (session('status'))
            <div class="mb-4 p-3 glass-card border-emerald-500/20 text-emerald-400 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-white/[0.04] py-4">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between text-xs text-gray-600">
            <span class="flex items-center gap-2">
                Orderbook Monitor &mdash; NIMA Research
                <span class="relative flex h-1.5 w-1.5">
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500/60 breathe"></span>
                </span>
            </span>
            <span>Data from Binance WebSocket API</span>
        </div>
    </footer>
    @yield('scripts')
</body>
</html>
