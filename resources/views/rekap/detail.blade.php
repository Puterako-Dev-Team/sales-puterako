{{-- filepath: resources/views/rekap/detail.blade.php --}}
@extends('layouts.app')

@section('content')
    <!-- Modal untuk Activity Log -->
    <div id="activityLogModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-[600px] max-w-full mx-4 max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Laporan Progress</h3>
                <button type="button" onclick="closeActivityLogModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div id="activityLogContent" class="overflow-y-auto flex-1">
                <div class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500 mt-2">Loading...</p>
                </div>
            </div>
            
            <div id="loadMoreContainer" class="hidden text-center mt-3">
                <button type="button" id="loadMoreBtn" onclick="loadMoreActivities()"
                    class="px-4 py-2 text-blue-600 hover:text-blue-700 font-medium">
                    Load More
                </button>
            </div>

            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeActivityLogModal()"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal untuk Tambah Grup -->
    <div id="tambahGrupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-[600px] max-w-full mx-4 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Tambah Grup Kolom</h3>
                <button type="button" onclick="closeTambahGrupModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Masukkan nama grup kolom (pisahkan dengan Enter untuk membuat grup lebih dari satu):
                </label>
                <textarea id="grupNameInput" rows="6" 
                    placeholder="Contoh:&#10;Kabel&#10;Pipa dan Aksesoris"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                <p class="text-xs text-gray-500 mt-2">Setiap baris akan menjadi satu grup kolom baru</p>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeTambahGrupModal()"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="saveTambahGrup()"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Tambah
                </button>
            </div>
        </div>
    </div>

    <!-- Modal untuk Tambah Kolom -->
    <div id="tambahKolomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-[500px] max-w-full mx-4 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Tambah Kolom</h3>
                <button type="button" onclick="closeTambahKolomModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mb-4" id="grupSelectionContainer">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Grup:
                </label>
                <select id="grupSelection" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Grup --</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Kolom:
                </label>
                <input type="text" id="kolomNameInput" 
                    placeholder="Masukkan nama kolom"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="kolomIsNumeric" class="w-4 h-4 text-blue-600 rounded">
                    <span class="text-sm font-medium text-gray-700">Kolom berisi angka</span>
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeTambahKolomModal()"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="saveTambahKolom()"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Tambah
                </button>
            </div>
        </div>
    </div>

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
            
            <!-- Activity Log Button -->
            <button type="button" id="logActivityBtn"
                class="bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600 font-semibold relative flex items-center gap-2"
                title="Laporan Progress">
                <x-lucide-clipboard-list class="w-5 h-5" />
                <span class="hidden sm:inline">Log</span>
                <span id="unreadBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
            </button>
            @endif
        </div>
        @endif

        {{-- CSRF Token for AJAX requests --}}
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        {{-- Tab Navigation --}}
        <div class="bg-white shadow rounded-t-lg mt-8">
            <div class="flex border-b border-gray-200 px-8">
                <button class="tab-btn px-6 py-4 font-semibold text-green-600 border-b-2 border-green-600 hover:text-green-600 focus:outline-none" data-tab="survey">
                    <x-lucide-table-2 class="inline w-5 h-5 mr-2" />
                    Data Survey
                </button>
                <button class="tab-btn px-6 py-4 font-semibold text-gray-600 hover:text-green-600 focus:outline-none" data-tab="dokumen">
                    <x-lucide-file-text class="inline w-5 h-5 mr-2" />
                    Dokumen Pendukung
                </button>
            </div>
        </div>

        {{-- Tab Content --}}
        <div class="bg-white p-6 rounded-b-lg shadow" id="tabContent">
            
            <!-- Data Survey Tab -->
            <div class="tab-panel" data-tab="survey">
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

            <!-- Dokumen Pendukung Tab -->
            <div class="tab-panel hidden" data-tab="dokumen">
                <h3 class="text-xl font-bold text-gray-800 mb-6">
                    <x-lucide-file-text class="inline w-6 h-6 mr-2 text-green-600" />
                    Dokumen Pendukung
                </h3>
                
                <div class="space-y-6">
                    <!-- Supporting Documents Section -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h3 class="font-bold text-gray-900 mb-4">File Pendukung</h3>
                        
                        <!-- Upload Form -->
                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <form id="supportDocForm" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                                    <input type="file" id="supportDocFile" name="file"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-green-500"
                                        required>
                                    <p class="text-xs text-gray-500 mt-1">Format: PDF, Word, Excel, atau Gambar (Maks 10MB)</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                                    <textarea id="supportDocNotes" name="notes"
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-green-500"
                                        rows="2" placeholder="Tambahkan catatan tentang file ini..."></textarea>
                                </div>

                                <button type="submit" id="uploadSupportDocBtn"
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
                                    Upload Dokumen Pendukung
                                </button>
                            </form>
                        </div>

                        <!-- Documents List -->
                        <div id="supportDocsList">
                            <div class="text-center text-gray-500 py-8">
                                <p>Belum ada dokumen pendukung.</p>
                            </div>
                        </div>
                    </div>
                </div>
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
            
            // AUTO-SAVE CONSTANTS
            const AUTOSAVE_INTERVAL = 60000; // 1 minute
            const LOCAL_STORAGE_KEY = `rekap_survey_autosave_{{ $rekap->id }}_v{{ isset($activeVersion) && $activeVersion !== null ? $activeVersion : 0 }}`;
            let autoSaveIntervalId = null;
            let survey = null;
            
            // Embed satuans data from server
            const SATUANS_DATA = @json($satuans ?? []);
            
            // Wait for SurveySpreadsheet class to be available
            const waitForModule = () => new Promise((resolve) => {
                if (window.SurveySpreadsheet) {
                    resolve();
                } else {
                    setTimeout(() => waitForModule().then(resolve), 100);
                }
            });
            
            await waitForModule();
            
            // Check for auto-saved data before initializing
            const savedAutoSave = loadAutoSaveData();
            
            // Initialize spreadsheet with version
            survey = new window.SurveySpreadsheet(containerId, {
                rekapId: {{ $rekap->id }},
                csrfToken: document.querySelector('input[name="_token"]').value,
                baseUrl: '{{ url('') }}',
                version: {{ isset($activeVersion) && $activeVersion !== null ? $activeVersion : 'null' }},
                satuans: SATUANS_DATA
            });
            
            await survey.init();
            
            // Hide loading
            if (loadingEl) loadingEl.style.display = 'none';
            
            // If there's auto-saved data, ask user to restore
            if (savedAutoSave && savedAutoSave.areas && savedAutoSave.areas.length > 0) {
                const savedDate = new Date(savedAutoSave.saved_at);
                const formattedDate = savedDate.toLocaleString('id-ID');
                
                if (confirm(`Ditemukan data auto-save dari ${formattedDate}.\n\nApakah Anda ingin memulihkan data tersebut?`)) {
                    restoreAutoSaveData(savedAutoSave);
                } else {
                    // User chose not to restore, clear the auto-save
                    clearAutoSaveData();
                }
            }
            
            // Start auto-save interval
            startAutoSave();
            
            // AUTO-SAVE FUNCTIONS
            function autoSaveToLocalStorage() {
                if (!survey || !survey.areas || survey.areas.length === 0) {
                    return false;
                }
                
                
                // Sync column headers from spreadsheet first
                survey.syncAllColumnHeaders();
                
                const areasData = survey.areas.map(area => {
                    const areaNameInput = document.getElementById(`area-${area.id}-name`);
                    const areaName = areaNameInput ? areaNameInput.value : '';
                    
                    const arrayData = survey.getAreaData(area);
                    const objData = survey.convertArrayToData(arrayData, area.headers);
                    
                    return {
                        id: area.serverId,
                        area_name: areaName,
                        headers: area.headers,
                        data: objData,
                        comments: survey.comments[area.id] || {},
                        satuans: survey.satuans[area.id] || {}
                    };
                });
                
                const autoSaveData = {
                    rekap_id: {{ $rekap->id }},
                    version: {{ isset($activeVersion) && $activeVersion !== null ? $activeVersion : 0 }},
                    areas: areasData,
                    saved_at: new Date().toISOString()
                };
                
                try {
                    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(autoSaveData));
                    const now = new Date();
                    
                    if (typeof window.notyf !== 'undefined') {
                        window.notyf.success({
                            message: 'Auto-save berhasil',
                            duration: 2000,
                            dismissible: true
                        });
                    }
                    return true;
                } catch (error) {
                    return false;
                }
            }
            
            function loadAutoSaveData() {
                try {
                    const savedData = localStorage.getItem(LOCAL_STORAGE_KEY);
                    if (savedData) {
                        return JSON.parse(savedData);
                    }
                } catch (error) {
                    console.error('❌ Failed to load auto-save data:', error);
                }
                return null;
            }
            
            function clearAutoSaveData() {
                try {
                    localStorage.removeItem(LOCAL_STORAGE_KEY);
                } catch (error) {
                    console.error('❌ Failed to clear auto-save data:', error);
                }
            }
            
            function restoreAutoSaveData(savedData) {
                if (!savedData || !savedData.areas || !survey) return false;
                
                
                // Clear existing areas - use while loop to avoid modifying array while iterating
                while (survey.areas.length > 0) {
                    survey.removeArea(survey.areas[0].id);
                }
                survey.areaCounter = 0;
                survey.container.innerHTML = '';
                
                // Recreate areas from saved data
                savedData.areas.forEach(areaData => {
                    survey.addArea(areaData.area_name, areaData.headers, areaData.data, areaData.id, areaData.comments, areaData.satuans);
                });
                
                // Re-apply formulas
                survey.areas.forEach(area => {
                    survey.applyFormulasToAllRows(area);
                    survey.styleFormulaColumns(area);
                });
                
                console.log('✅ Data restored from localStorage');
                
                if (typeof window.notyf !== 'undefined') {
                    window.notyf.success('Data berhasil dipulihkan dari auto-save');
                }
                
                return true;
            }
            
            function startAutoSave() {
                if (autoSaveIntervalId) {
                    clearInterval(autoSaveIntervalId);
                }
                autoSaveIntervalId = setInterval(autoSaveToLocalStorage, AUTOSAVE_INTERVAL);
            }
            
            function stopAutoSave() {
                if (autoSaveIntervalId) {
                    clearInterval(autoSaveIntervalId);
                    autoSaveIntervalId = null;
                }
            }
            
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
                            // Clear auto-save data after successful save
                            clearAutoSaveData();
                            
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
            
            // Stop auto-save when leaving page
            window.addEventListener('beforeunload', stopAutoSave);
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

    {{-- Activity Log JavaScript --}}
    <script>
        // Activity Log Modal Functions
        const rekapId = {{ $rekap->id }};
        let allActivities = [];
        let displayedCount = 0;
        const itemsPerPage = 7;

        function checkUnreadActivities() {
            const badge = document.getElementById('unreadBadge');
            if (!badge) return;
            
            fetch(`{{ route('rekap.countUnreadActivities') }}?id=${rekapId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > 0) {
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error checking unread activities:', error);
                });
        }

        function openActivityLogModal() {
            const modal = document.getElementById('activityLogModal');
            modal.classList.remove('hidden');
            loadActivityLog();
            
            // Mark activities as read on server
            fetch(`{{ route('rekap.markActivitiesRead') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: rekapId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide badge
                    const badge = document.getElementById('unreadBadge');
                    if (badge) badge.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error marking activities as read:', error);
            });
        }

        function closeActivityLogModal() {
            const modal = document.getElementById('activityLogModal');
            modal.classList.add('hidden');
        }

        function renderActivity(activity) {
            let description = '';
            if (activity.description === 'Exported Excel') {
                description = `${activity.causer_name} melakukan export Excel`;
                if (activity.properties && activity.properties.version !== undefined) {
                    description += ` (Rev ${activity.properties.version})`;
                }
            } else if (activity.description === 'Created revision') {
                description = `${activity.causer_name} membuat revisi`;
                if (activity.properties && activity.properties.new_version !== undefined) {
                    description += ` (Rev ${activity.properties.new_version})`;
                }
            } else if (activity.description === 'Edited survey') {
                description = `${activity.causer_name} mengedit data survey`;
                if (activity.properties && activity.properties.version !== undefined) {
                    description += ` (Rev ${activity.properties.version})`;
                }
            } else if (activity.description === 'Saved survey') {
                description = `${activity.causer_name} menyimpan data survey`;
                if (activity.properties && activity.properties.version !== undefined) {
                    description += ` (Rev ${activity.properties.version})`;
                }
            } else {
                description = `${activity.causer_name} - ${activity.description}`;
            }
            
            let html = `<div class="border-b pb-2">`;
            html += `<div class="text-sm text-gray-500 mb-1">${activity.created_at_formatted}</div>`;
            html += `<div class="text-gray-700">• ${description}</div>`;
            html += `</div>`;
            return html;
        }

        function loadActivityLog() {
            const contentDiv = document.getElementById('activityLogContent');
            const loadMoreContainer = document.getElementById('loadMoreContainer');

            fetch(`{{ route('rekap.showLog') }}?id=${rekapId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.activities.length > 0) {
                        allActivities = data.activities;
                        displayedCount = 0;
                        
                        let html = '<div id="activityList" class="space-y-3">';
                        
                        // Show first 7 items
                        const initialItems = allActivities.slice(0, itemsPerPage);
                        initialItems.forEach(activity => {
                            html += renderActivity(activity);
                        });
                        displayedCount = initialItems.length;
                        
                        html += '</div>';
                        contentDiv.innerHTML = html;
                        
                        // Show/hide load more button
                        if (allActivities.length > displayedCount) {
                            loadMoreContainer.classList.remove('hidden');
                        } else {
                            loadMoreContainer.classList.add('hidden');
                        }
                    } else {
                        contentDiv.innerHTML = '<p class="text-center text-gray-500 py-4">Belum ada aktivitas yang tercatat</p>';
                        loadMoreContainer.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading activity log:', error);
                    contentDiv.innerHTML = '<p class="text-center text-red-500 py-4">Gagal memuat activity log</p>';
                    loadMoreContainer.classList.add('hidden');
                });
        }

        function loadMoreActivities() {
            const activityList = document.getElementById('activityList');
            const loadMoreContainer = document.getElementById('loadMoreContainer');
            
            // Get next 7 items
            const nextItems = allActivities.slice(displayedCount, displayedCount + itemsPerPage);
            
            nextItems.forEach(activity => {
                const activityHtml = renderActivity(activity);
                activityList.insertAdjacentHTML('beforeend', activityHtml);
            });
            
            displayedCount += nextItems.length;
            
            // Hide load more button if all items are displayed
            if (displayedCount >= allActivities.length) {
                loadMoreContainer.classList.add('hidden');
            }
        }

        // Initialize activity log button
        document.addEventListener('DOMContentLoaded', function() {
            const logBtn = document.getElementById('logActivityBtn');
            if (logBtn) {
                logBtn.addEventListener('click', openActivityLogModal);
            }
            
            // Check unread activities on page load
            checkUnreadActivities();
            
            // Close activity log modal when clicking outside
            const modal = document.getElementById('activityLogModal');
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeActivityLogModal();
                    }
                });
            }

            // Initialize tab switching
            initializeTabSwitching();

            // Load supporting documents
            loadSupportingDocuments();

            // Attach form submit handler
            const supportDocForm = document.getElementById('supportDocForm');
            if (supportDocForm) {
                supportDocForm.addEventListener('submit', handleDocumentUpload);
            }
        });
        function initializeTabSwitching() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanels = document.querySelectorAll('.tab-panel');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Remove active state from all buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('text-green-600', 'border-b-2', 'border-green-600');
                        b.classList.add('text-gray-600');
                    });
                    
                    // Hide all tab panels
                    tabPanels.forEach(panel => {
                        panel.classList.add('hidden');
                    });
                    
                    // Add active state to clicked button
                    this.classList.add('text-green-600', 'border-b-2', 'border-green-600');
                    this.classList.remove('text-gray-600');
                    
                    // Show corresponding tab panel
                    document.querySelector(`.tab-panel[data-tab="${tabName}"]`).classList.remove('hidden');
                });
            });
        }

        // Load supporting documents
        async function loadSupportingDocuments() {
            try {
                const response = await fetch(`{{ route('rekap.supporting-documents', $rekap->id) }}`);
                if (!response.ok) {
                    // If route doesn't exist yet, show empty state
                    document.getElementById('supportDocsList').innerHTML = `
                        <div class="text-center text-gray-500 py-8">
                            <p>Belum ada dokumen pendukung.</p>
                        </div>
                    `;
                    return;
                }

                const documents = await response.json();
                renderSupportingDocuments(documents);
            } catch (error) {
                console.error('Error loading documents:', error);
                document.getElementById('supportDocsList').innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <p>Belum ada dokumen pendukung.</p>
                    </div>
                `;
            }
        }

        function renderSupportingDocuments(documents) {
            const container = document.getElementById('supportDocsList');
            
            if (!documents || documents.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <p>Belum ada dokumen pendukung.</p>
                    </div>
                `;
                return;
            }
            
            const html = documents.map(doc => `
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded hover:bg-gray-50">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">${doc.filename}</p>
                            <p class="text-xs text-gray-500">${doc.notes ? doc.notes : 'Tanpa catatan'}</p>
                            <p class="text-xs text-gray-400 mt-1">${new Date(doc.created_at).toLocaleString('id-ID')}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4 flex-shrink-0">
                        <a href="/rekap/{{ $rekap->id }}/download-document/${doc.id}"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Download
                        </a>
                        <button onclick="deleteSupportDocument(${doc.id})"
                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Hapus
                        </button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = html;
        }

        async function handleDocumentUpload(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('supportDocFile');
            const notesInput = document.getElementById('supportDocNotes');
            const uploadBtn = document.getElementById('uploadSupportDocBtn');
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('notes', notesInput.value);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            try {
                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Uploading...';
                
                const response = await fetch(`{{ route('rekap.upload-document', $rekap->id) }}`, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Upload failed');
                }
                
                // Reset form and reload documents
                fileInput.value = '';
                notesInput.value = '';
                await loadSupportingDocuments();
                
            } catch (error) {
                console.error('Error uploading document:', error);
                alert('Gagal upload dokumen. Coba lagi.');
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload Dokumen Pendukung';
            }
        }

        async function deleteSupportDocument(documentId) {
            if (!confirm('Yakin hapus dokumen ini?')) return;
            
            try {
                const response = await fetch(`/rekap/{{ $rekap->id }}/document/${documentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Delete failed');
                }
                
                await loadSupportingDocuments();
            } catch (error) {
                console.error('Error deleting document:', error);
                alert('Gagal hapus dokumen. Coba lagi.');
            }
        }
    </script>

    <!-- Modal Control Scripts -->
    <script>
        // Handle modal backdrop clicks
        document.addEventListener('DOMContentLoaded', function() {
            // Tambah Grup Modal
            const tambahGrupModal = document.getElementById('tambahGrupModal');
            if (tambahGrupModal) {
                tambahGrupModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeTambahGrupModal();
                    }
                });
            }

            // Tambah Kolom Modal
            const tambahKolomModal = document.getElementById('tambahKolomModal');
            if (tambahKolomModal) {
                tambahKolomModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeTambahKolomModal();
                    }
                });
            }
        });

        // Keyboard shortcuts for modals (outside DOMContentLoaded)
        document.addEventListener('keydown', function(e) {
            const tambahGrupModal = document.getElementById('tambahGrupModal');
            const tambahKolomModal = document.getElementById('tambahKolomModal');

            if (e.key === 'Escape') {
                if (tambahGrupModal && !tambahGrupModal.classList.contains('hidden')) {
                    closeTambahGrupModal();
                } else if (tambahKolomModal && !tambahKolomModal.classList.contains('hidden')) {
                    closeTambahKolomModal();
                }
            }

            if (e.key === 'Enter' && e.ctrlKey) {
                if (tambahGrupModal && !tambahGrupModal.classList.contains('hidden')) {
                    saveTambahGrup();
                } else if (tambahKolomModal && !tambahKolomModal.classList.contains('hidden')) {
                    saveTambahKolom();
                }
            }
        });
    </script>@endsection