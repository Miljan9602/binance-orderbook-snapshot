@if ($paginator->hasPages())
    <nav class="flex items-center justify-between">
        <div class="text-xs text-gray-600">
            Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}
        </div>
        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-xs text-gray-700 rounded-lg">&laquo; Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-gray-800 rounded-lg transition-colors">&laquo; Prev</a>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-1.5 text-xs text-gray-700">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1.5 text-xs bg-yellow-500 text-gray-900 font-semibold rounded-lg">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-gray-800 rounded-lg transition-colors">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-xs text-gray-400 hover:text-gray-200 hover:bg-gray-800 rounded-lg transition-colors">Next &raquo;</a>
            @else
                <span class="px-3 py-1.5 text-xs text-gray-700">Next &raquo;</span>
            @endif
        </div>
    </nav>
@endif
