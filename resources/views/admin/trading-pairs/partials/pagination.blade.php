@if ($paginator->hasPages())
    <nav class="flex items-center justify-between">
        <div class="text-xs text-gray-500 font-mono">
            Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}
        </div>
        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="flex items-center gap-0.5 px-3 py-1.5 text-xs text-gray-700 rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg> Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="flex items-center gap-0.5 px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg> Prev
                </a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-1.5 text-xs text-gray-600">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1.5 text-xs bg-cyan-500/15 text-cyan-400 border border-cyan-500/20 font-semibold rounded-lg">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg transition-colors">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="flex items-center gap-0.5 px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-white/[0.05] rounded-lg transition-colors">
                    Next <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </a>
            @else
                <span class="flex items-center gap-0.5 px-3 py-1.5 text-xs text-gray-700 rounded-lg">
                    Next <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
