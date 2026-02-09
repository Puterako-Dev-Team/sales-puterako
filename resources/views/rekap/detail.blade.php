{{-- filepath: resources/views/rekap/detail.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-8">

        <div class="flex items-center p-8 text-gray-600 mb-2">
            @if(Auth::user()->role === 'manager')
                <a href="{{ route('rekap.approve-list') }}" class="flex items-center hover:text-green-600">
                    <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                    List Rekap
                </a>
            @else
                <a href="{{ route('rekap.list') }}" class="flex items-center hover:text-green-600">
                    <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                    List Rekap
                </a>
            @endif
            <span class="mx-2">/</span>
            <span class="font-semibold">Detail Rekap</span>
            @if($rekap && $rekap->exists && $rekap->items->count() > 0)
                <div class="ml-auto">
                    <a href="{{ route('rekap.export', $rekap->id) }}" 
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition flex items-center gap-2">
                        <x-lucide-download class="w-5 h-5" />
                        Export to Excel
                    </a>
                </div>
            @endif
        </div>

        {{-- Version Selector --}}
        @if(isset($rekap) && $rekap->exists)
        @php
            $activeVersion = request('version') ?? ($currentVersion ? $currentVersion->version : null);
        @endphp
        <div class="flex items-center justify-end gap-4 px-8 mb-4">
            <form method="GET" action="{{ route('rekap.show', $rekap->id) }}" class="flex items-center gap-2">
                <label class="font-semibold">Lihat Versi:</label>
                <select name="version" onchange="this.form.submit()" class="border rounded px-3 py-2">
                    @if(!isset($versions) || (is_array($versions) ? empty($versions) : $versions->isEmpty()))
                        <option value="">Belum ada versi</option>
                    @else
                        @foreach ($versions as $v)
                            <option value="{{ $v->version }}" {{ $v->version == $activeVersion ? 'selected' : '' }}>
                                @if($v->version == 0)
                                    Versi Awal (0)
                                @else
                                    Revisi {{ $v->version }}
                                @endif
                            </option>
                        @endforeach
                    @endif
                </select>
            </form>

            @if(Auth::user()->role !== 'manager')
            <!-- Form untuk button buat revisi -->
            <form method="POST" action="{{ route('rekap.createRevision', ['id' => $rekap->id]) }}" id="createRevisionForm">
                @csrf
                <button type="submit" class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:shadow-lg font-semibold flex items-center gap-2">
                    <x-lucide-git-branch class="w-4 h-4" />
                    + Tambah Revisi
                </button>
            </form>
            @endif
        </div>
        @endif

        {{-- CSRF Token for AJAX requests --}}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        {{-- Survey Spreadsheet Section (Excel-like interface using jspreadsheet) --}}
        <div class="bg-white p-6 rounded shadow mt-8" id="survey-spreadsheet-section">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <x-lucide-table-2 class="inline w-6 h-6 mr-2 text-green-600" />
                    Data Survey
                </h3>
                <div class="flex gap-2">
                    <button type="button" id="btnTambahArea" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition flex items-center gap-2 text-sm">
                        <x-lucide-plus-circle class="w-4 h-4" /> Tambah Area
                    </button>
                    <a href="{{ url('rekap/' . $rekap->id . '/export-survey') }}{{ $currentVersion !== null ? '?version=' . $currentVersion->version : '' }}" class="bg-teal-500 text-white px-4 py-2 rounded hover:bg-teal-600 transition flex items-center gap-2 text-sm" id="exportExcelLink">
                        <x-lucide-download class="w-4 h-4" /> Export Excel
                    </a>
                    <button type="button" id="btnSimpanSurvey" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center gap-2">
                        <x-lucide-save class="w-5 h-5" /> Simpan Semua
                    </button>
                </div>
            </div>
            
            <div class="text-sm text-gray-500 mb-4">
                <p><strong>Tips:</strong> Setiap area memiliki spreadsheet sendiri. Tambah area baru dengan tombol "Tambah Area". Edit langsung seperti Excel. Klik kanan untuk menu tambah/hapus baris.</p>
            </div>
            
            {{-- jspreadsheet container for multiple areas --}}
            <div id="survey-spreadsheet" class="space-y-4"></div>
            
            {{-- Loading state --}}
            <div id="survey-loading" class="p-4 text-gray-500 text-center">
                Memuat spreadsheet...
            </div>
        </div>
    </div>

    {{-- Include jspreadsheet module from Vite --}}
    @vite(['resources/js/survey-spreadsheet.js'])

    <script>
        // Initialize jspreadsheet-based survey on DOM ready
        document.addEventListener('DOMContentLoaded', async function() {
            const containerId = 'survey-spreadsheet';
            const loadingEl = document.getElementById('survey-loading');
            
            // Wait for SurveySpreadsheet class to be available
            const waitForModule = () => new Promise((resolve) => {
                if (window.SurveySpreadsheet) {
                    resolve();
                } else {
                    setTimeout(() => waitForModule().then(resolve), 100);
                }
            });
            
            await waitForModule();
            
            // Initialize spreadsheet with version
            const survey = new window.SurveySpreadsheet(containerId, {
                rekapId: {{ $rekap->id }},
                csrfToken: document.querySelector('input[name="_token"]').value,
                baseUrl: '{{ url('') }}',
                version: {{ isset($activeVersion) && $activeVersion !== null ? $activeVersion : 'null' }}
            });
            
            await survey.init();
            
            // Hide loading
            if (loadingEl) loadingEl.style.display = 'none';
            
            // Button handlers
            const btnTambahArea = document.getElementById('btnTambahArea');
            const btnSimpanSurvey = document.getElementById('btnSimpanSurvey');
            
            if (btnTambahArea) {
                btnTambahArea.addEventListener('click', () => {
                    survey.addArea();
                });
            }
            
            if (btnSimpanSurvey) {
                btnSimpanSurvey.addEventListener('click', async () => {
                    btnSimpanSurvey.disabled = true;
                    btnSimpanSurvey.textContent = 'Menyimpan...';
                    
                    try {
                        const success = await survey.save();
                        if (success) {
                            if (typeof window.notyf !== 'undefined') {
                                window.notyf.success('Semua area survey berhasil disimpan!');
                            } else {
                                alert('Semua area survey berhasil disimpan!');
                            }
                        } else {
                            throw new Error('Gagal menyimpan');
                        }
                    } catch (err) {
                        console.error('Error saving:', err);
                        if (typeof window.notyf !== 'undefined') {
                            window.notyf.error('Gagal menyimpan survey: ' + err.message);
                        } else {
                            alert('Gagal menyimpan survey: ' + err.message);
                        }
                    } finally {
                        btnSimpanSurvey.disabled = false;
                        btnSimpanSurvey.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Simpan Semua';
                    }
                });
            }
        });
    </script>

    {{-- Tom Select CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        /* Remove Tom Select wrapper border to match other inputs */
        .ts-wrapper.single .ts-control {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            box-shadow: none;
        }
        
        .ts-wrapper.single .ts-control:focus-within {
            outline: none;
            ring: 2px;
            ring-color: #bbf7d0;
        }
        
        /* Remove the default Tom Select styling */
        .ts-wrapper {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
    </style>

    <script>
        let tipesCache = {};

        // Initialize Tom Select for nama item
        function initializeTomSelect(index) {
            const input = document.querySelector(`.nama-item-input[data-index="${index}"]`);
            if (!input) return;

            // Destroy existing if any
            if (input.tomselect) {
                input.tomselect.destroy();
            }

            new TomSelect(input, {
                create: function(input) {
                    return {
                        value: 'new:' + input,
                        text: input
                    }
                },
                createOnBlur: true,
                maxOptions: 50,
                maxItems: 1,
                valueField: 'value',
                labelField: 'text',
                searchField: 'text',
                load: function(query, callback) {
                    if (query.length < 1) return callback();

                    fetch(`{{ route('rekap.search-tipes') }}?q=${encodeURIComponent(query)}&t=${Date.now()}`)
                        .then(response => response.json())
                        .then(data => {
                            // Remove duplicates client-side as well
                            const uniqueData = [];
                            const seen = new Set();
                            data.forEach(item => {
                                if (!seen.has(item.text.toLowerCase())) {
                                    uniqueData.push(item);
                                    seen.add(item.text.toLowerCase());
                                }
                            });
                            
                            tipesCache = uniqueData.reduce((acc, item) => {
                                acc[item.text] = item.value;
                                return acc;
                            }, tipesCache);
                            
                            // Add options to Tom Select control so they display in dropdown
                            uniqueData.forEach(item => {
                                if (!input.tomselect.options[item.value]) {
                                    input.tomselect.addOption(item);
                                }
                            });
                            
                            callback(uniqueData);
                        })
                        .catch(() => callback());
                },
                onItemAdd: function(value, item) {
                    const hiddenInput = document.querySelector(`input[name="items[${index}][tipes_id]"]`);
                    
                    if (value.startsWith('new:')) {
                        // Create new tipe
                        const newTipeName = value.substring(4);
                        input.value = newTipeName;
                        
                        fetch('{{ route("rekap.create-tipe") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            },
                            body: JSON.stringify({ nama: newTipeName })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.id) {
                                hiddenInput.value = data.id;
                                tipesCache[newTipeName] = data.id;
                                input.value = newTipeName;
                            }
                        });
                    } else {
                        // For existing tipes, always keep the display as the NAME not ID
                        let tipeName = item?.text;
                        
                        if (!tipeName && input.tomselect) {
                            const option = input.tomselect.options[value];
                            tipeName = option?.text || option?.nama || value;
                        }
                        
                        if (!tipeName) {
                            for (let [key, val] of Object.entries(tipesCache)) {
                                if (val == value) {
                                    tipeName = key;
                                    break;
                                }
                            }
                        }
                        
                        // IMPORTANT: Set display to name, ID to hidden field
                        input.value = tipeName || value;
                        hiddenInput.value = value;
                    }
                },
                render: {
                    option_create: function(data, escape) {
                        return '<div class="create">Tambah <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                    },
                    no_results: function(data, escape) {
                        return '<div class="no-results">Tidak ada hasil untuk "' + escape(data.input) + '"</div>';
                    }
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTomSelect(0);
        });

        // Tambah item row
        function attachAddItemListener() {
            const buttons = document.querySelectorAll('.btn-add-item');
            buttons.forEach(button => {
                // Remove existing listeners by cloning
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Add new listener
                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    let idx = document.querySelectorAll('#item-list .item-row').length;
                    let html = `
                        <div class="item-row mb-6 p-4 bg-white rounded-lg border border-gray-300">
                            <div class="flex gap-4 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-2 text-gray-700">Kategori <span class="text-red-500">*</span></label>
                                    <select name="items[${idx}][rekap_kategori_id]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($kategoris as $kategori)
                                            <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-2 text-gray-700">Nama Item <span class="text-red-500">*</span></label>
                                    <input type="hidden" name="items[${idx}][tipes_id]" class="tipes-id-input" value="">
                                    <input type="text" name="items[${idx}][nama_item]"
                                        class="nama-item-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                        placeholder="Cari atau ketik nama item..." required
                                        data-index="${idx}">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-2 text-gray-700">Jumlah <span class="text-red-500">*</span></label>
                                    <input type="number" name="items[${idx}][jumlah]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                        placeholder="0.00" min="0.01" step="any" required>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-2 text-gray-700">Satuan <span class="text-red-500">*</span></label>
                                    <select name="items[${idx}][satuan_id]"
                                        class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                        <option value="">Pilih Satuan</option>
                                        @foreach ($satuans as $satuan)
                                            <option value="{{ $satuan->id }}">{{ $satuan->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition h-10 flex items-center justify-center">
                                    <x-lucide-minus class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    `;
                    document.getElementById('item-list').insertAdjacentHTML('beforeend', html);
                    initializeTomSelect(idx);
                });
            });
        }
        
        // Initial attachment
        attachAddItemListener();

        // Tambah Area Button Handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-add-area')) {
                e.preventDefault();
                
                // Get current max index from all items
                let maxIdx = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const inputs = row.querySelectorAll('[name^="items["]');
                    inputs.forEach(input => {
                        const match = input.name.match(/items\[(\d+)\]/);
                        if (match) {
                            maxIdx = Math.max(maxIdx, parseInt(match[1]));
                        }
                    });
                });
                
                const newIdx = maxIdx + 1;
                
                // Add new area section
                const areaSection = `
                    <div class="area-section mb-6 p-4 bg-white rounded-lg border-2 border-blue-200">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Nama Area <span class="text-red-500">*</span></label>
                                <input type="text" class="area-name-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-blue-200"
                                    placeholder="Masukkan nama area (misal: Kantor, Halaman, dll)" required>
                            </div>
                            <button type="button" class="btn-remove-area bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition flex items-center gap-2 ml-4 mt-6">
                                <x-lucide-trash-2 class="w-4 h-4" /> Hapus Area
                            </button>
                        </div>
                        <div class="area-items-container">
                            <div class="item-row mb-4 p-3 bg-gray-50 rounded-lg border border-gray-300">
                                <div class="flex gap-4 items-end">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Kategori <span class="text-red-500">*</span></label>
                                        <select name="items[${newIdx}][rekap_kategori_id]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                            <option value="">Pilih Kategori</option>
                                            @foreach ($kategoris as $kategori)
                                                <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Nama Item <span class="text-red-500">*</span></label>
                                        <input type="hidden" name="items[${newIdx}][tipes_id]" class="tipes-id-input" value="">
                                        <input type="hidden" name="items[${newIdx}][nama_area]" class="item-area-name" value="">
                                        <input type="text" name="items[${newIdx}][nama_item]"
                                            class="nama-item-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            placeholder="Cari atau ketik nama item..." required data-index="${newIdx}">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Jumlah <span class="text-red-500">*</span></label>
                                        <input type="number" name="items[${newIdx}][jumlah]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            placeholder="0.00" min="0.01" step="any" required>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Satuan <span class="text-red-500">*</span></label>
                                        <select name="items[${newIdx}][satuan_id]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                            <option value="">Pilih Satuan</option>
                                            @foreach ($satuans as $satuan)
                                                <option value="{{ $satuan->id }}">{{ $satuan->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition h-10 flex items-center justify-center">
                                        <x-lucide-minus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="button" class="btn-add-item-in-area bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition flex items-center gap-2">
                                <x-lucide-plus class="w-4 h-4" /> Tambah Item
                            </button>
                        </div>
                    </div>
                `;
                
                const container = document.getElementById('area-container') || document.getElementById('item-list');
                container.insertAdjacentHTML('beforeend', areaSection);
                initializeTomSelect(newIdx);
            }
        });

        // Remove Area Handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-area')) {
                const areaSection = e.target.closest('.area-section');
                const allAreas = document.querySelectorAll('.area-section');
                
                if (allAreas.length <= 1) {
                    alert('Minimal harus ada satu area!');
                    return;
                }
                
                if (confirm('Apakah Anda yakin ingin menghapus area ini beserta semua itemnya?')) {
                    areaSection.remove();
                }
            }
        });

        // Add Item in Specific Area Handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-add-item-in-area')) {
                e.preventDefault();
                const areaSection = e.target.closest('.area-section');
                const areaItemsContainer = areaSection.querySelector('.area-items-container');
                const areaNameInput = areaSection.querySelector('.area-name-input');
                
                // Get current max index
                let maxIdx = 0;
                document.querySelectorAll('.item-row').forEach(row => {
                    const inputs = row.querySelectorAll('[name^="items["]');
                    inputs.forEach(input => {
                        const match = input.name.match(/items\[(\d+)\]/);
                        if (match) {
                            maxIdx = Math.max(maxIdx, parseInt(match[1]));
                        }
                    });
                });
                
                const newIdx = maxIdx + 1;
                
                const itemHtml = `
                    <div class="item-row mb-4 p-3 bg-gray-50 rounded-lg border border-gray-300">
                        <div class="flex gap-4 items-end">
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Kategori <span class="text-red-500">*</span></label>
                                <select name="items[${newIdx}][rekap_kategori_id]"
                                    class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach ($kategoris as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Nama Item <span class="text-red-500">*</span></label>
                                <input type="hidden" name="items[${newIdx}][tipes_id]" class="tipes-id-input" value="">
                                <input type="hidden" name="items[${newIdx}][nama_area]" class="item-area-name" value="">
                                <input type="text" name="items[${newIdx}][nama_item]"
                                    class="nama-item-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                    placeholder="Cari atau ketik nama item..." required data-index="${newIdx}">
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Jumlah <span class="text-red-500">*</span></label>
                                <input type="number" name="items[${newIdx}][jumlah]"
                                    class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                    placeholder="0.00" min="0.01" step="any" required>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-2 text-gray-700">Satuan <span class="text-red-500">*</span></label>
                                <select name="items[${newIdx}][satuan_id]"
                                    class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                    <option value="">Pilih Satuan</option>
                                    @foreach ($satuans as $satuan)
                                        <option value="{{ $satuan->id }}">{{ $satuan->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition h-10 flex items-center justify-center">
                                <x-lucide-minus class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                `;
                
                areaItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
                initializeTomSelect(newIdx);
            }
        });

        // Hapus item row
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-item')) {
                const row = e.target.closest('.item-row');
                const areaSection = row.closest('.area-section');
                const itemsInArea = areaSection.querySelectorAll('.item-row');
                
                if (itemsInArea.length <= 1) {
                    alert('Minimal harus ada satu item di setiap area!');
                    return;
                }
                
                row.remove();
            }
        });

        // Edit mode
        const btnEdit = document.getElementById('btnEditItem');
        const btnCancel = document.getElementById('btnCancelEdit');
        const btnSimpan = document.getElementById('btnSimpan');
        const btnAddItem = document.querySelector('.btn-add-item');
        const itemList = document.getElementById('item-list');

        if (btnEdit) {
            btnEdit.addEventListener('click', function() {
                // Clear cache before edit
                tipesCache = {};
                
                const items = @json($rekap->items);
                const kategoris = @json($kategoris);
                const satuans = @json($satuans);

                // Group items by area
                const groupedItems = {};
                items.forEach(item => {
                    const area = item.nama_area || 'Default Area';
                    if (!groupedItems[area]) {
                        groupedItems[area] = [];
                    }
                    groupedItems[area].push(item);
                });

                // Sort areas by the minimum ID in each group (to preserve insertion order)
                const sortedAreas = Object.keys(groupedItems).sort((a, b) => {
                    const minIdA = Math.min(...groupedItems[a].map(item => item.id));
                    const minIdB = Math.min(...groupedItems[b].map(item => item.id));
                    return minIdA - minIdB;
                });

                // Generate HTML with area-section structure
                let html = '';
                let globalIdx = 0;
                
                sortedAreas.forEach(areaName => {
                    const areaItems = groupedItems[areaName];
                    
                    html += `
                        <div class="area-section mb-6 p-4 bg-white rounded-lg border-2 border-blue-200">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium mb-2 text-gray-700">Nama Area <span class="text-red-500">*</span></label>
                                    <input type="text" class="area-name-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-blue-200"
                                        value="${areaName}" required>
                                </div>
                                <button type="button" class="btn-remove-area bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition flex items-center gap-2 ml-4 mt-6">
                                    <x-lucide-trash-2 class="w-4 h-4" /> Hapus Area
                                </button>
                            </div>
                            <div class="area-items-container">
                    `;
                    
                    areaItems.forEach(item => {
                        html += `
                            <div class="item-row mb-4 p-3 bg-gray-50 rounded-lg border border-gray-300">
                                <div class="flex gap-4 items-end">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Kategori <span class="text-red-500">*</span></label>
                                        <select name="items[${globalIdx}][rekap_kategori_id]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                            <option value="">Pilih Kategori</option>
                                            ${kategoris.map(k => `
                                                <option value="${k.id}" ${k.id == item.rekap_kategori_id ? 'selected' : ''}>${k.nama}</option>
                                            `).join('')}
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Nama Item <span class="text-red-500">*</span></label>
                                        <input type="hidden" name="items[${globalIdx}][tipes_id]" class="tipes-id-input" value="${item.tipes_id}">
                                        <input type="hidden" name="items[${globalIdx}][nama_area]" class="item-area-name" value="${areaName}">
                                        <input type="text" name="items[${globalIdx}][nama_item]"
                                            class="nama-item-input border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            value="${item.tipe && item.tipe.nama ? item.tipe.nama : ''}" required
                                            data-index="${globalIdx}">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Jumlah <span class="text-red-500">*</span></label>
                                        <input type="number" name="items[${globalIdx}][jumlah]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200"
                                            value="${item.jumlah}" min="0.01" step="any" required>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-2 text-gray-700">Satuan <span class="text-red-500">*</span></label>
                                        <select name="items[${globalIdx}][satuan_id]"
                                            class="border rounded-lg px-3 py-2 w-full focus:ring focus:ring-green-200" required>
                                            <option value="">Pilih Satuan</option>
                                            ${satuans.map(s => `
                                                <option value="${s.id}" ${s.id == item.satuan_id ? 'selected' : ''}>${s.nama}</option>
                                            `).join('')}
                                        </select>
                                    </div>
                                    <button type="button" class="btn-remove-item bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition h-10 flex items-center justify-center">
                                        <x-lucide-minus class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        `;
                        globalIdx++;
                    });
                    
                    html += `
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="button" class="btn-add-item-in-area bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition flex items-center gap-2">
                                    <x-lucide-plus class="w-4 h-4" /> Tambah Item
                                </button>
                            </div>
                        </div>
                    `;
                });

                itemList.innerHTML = html;

                // Toggle buttons
                btnEdit.classList.add('hidden');
                btnCancel.classList.remove('hidden');
                btnSimpan.classList.remove('hidden');
                const btnAddArea = document.querySelector('.btn-add-area');
                if (btnAddArea) btnAddArea.classList.remove('hidden');

                // Re-initialize Tom Select for all items
                for (let i = 0; i < globalIdx; i++) {
                    initializeTomSelect(i);
                }
            });
        }

        if (btnCancel) {
            btnCancel.addEventListener('click', function() {
                location.reload();
            });
        }

        // Form validation
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            let isValid = true;
            let errorMessages = [];
            
            // Sync area names from area-name-input to item-area-name hidden fields
            document.querySelectorAll('.area-section').forEach(areaSection => {
                const areaNameInput = areaSection.querySelector('.area-name-input');
                const areaName = areaNameInput?.value.trim();
                
                if (!areaName) {
                    isValid = false;
                    errorMessages.push('Semua nama area harus diisi');
                    return;
                }
                
                // Update all items in this area
                areaSection.querySelectorAll('.item-area-name').forEach(hiddenInput => {
                    hiddenInput.value = areaName;
                });
            });
            
            const allItems = document.querySelectorAll('.item-row');
            
            if (allItems.length === 0) {
                isValid = false;
                errorMessages.push('Minimal harus ada satu item');
            }

            // Validate each item
            allItems.forEach((row) => {
                // Find the actual index from the name attribute
                const firstInput = row.querySelector('[name^="items["]');
                const match = firstInput?.name.match(/items\[(\d+)\]/);
                const idx = match ? parseInt(match[1]) : 0;
                
                const kategoriSelect = row.querySelector(`select[name="items[${idx}][rekap_kategori_id]"]`);
                const namaItemInput = row.querySelector(`input[name="items[${idx}][nama_item]"]`);
                const jumlahInput = row.querySelector(`input[name="items[${idx}][jumlah]"]`);
                const satuanSelect = row.querySelector(`select[name="items[${idx}][satuan_id]"]`);
                const tipesIdInput = row.querySelector(`input[name="items[${idx}][tipes_id]"]`);
                const namaAreaInput = row.querySelector(`input[name="items[${idx}][nama_area]"]`);

                // Clean nama_item value (remove "new:" prefix if exists)
                if (namaItemInput?.value) {
                    let cleanName = namaItemInput.value;
                    if (cleanName.startsWith('new:')) {
                        cleanName = cleanName.substring(4);
                    }
                    namaItemInput.value = cleanName;
                }

                // Check basic fields
                if (!kategoriSelect?.value) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Kategori harus dipilih`);
                }
                if (!namaItemInput?.value || !namaItemInput.value.trim()) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Nama Item harus diisi`);
                }
                if (!jumlahInput?.value || jumlahInput.value <= 0) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Jumlah harus lebih dari 0`);
                }
                if (!satuanSelect?.value) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Satuan harus dipilih`);
                }
                if (!namaAreaInput?.value || !namaAreaInput.value.trim()) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Nama Area tidak valid`);
                }

                // Try to find tipes_id from cache if not set
                if (!tipesIdInput?.value && namaItemInput?.value) {
                    if (tipesCache[namaItemInput.value]) {
                        tipesIdInput.value = tipesCache[namaItemInput.value];
                    } else if (namaItemInput.value.trim()) {
                        // If still not found, create a new one synchronously
                        fetch('{{ route("rekap.create-tipe") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            },
                            body: JSON.stringify({ nama: namaItemInput.value })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.id) {
                                tipesIdInput.value = data.id;
                                tipesCache[namaItemInput.value] = data.id;
                                // Resubmit form after tipes is created
                                setTimeout(() => document.getElementById('itemForm').submit(), 100);
                            }
                        })
                        .catch(err => {
                            console.error('Error creating tipe:', err);
                            isValid = false;
                        });
                        e.preventDefault();
                        return;
                    }
                }

                // Check if tipes_id is empty after all attempts
                if (!tipesIdInput?.value) {
                    isValid = false;
                    errorMessages.push(`Item ${idx + 1}: Item tidak valid. Silakan pilih item dari daftar atau buat yang baru`);
                }
            });

            if (!isValid) {
                e.preventDefault();
                
                // Show error messages
                if (errorMessages.length > 0) {
                    if (typeof toastr !== 'undefined') {
                        errorMessages.forEach(msg => {
                            toastr.error(msg);
                        });
                    } else {
                        alert('Validasi gagal:\n\n' + errorMessages.join('\n'));
                    }
                }
            }
        });
    </script>
@endsection
