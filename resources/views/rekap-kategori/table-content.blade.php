<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-green-500 text-white">
                <th class="px-4 py-3 text-left font-semibold rounded-tl-md">
                    <button class="sort-button flex items-center gap-1 hover:bg-green-600 rounded px-2 py-1 transition"
                            data-column="nama" data-direction="{{ request('sort') == 'nama' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                        Nama Kategori
                        @if(request('sort') == 'nama')
                            @if(request('direction') == 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3v18l7-7 7 7V3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17 17V3l-7 7-7-7v14z"/>
                                </svg>
                            @endif
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 12l5-5 5 5H5z"/>
                            </svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-left font-semibold">Jumlah Item</th>
                <th class="px-4 py-3 text-left font-semibold">
                    <button class="sort-button flex items-center gap-1 hover:bg-green-600 rounded px-2 py-1 transition"
                            data-column="created_at" data-direction="{{ request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                        Dibuat
                        @if(request('sort') == 'created_at')
                            @if(request('direction') == 'asc')
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3v18l7-7 7 7V3z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17 17V3l-7 7-7-7v14z"/>
                                </svg>
                            @endif
                        @else
                            <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 12l5-5 5 5H5z"/>
                            </svg>
                        @endif
                    </button>
                </th>
                <th class="px-4 py-3 text-center font-semibold rounded-tr-md">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($kategoris as $kategori)
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $kategori->nama }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                            {{ $kategori->items()->count() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $kategori->created_at->format('d M Y, H:i') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <button class="btn-edit bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition flex items-center gap-1"
                                    data-id="{{ $kategori->id }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button class="btn-delete bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition flex items-center gap-1"
                                    data-id="{{ $kategori->id }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            Tidak ada data kategori
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
