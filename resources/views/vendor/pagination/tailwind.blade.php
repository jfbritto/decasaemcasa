@if ($paginator->hasPages())
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            Exibindo <span class="font-medium">{{ $paginator->firstItem() }}</span> a <span class="font-medium">{{ $paginator->lastItem() }}</span> de <span class="font-medium">{{ $paginator->total() }}</span> resultados
        </p>
    </div>
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center">
        <ul class="inline-flex items-center -space-x-px text-sm">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="flex items-center justify-center px-3 h-8 text-gray-300 bg-white border border-gray-200 rounded-l-lg cursor-default">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" class="flex items-center justify-center px-3 h-8 text-gray-500 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-50 hover:text-indigo-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
                    </a>
                </li>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="flex items-center justify-center px-3 h-8 text-gray-400 bg-white border border-gray-200">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="flex items-center justify-center px-3 h-8 text-white bg-indigo-600 border border-indigo-600 font-medium">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="flex items-center justify-center px-3 h-8 text-gray-600 bg-white border border-gray-200 hover:bg-indigo-50 hover:text-indigo-600">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" class="flex items-center justify-center px-3 h-8 text-gray-500 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-50 hover:text-indigo-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                    </a>
                </li>
            @else
                <li>
                    <span class="flex items-center justify-center px-3 h-8 text-gray-300 bg-white border border-gray-200 rounded-r-lg cursor-default">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
