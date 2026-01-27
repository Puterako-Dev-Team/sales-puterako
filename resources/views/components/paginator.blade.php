@if ($paginator->hasPages())
    <div class="flex items-center justify-between bg-white px-4 py-3 border-t">
        <!-- Left side: Showing info -->
        <div>
            <p class="text-sm text-gray-700">
                Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} entries
            </p>
        </div>

        <!-- Right side: Pagination buttons -->
        <div class="flex items-center space-x-2">
            {{-- Previous Button --}}
            @if ($paginator->onFirstPage())
                <button disabled class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border rounded cursor-not-allowed">
                    Previous
                </button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    Previous
                </a>
            @endif

            {{-- Page Numbers Logic --}}
            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $maxVisible = 5; // Maksimal 5 nomor halaman yang terlihat
                
                if ($last <= $maxVisible) {
                    // Jika total halaman <= 5, tampilkan semua
                    $start = 1;
                    $end = $last;
                } else {
                    // Jika total halaman > 5, gunakan logika sliding window
                    $start = max(1, $current - 2);
                    $end = min($last, $start + $maxVisible - 1);
                    
                    // Adjust start jika end sudah mentok di akhir
                    if ($end == $last) {
                        $start = max(1, $end - $maxVisible + 1);
                    }
                }
            @endphp

            {{-- Tampilkan halaman 1 + ... jika perlu --}}
            @if($start > 1)
                <a href="{{ $paginator->url(1) }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    1
                </a>
                @if($start > 2)
                    <span class="px-2 text-gray-500">...</span>
                @endif
            @endif

            {{-- Loop nomor halaman --}}
            @for ($i = $start; $i <= $end; $i++)
                @if ($i == $current)
                    <button class="px-3 py-1 text-sm text-white bg-blue-600 border border-blue-600 rounded">
                        {{ $i }}
                    </button>
                @else
                    <a href="{{ $paginator->url($i) }}" 
                       class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                        {{ $i }}
                    </a>
                @endif
            @endfor

            {{-- Tampilkan ... + halaman terakhir jika perlu --}}
            @if($end < $last)
                @if($end < $last - 1)
                    <span class="px-2 text-gray-500">...</span>
                @endif
                <a href="{{ $paginator->url($last) }}" 
                   class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition pagination-link">
                    {{ $last }}
                </a>
            @endif

            {{-- Next Button --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" 
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