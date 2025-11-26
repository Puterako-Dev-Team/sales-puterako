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
        <div class="overflow-auto">
            <table class="min-w-full bg-white text-sm border border-gray-300">
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
                                @endphp
                                <td class="px-2 py-2 border border-gray-300 text-center">
                                    {{ $total > 0 ? $total : '-' }}
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
                <h3 class="text-xl font-bold text-green-700">Tambah Item Rekap</h3>
                <div class="flex gap-2">
                    <button type="button" id="btnEditItem"
                        class="bg-yellow-500 text-white px-4 py-2 rounded font-semibold hover:bg-yellow-600 {{ $rekap->items->count() ? '' : 'hidden' }}">
                        Edit Item
                    </button>
                    <button type="button" id="btnCancelEdit"
                        class="bg-gray-400 text-white px-4 py-2 rounded font-semibold hover:bg-gray-600 hidden">
                        Batal Edit
                    </button>
                    <button type="submit" form="itemForm"
                        class="bg-green-600 text-white px-4 py-2 rounded font-semibold hover:bg-green-700">
                        Simpan Semua Item
                    </button>
                </div>
            </div>
            <form method="POST"
                action="{{ $isEdit ? route('rekap.updateItems', $rekap->id) : route('rekap.addItem', $rekap->id) }}"
                id="itemForm">
                @csrf
                <div id="item-list">
                    {{-- Initial Empty Item --}}
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
                                <button type="button"
                                    class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
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
                                        placeholder="Jumlah" min="1" required>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                                    <input type="text" name="items[0][detail][0][keterangan]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                        placeholder="Keterangan (misal: Lantai 1)">
                                </div>
                                <div class="flex items-end">
                                    <button type="button"
                                        class="btn-remove-detail bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition">
                                        <x-lucide-minus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button"
                            class="btn-add-detail bg-green-500 text-white px-4 py-2 rounded-lg mt-2 hover:bg-green-600 transition">
                            <x-lucide-plus class="w-5 h-5 inline" /> Detail
                        </button>
                    </div>
                </div>
                <button type="button"
                    class="btn-add-item bg-green-500 text-white px-4 py-2 rounded-lg mt-4 hover:bg-green-600 transition flex items-center gap-2">
                    <x-lucide-plus class="w-5 h-5 inline" /> Item
                </button>
            </form>
        </div>
    </div>

    <script>
        // Tambah item
        document.querySelector('.btn-add-item').addEventListener('click', function() {
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
                    <input type="number" name="items[${idx}][detail][0][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Jumlah" min="1" required>
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
            updatePreview();
        });

        // Tambah detail per item
        document.getElementById('item-list').addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-add-detail')) {
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
                    <input type="number" name="items[${itemIdx}][detail][${detailIdx}][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Jumlah" min="1" required>
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
                updatePreview();
            }
            // Hapus detail
            if (e.target.classList.contains('btn-remove-detail')) {
                e.target.closest('.detail-row').remove();
                updatePreview();
            }
            // Hapus item
            if (e.target.classList.contains('btn-remove-item')) {
                e.target.closest('.item-row').remove();
                updatePreview();
            }
        });

        // Preview item di atas
        function updatePreview() {
            const itemRows = document.querySelectorAll('#item-list .item-row');
            const previewList = document.getElementById('preview-list');
            const fallback = document.getElementById('fallback-preview');
            if (previewList) {
                previewList.innerHTML = '';
                let items = [];
                itemRows.forEach(function(row) {
                    const kategori = row.querySelector('select').selectedOptions[0]?.text || '';
                    const nama = row.querySelector('input[name*="[nama_item]"]').value;
                    const detailRows = row.querySelectorAll('.detail-row');
                    detailRows.forEach(function(dRow) {
                        const nama_detail = dRow.querySelector('input[name*="[nama_detail]"]').value;
                        const jumlah = dRow.querySelector('input[name*="[jumlah]"]').value;
                        const keterangan = dRow.querySelector('input[name*="[keterangan]"]').value;
                        if (nama && nama_detail && jumlah) {
                            items.push({
                                kategori,
                                nama,
                                nama_detail,
                                jumlah,
                                keterangan
                            });
                        }
                    });
                });
                if (items.length === 0 && fallback) {
                    fallback.classList.remove('hidden');
                    previewList.classList.add('hidden');
                } else if (fallback) {
                    fallback.classList.add('hidden');
                    previewList.classList.remove('hidden');
                    items.forEach(function(item, idx) {
                        previewList.innerHTML += `<li class="border-b py-2 flex flex-col">
                    <span class="font-semibold">${item.kategori} - ${item.nama}</span>
                    <span>Detail: ${item.nama_detail} | Jumlah: <span class="text-green-600 font-bold">${item.jumlah}</span> ${item.keterangan ? '| ' + item.keterangan : ''}</span>
                </li>`;
                    });
                }
            }
        }

        // Edit item: fetch data ke input
        const btnEdit = document.getElementById('btnEditItem');
        const btnCancel = document.getElementById('btnCancelEdit');
        const itemList = document.getElementById('item-list');

        // ---- Helper: Build satu item kosong ----
        function buildEmptyItem() {
            return `
        <div class="item-row mb-6 bg-gray-50 rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex gap-4 mb-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Kategori</label>
                    <select name="items[0][rekap_kategori_id]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($kategoris as $kategori)
                            <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1 text-gray-700">Nama Item</label>
                    <input type="text" name="items[0][nama_item]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Nama Item" required>
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
                        <input type="text" name="items[0][detail][0][nama_detail]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Nama Detail" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1 text-gray-700">Jumlah</label>
                        <input type="number" name="items[0][detail][0][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Jumlah" min="1" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1 text-gray-700">Keterangan</label>
                        <input type="text" name="items[0][detail][0][keterangan]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" placeholder="Keterangan">
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
        }

        // ---- EDIT MODE ----
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
                                    <input type="number" name="items[${idx}][detail][${didx}][jumlah]" class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" value="${d.jumlah}" min="1" required>
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

                updatePreview();
            });
        }

        // ---- CANCEL EDIT ----
        btnCancel.addEventListener("click", function() {
            itemList.innerHTML = buildEmptyItem();
            btnCancel.classList.add("hidden");

            // hanya show Edit kalau memang ada item sebelumnya
            if (@json($rekap->items->count()) > 0) {
                btnEdit.classList.remove("hidden");
            }

            updatePreview();
        });

        // Update preview setiap input berubah
        document.getElementById('item-list').addEventListener('input', updatePreview);
        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
@endsection