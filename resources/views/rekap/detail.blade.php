{{-- filepath: resources/views/rekap/detail.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-8">

        <div class="flex items-center p-8 text-gray-600 mb-2">
            <a href="{{ route('rekap.list') }}" class="flex items-center hover:text-green-600">
                <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                List Rekap
            </a>
            <span class="mx-2">/</span>
            <span class="font-semibold">Detail Rekap</span>
        </div>

        {{-- DIV ATAS: Preview Item --}}
        <div class="overflow-auto bg-white p-6 rounded shadow">
            <table class="min-w-full bg-white text-sm border border-gray-300 rounded">
                <thead>
                    <tr class="bg-green-100">
                        <th rowspan="2" class="px-2 py-2 border border-gray-300 font-semibold">Detail</th>
                        @foreach ($previewKategori as $kategori)
                            <th class="px-2 py-2 border border-gray-300 font-semibold text-center"
                                colspan="{{ count($kategori['items']) }}">
                                {{ $kategori['nama'] }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="bg-green-50">
                        @foreach ($previewKategori as $kategori)
                            @foreach ($kategori['items'] as $item)
                                <th class="px-2 py-2 border border-gray-300 font-normal text-center">
                                    {{ $item['nama_item'] }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($previewDetails as $detailName)
                        <tr>
                            <td class="px-2 py-2 border border-gray-300 font-semibold">{{ $detailName }}</td>
                            @foreach ($previewKategori as $kategori)
                                @foreach ($kategori['items'] as $item)
                                    @php
                                        $found = collect($item['detail'])->firstWhere('nama_detail', $detailName);
                                    @endphp
                                    <td class="px-2 py-2 border border-gray-300 text-center">
                                        {{ $found['jumlah'] ?? '-' }}
                                        <br>
                                        <span class="text-xs text-gray-500">{{ $found['keterangan'] ?? '' }}</span>
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-green-100 font-semibold">
                        <td class="px-2 py-2 border border-gray-300 text-center">Total</td>
                        @foreach ($previewKategori as $kategori)
                            @foreach ($kategori['items'] as $item)
                                @php
                                    $total = collect($item['detail'])->sum('jumlah');
                                    $rounded = $total > 0 ? ceil($total) : 0;
                                @endphp
                                <td class="px-2 py-2 border border-gray-300 text-center">
                                    {{ $rounded }}
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- DIV BAWAH: Form Input --}}
        <div class="bg-white p-6 rounded shadow mt-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-green-700">
                    {{ $rekap->items->count() > 0 ? 'Data Item Rekap' : 'Tambah Item Rekap' }}
                </h3>
                <div class="flex gap-2">
                    @if($rekap->items->count() > 0)
                        <button type="button" id="btnEditItem"
                            class="bg-yellow-500 text-white px-4 py-2 rounded font-semibold hover:bg-yellow-600">
                            Edit Item
                        </button>
                        <button type="button" id="btnCancelEdit"
                            class="bg-gray-400 text-white px-4 py-2 rounded font-semibold hover:bg-gray-600 hidden">
                            Batal Edit
                        </button>
                    @endif
                    <button type="submit" form="itemForm" id="btnSimpan"
                        class="bg-green-600 text-white px-4 py-2 rounded font-semibold hover:bg-green-700 {{ $rekap->items->count() > 0 ? 'hidden' : '' }}">
                        Simpan Semua Item
                    </button>
                </div>
            </div>
            <form method="POST"
                action="{{ $isEdit ? route('rekap.updateItems', $rekap->id) : route('rekap.addItem', $rekap->id) }}"
                id="itemForm">
                @csrf
                <div id="item-list">
                    @if($rekap->items->count() > 0)
                        {{-- Tampilkan data existing dalam mode readonly --}}
                        @foreach($rekap->items as $idx => $item)
                            <div class="item-row mb-6 bg-gray-50 rounded-lg shadow-sm p-4 border border-gray-200">
                                {{-- Kategori & Nama Item (Sejajar) --}}
                                <div class="flex gap-4 mb-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1 text-gray-700">Kategori</label>
                                        <select name="items[{{ $idx }}][rekap_kategori_id]"
                                            class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed" 
                                            disabled required>
                                            @foreach ($kategoris as $kategori)
                                                <option value="{{ $kategori->id }}" 
                                                    {{ $kategori->id == $item->rekap_kategori_id ? 'selected' : '' }}>
                                                    {{ $kategori->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1 text-gray-700">Nama Item</label>
                                        <input type="text" name="items[{{ $idx }}][nama_item]"
                                            class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                            value="{{ $item->nama_item }}" readonly required>
                                    </div>
                                </div>

                                {{-- Detail List --}}
                                <div class="detail-list">
                                    @foreach($item->detail ?? [] as $didx => $detail)
                                        <div class="flex gap-4 mb-2 detail-row">
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium mb-1 text-gray-700">Nama Detail</label>
                                                <input type="text" name="items[{{ $idx }}][detail][{{ $didx }}][nama_detail]"
                                                    class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                                    value="{{ $detail['nama_detail'] }}" readonly required>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                                                <input type="number" name="items[{{ $idx }}][detail][{{ $didx }}][jumlah]"
                                                    class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                                    value="{{ $detail['jumlah'] }}" readonly min="0.01" step="any" required>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                                                <input type="text" name="items[{{ $idx }}][detail][{{ $didx }}][keterangan]"
                                                    class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                                    value="{{ $detail['keterangan'] ?? '' }}" readonly>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- Initial Empty Item untuk data baru --}}
                        <div class="item-row mb-6 bg-gray-50 rounded-lg shadow-sm p-4 border border-gray-200">
                            {{-- Kategori & Nama Item (Sejajar) --}}
                            <div class="flex gap-4 mb-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Kategori</label>
                                    <select name="items[0][rekap_kategori_id]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($kategoris as $kategori)
                                            <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Nama Item</label>
                                    <input type="text" name="items[0][nama_item]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                        placeholder="Nama Item" required>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                                        <x-lucide-minus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            {{-- Detail List --}}
                            <div class="detail-list">
                                <div class="flex gap-4 mb-2 detail-row">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1 text-gray-700">Nama Detail</label>
                                        <input type="text" name="items[0][detail][0][nama_detail]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            placeholder="Nama Detail (misal: Cam 1)" required>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                                        <input type="number" name="items[0][detail][0][jumlah]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            placeholder="Jumlah" min="0.01" step="any" required>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                                        <input type="text" name="items[0][detail][0][keterangan]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            placeholder="Keterangan (misal: Lantai 1)">
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" class="btn-remove-detail bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                                            <x-lucide-minus class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-add-detail bg-green-500 text-white px-4 py-2 rounded-lg mt-2 hover:bg-green-600 transition">
                                <x-lucide-plus class="w-5 h-5 inline" /> Detail
                            </button>
                        </div>
                    @endif
                </div>
                <button type="button"
                    class="btn-add-item bg-green-500 text-white px-4 py-2 rounded-lg mt-4 hover:bg-green-600 transition flex items-center gap-2 {{ $rekap->items->count() > 0 ? 'hidden' : '' }}">
                    <x-lucide-plus class="w-5 h-5 inline" /> Item
                </button>
            </form>
        </div>
    </div>

    <script>
        const hasExistingData = {{ $rekap->items->count() > 0 ? 'true' : 'false' }};
        
        // Tambah item
        document.querySelector('.btn-add-item')?.addEventListener('click', function() {
            let idx = document.querySelectorAll('#item-list .item-row').length;
            let html = `
    <div class="item-row mb-6 bg-gray-50 rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Kategori</label>
                <select name="items[${idx}][rekap_kategori_id]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                    <option value="">Pilih Kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-1 text-gray-700">Nama Item</label>
                <input type="text" name="items[${idx}][nama_item]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Nama Item" required>
            </div>
            <div class="flex items-end">
                <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                    <x-lucide-minus class="w-5 h-5" />
                </button>
            </div>
        </div>
        <div class="detail-list">
            <div class="flex gap-4 mb-2 detail-row">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Nama Detail</label>
                    <input type="text" name="items[${idx}][detail][0][nama_detail]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Nama Detail" required>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                    <input type="number" name="items[${idx}][detail][0][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Jumlah" min="0.01" step="any" required>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                    <input type="text" name="items[${idx}][detail][0][keterangan]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Keterangan">
                </div>
                <div class="flex items-end">
                    <button type="button" class="btn-remove-detail bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                        <x-lucide-minus class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
        <button type="button" class="btn-add-detail bg-green-500 text-white px-4 py-2 rounded-lg mt-2 hover:bg-green-600 transition">
            <x-lucide-plus class="w-5 h-5 inline" /> Detail
        </button>
    </div>
    `;
            document.getElementById('item-list').insertAdjacentHTML('beforeend', html);
        });

        // Tambah detail per item
        document.getElementById('item-list').addEventListener('click', function(e) {
            if (e.target.closest('.btn-add-detail')) {
                const itemRow = e.target.closest('.item-row');
                const detailList = itemRow.querySelector('.detail-list');
                const itemIdx = Array.from(document.querySelectorAll('.item-row')).indexOf(itemRow);
                const detailIdx = detailList.querySelectorAll('.detail-row').length;
                const html = `
            <div class="flex gap-4 mb-2 detail-row">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Nama Detail</label>
                    <input type="text" name="items[${itemIdx}][detail][${detailIdx}][nama_detail]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Nama Detail" required>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                    <input type="number" name="items[${itemIdx}][detail][${detailIdx}][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Jumlah" min="0.01" step="any" required>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                    <input type="text" name="items[${itemIdx}][detail][${detailIdx}][keterangan]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Keterangan">
                </div>
                <div class="flex items-end">
                    <button type="button" class="btn-remove-detail bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                        <x-lucide-minus class="w-5 h-5" />
                    </button>
                </div>
            </div>
        `;
                detailList.insertAdjacentHTML('beforeend', html);
            }
            // Hapus detail
            if (e.target.closest('.btn-remove-detail')) {
                e.target.closest('.detail-row').remove();
            }
            // Hapus item
            if (e.target.closest('.btn-remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });

        // Edit item: aktifkan form untuk edit
        const btnEdit = document.getElementById('btnEditItem');
        const btnCancel = document.getElementById('btnCancelEdit');
        const btnSimpan = document.getElementById('btnSimpan');
        const btnAddItem = document.querySelector('.btn-add-item');
        const itemList = document.getElementById('item-list');

        if (btnEdit) {
            btnEdit.addEventListener("click", function() {
                const items = @json($rekap->items);
                const kategoris = @json($kategoris);

                let html = "";
                items.forEach((item, idx) => {
                    html += `
                <div class="item-row mb-6 bg-gray-50 rounded-lg shadow-sm p-4 border border-gray-200">
                    <div class="flex gap-4 mb-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700">Kategori</label>
                            <select name="items[${idx}][rekap_kategori_id]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                <option value="">Pilih Kategori</option>
                                ${kategoris.map(k => `
                                    <option value="${k.id}" ${k.id == item.rekap_kategori_id ? 'selected' : ''}>${k.nama}</option>
                                `).join("")}
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1 text-gray-700">Nama Item</label>
                            <input type="text" name="items[${idx}][nama_item]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" value="${item.nama_item}" required>
                        </div>
                        <div class="flex items-end">
                            <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                                <x-lucide-minus class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div class="detail-list">
                        ${item.detail.map((d, didx) => `
                            <div class="flex gap-4 mb-2 detail-row">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Nama Detail</label>
                                    <input type="text" name="items[${idx}][detail][${didx}][nama_detail]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" value="${d.nama_detail}" required>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                                    <input type="number" name="items[${idx}][detail][${didx}][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" value="${d.jumlah}" min="0.01" step="any" required>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                                    <input type="text" name="items[${idx}][detail][${didx}][keterangan]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" value="${d.keterangan ?? ''}">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" class="btn-remove-detail bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                                        <x-lucide-minus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        `).join("")}
                    </div>

                    <button type="button" class="btn-add-detail bg-green-500 text-white px-4 py-2 rounded-lg mt-2 hover:bg-green-600 transition">
                        <x-lucide-plus class="w-5 h-5 inline" /> Detail
                    </button>
                </div>
                `;
                });

                itemList.innerHTML = html;

                // toggle tombol
                btnEdit.classList.add("hidden");
                btnCancel.classList.remove("hidden");
                btnSimpan.classList.remove("hidden");
                if (btnAddItem) btnAddItem.classList.remove("hidden");
            });
        }

        // Cancel edit: kembalikan ke readonly
        if (btnCancel) {
            btnCancel.addEventListener("click", function() {
                location.reload(); // Reload untuk reset ke state awal
            });
        }
    </script>
@endsection