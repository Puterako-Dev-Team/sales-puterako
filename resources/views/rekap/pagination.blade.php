{{-- filepath: resources/views/rekap/pagination.blade.php --}}
@if ($rekaps->hasPages())
    <div class="flex items-center justify-between bg-white px-4 py-3 border ">
        <div>
            <p class="text-sm text-gray-700">
                Showing {{ $rekaps->firstItem() ?? 0 }} to {{ $rekaps->lastItem() ?? 0 }} of {{ $rekaps->total() }} entries
            </p>
        </div>
        <div class="flex items-center space-x-2">
            {{-- Previous Button --}}
            @if ($rekaps->onFirstPage())
                <button disabled class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border rounded cursor-not-allowed">
                    Previous
                </button>
            @else
                <a href="{{ $rekaps->previousPageUrl() }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    Previous
                </a>
            @endif

            {{-- Page Numbers Logic --}}
            @php
                $current = $rekaps->currentPage();
                $last = $rekaps->lastPage();
                $maxVisible = 5;
                if ($last <= $maxVisible) {
                    $start = 1;
                    $end = $last;
                } else {
                    $start = max(1, $current - 2);
                    $end = min($last, $start + $maxVisible - 1);
                    if ($end == $last) {
                        $start = max(1, $end - $maxVisible + 1);
                    }
                }
            @endphp

            @if($start > 1)
                <a href="{{ $rekaps->url(1) }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    1
                </a>
                @if($start > 2)
                    <span class="px-2 text-gray-500">...</span>
                @endif
            @endif

            @for ($i = $start; $i <= $end; $i++)
                @if ($i == $current)
                    <button class="px-3 py-1 text-sm text-white bg-blue-600 border border-blue-600 rounded">
                        {{ $i }}
                    </button>
                @else
                    <a href="{{ $rekaps->url($i) }}" 
                       class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                        {{ $i }}
                    </a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)
                    <span class="px-2 text-gray-500">...</span>
                @endif
                <a href="{{ $rekaps->url($last) }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    {{ $last }}
                </a>
            @endif

            @if ($rekaps->hasMorePages())
                <a href="{{ $rekaps->nextPageUrl() }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    Next
                </a>
            @else
                <button disabled class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border rounded cursor-not-allowed">
                    Next
                </button>
            @endif
        </div>
    </div>
@endif