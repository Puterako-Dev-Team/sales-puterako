<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-green-500 text-white">
            <th class="px-2 py-2 font-semibold text-center">No</th>
            <th class="px-2 py-2 font-semibold ">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="created_at" data-direction="{{ request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Tanggal
                    @if(request('sort') == 'created_at')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold ">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="no_penawaran" data-direction="{{ request('sort') == 'no_penawaran' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    No Penawaran
                    @if(request('sort') == 'no_penawaran')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold ">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="perihal" data-direction="{{ request('sort') == 'perihal' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Perihal
                    @if(request('sort') == 'perihal')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold ">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="nama_perusahaan" data-direction="{{ request('sort') == 'nama_perusahaan' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Perusahaan
                    @if(request('sort') == 'nama_perusahaan')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold ">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="pic_perusahaan" data-direction="{{ request('sort') == 'pic_perusahaan' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    PIC Perusahaan
                    @if(request('sort') == 'pic_perusahaan')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="pic_admin" data-direction="{{ request('sort') == 'pic_admin' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    PIC Admin
                    @if(request('sort') == 'pic_admin')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold">
                <button class="sort-button flex justify-between gap-1 w-full hover:bg-green-600 rounded px-2 py-1 transition" 
                        data-column="status" data-direction="{{ request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc' }}">
                    Status
                    @if(request('sort') == 'status')
                        @if(request('direction') == 'asc')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                            </svg>
                        @endif
                    @else
                        <svg class="w-4 h-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 12l5-5 5 5H5z"/>
                        </svg>
                    @endif
                </button>
            </th>
            <th class="px-2 py-2 font-semibold text-center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($penawarans as $index => $p)
            <tr class="border-b transition hover:bg-gray-50">
                <td class="px-2 py-2 ">{{ $penawarans->firstItem() + $index }}</td>
                <td class="px-2 py-2 ">{{ $p->created_at->format('Y/m/d') }}</td>
                <td class="px-2 py-2 ">{{ $p->no_penawaran }}</td>
                <td class="px-2 py-2">{{ $p->perihal }}</td>
                <td class="px-2 py-2">{{ $p->nama_perusahaan }}</td>
                <td class="px-2 py-2">{{ $p->pic_perusahaan }}</td>
                <td class="px-2 py-2">{{ $p->user ? $p->user->name : 'N/A' }}</td>
                <td class="px-2 py-2">
                    @if ($p->status === 'draft')
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                            Draft
                        </span>
                    @elseif($p->status === 'lost')
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                            Lost
                        </span>
                    @elseif($p->status === 'success')
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                            Success
                        </span>
                    @else
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">
                            {{ $p->status }}
                        </span>
                    @endif
                </td>
                <td class="px-2 py-2 text-center">
                    <div class="flex gap-1 justify-center">
                        @if ($p->tiket)
                            <button class="bg-gray-300 text-gray-700 px-2 py-1 rounded flex items-center gap-1 text-xs" disabled>
                                <x-lucide-ticket class="w-4 h-4" />
                                Tiket
                            </button>
                        @else
                            <a href="{{ route('penawaran.show', ['id' => $p->id_penawaran]) }}"
                                class="bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition"
                                title="Lihat Detail">
                                <x-lucide-file-text class="w-5 h-5 inline" />
                            </a>
                            <button class="btn-edit bg-yellow-500 text-white px-2 py-2 rounded flex items-center gap-1 text-xs hover:bg-yellow-700 transition"
                            data-id="{{ $p->id_penawaran }}" title="Edit">
                            <x-lucide-square-pen class="w-5 h-5 inline" />
                        </button>
                        <a href="{{ route('penawaran.followUp', $p->id_penawaran) }}"
                            class="bg-blue-600 text-white px-2 py-2 rounded flex items-center gap-1 text-xs hover:bg-blue-700 transition"
                            title="Follow Up">
                            <x-lucide-phone-outgoing class="w-5 h-5 inline" />
                        </a>
                        @endif
                        <button class="btn-delete bg-red-500 text-white px-2 py-2 rounded hover:bg-red-700 transition"
                            data-id="{{ $p->id_penawaran }}" title="Hapus Data">
                            <x-lucide-trash-2 class="w-5 h-5 inline" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="py-8">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        @if(request()->hasAny(['tanggal_dari', 'no_penawaran', 'nama_perusahaan', 'status', 'pic_admin']))
                            <x-lucide-search-x class="w-8 h-8" />
                            <span>Tidak ada data yang sesuai dengan filter</span>
                        @else
                            <x-lucide-ticket class="w-8 h-8" />
                            <span>Belum ada tiket penawaran</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>