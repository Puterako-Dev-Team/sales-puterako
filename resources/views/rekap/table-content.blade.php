<!-- filepath: resources/views/rekap/table-content.blade.php -->
<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-green-500 text-white">
            <th class="px-2 py-2 font-semibold text-center rounded-tl-md">No</th>
            <th class="px-2 py-2 font-semibold">Tanggal Buat</th>
            <th class="px-2 py-2 font-semibold">Nama Rekap</th>
            <th class="px-2 py-2 font-semibold">No Penawaran</th>
            <th class="px-2 py-2 font-semibold">Perusahaan</th>
            <th class="px-2 py-2 font-semibold">Dibuat Oleh</th>
            {{-- <th class="px-2 py-2 font-semibold">Total Item</th> --}}
            <th class="px-2 py-2 font-semibold text-center rounded-tr-md">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rekaps as $index => $rekap)
            <tr class="border-b transition hover:bg-gray-50">
                <td class="px-2 py-2 ">{{ $rekaps->firstItem() + $index }}</td>
                <td class="px-2 py-2 ">{{ $rekap->created_at->format('Y/m/d') }}</td>
                <td class="px-2 py-2">{{ $rekap->nama }}</td>
                <td class="px-2 py-2 ">{{ $rekap->no_penawaran }}</td>
                <td class="px-2 py-2">{{ $rekap->nama_perusahaan }}</td>
                <td class="px-2 py-2">{{ $rekap->user ? $rekap->user->name : 'N/A' }}</td>
                {{-- <td class="px-2 py-2 text-center">
                    <span class="inline-block px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                        {{ $rekap->items->sum(fn($item) => is_array($item->detail) ? count($item->detail) : 0) }} detail
                    </span>
                </td> --}}
                <td class="px-2 py-2 text-center">
                    <div class="flex gap-1 justify-center">
                        <a href="{{ route('rekap.show', $rekap->id) }}"
                            class="bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition"
                            title="Lihat Detail">
                            <x-lucide-eye class="w-5 h-5 inline" />
                        </a>
                        <button class="btn-edit-rekap bg-yellow-500 text-white px-2 py-2 rounded flex items-center gap-1 text-xs hover:bg-yellow-700 transition"
                            data-id="{{ $rekap->id }}" title="Edit">
                            <x-lucide-square-pen class="w-5 h-5 inline" />
                        </button>
                        <button class="btn-delete-rekap bg-red-500 text-white px-2 py-2 rounded hover:bg-red-700 transition"
                            data-id="{{ $rekap->id }}" title="Hapus Data">
                            <x-lucide-trash-2 class="w-5 h-5 inline" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="py-8">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        @if(request()->hasAny(['tanggal_dari', 'nama', 'no_penawaran', 'nama_perusahaan', 'user_id']))
                            <x-lucide-search-x class="w-8 h-8" />
                            <span>Tidak ada data rekap yang sesuai dengan filter</span>
                        @else
                            <x-lucide-file-bar-chart class="w-8 h-8" />
                            <span class="text-lg font-medium">Belum ada data rekap</span>
                            <span class="text-sm">Mulai buat rekap pertama Anda</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>