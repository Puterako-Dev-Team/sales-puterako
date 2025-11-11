<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-green-500 text-white">
            <th class="px-2 py-2 font-semibold text-center">No</th>
            <th class="px-2 py-2 font-semibold text-center">
                <button class="sort-button flex items-center justify-center gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition"
                        data-column="nama_mitra" data-direction="{{ request('sort') == 'nama_mitra' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Nama Perusahaan
                    @if(request('sort') == 'nama_mitra')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M5 12l5-5 5 5H5z"/></svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold text-center">
                <button class="sort-button flex items-center justify-center gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition"
                        data-column="provinsi" data-direction="{{ request('sort') == 'provinsi' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Provinsi
                    @if(request('sort') == 'provinsi')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M5 12l5-5 5 5H5z"/></svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold text-center">
                <button class="sort-button flex items-center justify-center gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition"
                        data-column="kota" data-direction="{{ request('sort') == 'kota' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Kota
                    @if(request('sort') == 'kota')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M5 12l5-5 5 5H5z"/></svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold text-center">
                <button class="sort-button flex items-center justify-center gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition"
                        data-column="alamat" data-direction="{{ request('sort') == 'alamat' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Alamat
                    @if(request('sort') == 'alamat')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/></svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M5 12l5-5 5 5H5z"/></svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold text-center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($mitras as $index => $m)
            <tr class="border-b transition hover:bg-gray-50">
                <td class="px-2 py-2 text-center">{{ $mitras->firstItem() + $index }}</td>
                <td class="px-2 py-2">{{ $m->nama_mitra }}</td>
                <td class="px-2 py-2 text-center">{{ $m->provinsi }}</td>
                <td class="px-2 py-2 text-center">{{ $m->kota }}</td>
                <td class="px-2 py-2">{{ $m->alamat }}</td>
                <td class="px-2 py-2 text-center">
                    <div class="flex gap-1 justify-center">
                        <button class="bg-yellow-500 text-white px-2 py-2 rounded text-xs hover:bg-yellow-600" title="Edit">
                            <x-lucide-square-pen class="w-5 h-5 inline" />
                        </button>
                        <button class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600" title="Hapus">
                            <x-lucide-trash-2 class="w-5 h-5 inline" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-8">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        <x-lucide-search-x class="w-8 h-8" />
                        <span>Belum ada mitra</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>