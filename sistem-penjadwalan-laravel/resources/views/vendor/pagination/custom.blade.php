<div class="flex flex-col md:flex-row items-center justify-between w-full">
    <div class="text-sm text-gray-500 mb-4 md:mb-0">
        @if ($paginator->count() > 0)
            Menampilkan <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span> 
            hingga <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span> 
            dari <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span> entri
        @else
            Menampilkan 0 entri
        @endif
    </div>

    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-end gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="p-2 text-gray-300 cursor-not-allowed flex items-center justify-center" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition flex items-center justify-center" aria-label="Previous">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-2 text-gray-700 text-lg font-bold tracking-widest flex items-center justify-center" aria-disabled="true">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="w-9 h-9 flex items-center justify-center border border-gray-800 text-gray-900 rounded-lg text-base font-semibold" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center text-gray-700 hover:bg-gray-100 rounded-lg text-base font-medium transition" aria-label="Go to page {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition flex items-center justify-center" aria-label="Next">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="p-2 text-gray-300 cursor-not-allowed flex items-center justify-center" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </nav>
    @endif
</div>
