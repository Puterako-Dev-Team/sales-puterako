{{--
    =====================================================
    REFERENCE FILE - OLD FORM INPUT (DO NOT INCLUDE)
    =====================================================
    This file contains the old form-based input code 
    that was replaced by the Survey Spreadsheet.
    Kept for reference only.
    =====================================================
--}}

{{-- DIV BAWAH: Form Input (OLD - replaced by Survey Spreadsheet) --}}
<div class="bg-white p-6 rounded shadow mt-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-green-700">
            {{ $rekap->items->count() > 0 ? 'Data Item Rekap' : 'Tambah Item Rekap' }}
        </h3>
        <div class="flex gap-2">
            @if($rekap->items->count() > 0 && $rekap->status !== 'approved')
                <button type="button" id="btnEditItem"
                    class="bg-yellow-500 text-white px-4 py-2 rounded font-semibold hover:bg-yellow-600">
                    Edit Item
                </button>
                <button type="button" id="btnCancelEdit"
                    class="bg-gray-400 text-white px-4 py-2 rounded font-semibold hover:bg-gray-600 hidden">
                    Batal Edit
                </button>
            @endif
            @if($rekap->status !== 'approved')
                <button type="button" id="btnSimpan"
                    class="bg-green-600 text-white px-4 py-2 rounded font-semibold hover:bg-green-700 {{ $rekap->items->count() > 0 ? 'hidden' : '' }}">
                    Simpan Semua Item
                </button>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
            <ul class="text-red-700 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const errors = @json($errors->all());
                if (errors.length > 0) {
                    errors.forEach(error => {
                        // Convert technical error messages to user-friendly ones
                        let message = error;
                        
                        // Map specific field errors to user-friendly messages
                        if (error.includes('nama_area') || error.includes('Nama Area')) {
                            message = 'Nama Area harus diisi';
                        } 
                        // Handle items.X.rekap_kategori_id
                        else if (error.includes('rekap_kategori_id') || error.includes('Kategori')) {
                            const match = error.match(/items\.(\d+)/);
                            const itemNum = match ? parseInt(match[1]) + 1 : '';
                            message = itemNum ? `Item ${itemNum}: Kategori harus dipilih` : 'Kategori harus dipilih untuk setiap item';
                        } 
                        // Handle items.X.tipes_id
                        else if (error.includes('tipes_id') || error.includes('Item tidak valid')) {
                            const match = error.match(/items\.(\d+)/);
                            const itemNum = match ? parseInt(match[1]) + 1 : '';
                            message = itemNum ? `Item ${itemNum}: Nama Item harus dipilih dari daftar atau buat yang baru` : 'Nama Item harus dipilih dari daftar atau buat yang baru';
                        } 
                        // Handle items.X.nama_item
                        else if (error.includes('nama_item') || error.includes('Nama Item')) {
                            const match = error.match(/items\.(\d+)/);
                            const itemNum = match ? parseInt(match[1]) + 1 : '';
                            message = itemNum ? `Item ${itemNum}: Nama Item harus diisi` : 'Nama Item harus diisi';
                        } 
                        // Handle items.X.jumlah
                        else if (error.includes('jumlah') || error.includes('Jumlah')) {
                            const match = error.match(/items\.(\d+)/);
                            const itemNum = match ? parseInt(match[1]) + 1 : '';
                            message = itemNum ? `Item ${itemNum}: Jumlah harus diisi dengan angka lebih dari 0` : 'Jumlah harus diisi dengan angka lebih dari 0';
                        } 
                        // Handle items.X.satuan_id
                        else if (error.includes('satuan_id') || error.includes('Satuan')) {
                            const match = error.match(/items\.(\d+)/);
                            const itemNum = match ? parseInt(match[1]) + 1 : '';
                            message = itemNum ? `Item ${itemNum}: Satuan harus dipilih` : 'Satuan harus dipilih untuk setiap item';
                        } 
                        else if (error.includes('sudah ada di rekap ini')) {
                            message = error;
                        } 
                        else if (error.includes('tidak boleh duplikat')) {
                            message = error;
                        }
                        
                        // Show toaster if available
                        if (typeof toastr !== 'undefined') {
                            toastr.error(message, 'Error');
                        }
                    });
                }
            });
        </script>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('rekap.updateItems', $rekap->id) : route('rekap.addItem', $rekap->id) }}"
        id="itemForm" novalidate>
        @csrf
        
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
            @if($rekap->items->count() > 0)
                {{-- Readonly mode --}}
                <div id="item-list">
                    @php
                        $groupedByAreaReadonly = $rekap->items->sortBy('id')->groupBy('nama_area');
                        // Preserve insertion order
                        $areaOrderReadonly = [];
                        foreach ($rekap->items->sortBy('id') as $item) {
                            if (!in_array($item->nama_area, $areaOrderReadonly)) {
                                $areaOrderReadonly[] = $item->nama_area;
                            }
                        }
                        $globalIdx = 0;
                    @endphp
                    @foreach($areaOrderReadonly as $area)
                        @if(isset($groupedByAreaReadonly[$area]))
                        @php $areaItems = $groupedByAreaReadonly[$area]; @endphp
                        <div class="mb-6">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Nama Area <span class="text-red-500">*</span></label>
                                <div class="border rounded-lg px-3 py-2 w-full bg-gray-100 text-gray-700">{{ $area }}</div>
                            </div>
                            @foreach($areaItems as $item)
                                <div class="item-row mb-4 p-4 bg-white rounded-lg border border-gray-300">
                                    <div class="flex gap-4 items-end">
                                        <div class="flex-1">
                                            <label class="block text-sm font-medium mb-2 text-gray-700">Kategori</label>
                                            <select name="items[{{ $globalIdx }}][rekap_kategori_id]"
                                                class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                                disabled>
                                                @foreach ($kategoris as $kategori)
                                                    <option value="{{ $kategori->id }}"
                                                        {{ $kategori->id == $item->rekap_kategori_id ? 'selected' : '' }}>
                                                        {{ $kategori->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Nama Item</label>
                                        <input type="text" name="items[{{ $globalIdx }}][nama_item]"
                                            class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                            value="{{ $item->tipe->nama ?? '-' }}" readonly>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Jumlah</label>
                                        <input type="number" name="items[{{ $globalIdx }}][jumlah]"
                                            class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                            value="{{ $item->jumlah }}" readonly min="0.01" step="any">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Satuan</label>
                                        <select name="items[{{ $globalIdx }}][satuan_id]"
                                            class="border rounded-lg px-3 py-2 w-full bg-gray-100 cursor-not-allowed"
                                            disabled>
                                            @foreach ($satuans as $satuan)
                                                <option value="{{ $satuan->id }}"
                                                    {{ $satuan->id == $item->satuan_id ? 'selected' : '' }}>
                                                    {{ $satuan->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <input type="hidden" name="items[{{ $globalIdx }}][tipes_id]" value="{{ $item->tipes_id }}">
                            </div>
                            @php $globalIdx++; @endphp
                        @endforeach
                        </div>
                        @endif
                    @endforeach
                </div>

                <div class="flex justify-between mt-6">
                    <button type="button" class="btn-add-area bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition flex items-center gap-2 font-semibold hidden">
                        <x-lucide-layout-grid class="w-5 h-5" /> Tambah Area
                    </button>
                    <button type="button" class="btn-add-item bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition flex items-center gap-2 font-semibold hidden">
                        <x-lucide-plus class="w-5 h-5" /> Tambah Item
                    </button>
                </div>
            @else
                {{-- Form mode untuk input baru dengan jspreadsheet --}}
                <div id="spreadsheet-container">
                    <div id="area-sections-container">
                        {{-- Area sections will be added dynamically --}}
                    </div>
                    <div class="flex justify-start mt-6">
                        <button type="button" id="btn-add-area-spreadsheet" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition flex items-center gap-2 font-semibold">
                            <x-lucide-layout-grid class="w-5 h-5" /> Tambah Area
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </form>
</div>
