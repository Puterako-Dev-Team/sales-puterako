<!-- filepath: resources/views/rekap/approve-table.blade.php -->
<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-green-500 text-white">
            <th class="px-2 py-2 font-semibold text-center rounded-tl-md">No</th>
            <th class="px-2 py-2 font-semibold text-left">Nama Rekap</th>
            <th class="px-2 py-2 font-semibold text-left">No Penawaran</th>
            <th class="px-2 py-2 font-semibold text-left">Perusahaan</th>
            <th class="px-2 py-2 font-semibold text-left">Dibuat Oleh</th>
            <th class="px-2 py-2 font-semibold text-left">Tanggal</th>
            <th class="px-2 py-2 font-semibold text-center rounded-tr-md">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rekaps as $index => $rekap)
            <tr class="border-b transition hover:bg-gray-50">
                <td class="px-2 py-2 text-center">{{ $rekaps->firstItem() + $index }}</td>
                <td class="px-2 py-2 text-left">
                    <a href="{{ route('rekap.show', $rekap->id) }}" class="text-green-600 hover:text-green-800 underline">
                        {{ $rekap->nama }}
                    </a>
                </td>
                <td class="px-2 py-2 text-left">{{ $rekap->no_penawaran ?? '-' }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->nama_perusahaan }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->user ? $rekap->user->name : 'N/A' }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->created_at->format('Y/m/d') }}</td>
                <td class="px-2 py-2 text-center">
                    <button class="btn-approve-rekap bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600 transition"
                        data-id="{{ $rekap->id }}" title="Setujui">
                        <x-lucide-check class="w-5 h-5 inline" />
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-8">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        @if(request()->hasAny(['tanggal_dari', 'nama', 'no_penawaran', 'nama_perusahaan', 'pic_admin']))
                            <x-lucide-search-x class="w-8 h-8" />
                            <span>Tidak ada data rekap yang menunggu persetujuan</span>
                        @else
                            <x-lucide-check-circle class="w-8 h-8" />
                            <span class="text-lg font-medium">Semua rekap sudah disetujui</span>
                            <span class="text-sm">Tidak ada rekap yang menunggu persetujuan</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($rekaps->hasPages())
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
        {{ $rekaps->links() }}
    </div>
@endif

{{-- History Section Separator --}}
@if(isset($historyRekaps) && $historyRekaps->count() > 0)
<div class="border-t-4 border-gray-300 my-6"></div>
<div class="bg-gray-100 px-4 py-3 rounded-t-lg border-b border-gray-300">
    <h3 class="text-lg font-semibold text-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Riwayat Approval
    </h3>
</div>
<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-gray-500 text-white">
            <th class="px-2 py-2 font-semibold text-center rounded-tl-md">No</th>
            <th class="px-2 py-2 font-semibold text-left">Nama Rekap</th>
            <th class="px-2 py-2 font-semibold text-left">No Penawaran</th>
            <th class="px-2 py-2 font-semibold text-left">Perusahaan</th>
            <th class="px-2 py-2 font-semibold text-left">Dibuat Oleh</th>
            <th class="px-2 py-2 font-semibold text-left">Tgl Permintaan</th>
            <th class="px-2 py-2 font-semibold text-left">Tgl Disetujui</th>
            <th class="px-2 py-2 font-semibold text-center rounded-tr-md">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($historyRekaps as $index => $rekap)
            <tr class="border-b transition hover:bg-gray-50 bg-gray-50">
                <td class="px-2 py-2 text-center">{{ $index + 1 }}</td>
                <td class="px-2 py-2 text-left">
                    <a href="{{ route('rekap.show', $rekap->id) }}" class="text-green-600 hover:text-green-800 underline">
                        {{ $rekap->nama }}
                    </a>
                </td>
                <td class="px-2 py-2 text-left">{{ $rekap->no_penawaran ?? '-' }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->nama_perusahaan }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->user ? $rekap->user->name : 'N/A' }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->created_at->format('Y/m/d H:i') }}</td>
                <td class="px-2 py-2 text-left">{{ $rekap->updated_at->format('Y/m/d H:i') }}</td>
                <td class="px-2 py-2 text-center">
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Approved
                    </span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
