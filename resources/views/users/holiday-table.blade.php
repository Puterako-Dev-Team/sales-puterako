<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-green-500 text-white">
                <th class="px-4 py-3 text-left font-semibold rounded-tl-md">No</th>
                <th class="px-4 py-3 text-left font-semibold ">Tanggal Libur</th>
                <th class="px-4 py-3 text-left font-semibold">Nama Hari Libur</th>
                <th class="px-4 py-3 text-left font-semibold">Status</th>
                <th class="px-4 py-3 text-center font-semibold rounded-tr-md">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($holidays as $holiday)
                <tr class="border-b hover:bg-gray-50 bg-white transition-colors">
                    <td class="px-4 py-3">{{ $loop->iteration }}</td>
                    <td class="px-4 py-3">
                        <span class="font-medium">
                            {{ \Carbon\Carbon::parse($holiday->tanggal_libur)->locale('id')->isoFormat('DD MMMM YYYY') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $holiday->nama_libur }}</td>
                    <td class="px-4 py-3">
                        @if($holiday->libur_nasional)
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Libur Nasional</span>
                        @else
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Cuti Bersama</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-2 justify-center">
                            <button onclick="showEditModal({{ $holiday->id }}, '{{ $holiday->tanggal_libur }}', '{{ addslashes($holiday->nama_libur) }}', {{ $holiday->libur_nasional ? '1' : '0' }})"
                                class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600 transition-colors"
                                title="Ubah Status">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-gray-500">
                        Belum ada data libur untuk tahun ini
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>