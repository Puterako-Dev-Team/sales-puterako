@extends('layouts.app')

@section('content')
    <style>
        #formPanel {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .translate-x-full {
            transform: translateX(100%);
        }

        .translate-x-0 {
            transform: translateX(0);
        }

        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-item label {
            font-weight: 500;
            font-size: 0.875rem;
            color: #374151;
        }

        .filter-item input,
        .filter-item select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .filter-item input:focus,
        .filter-item select:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            align-items: end;
        }

        .loading-overlay {
            position: relative;
        }

        .loading-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            z-index: 10;
        }

        .loading-overlay.loading::after {
            display: block;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            z-index: 11;
        }

        .loading-overlay.loading .loading-spinner {
            display: block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>

    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">List Penawaran</h1>
            <button id="btnTambah"
                class="bg-green-500 text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
                <x-lucide-plus class="w-5 h-5 inline" />
                Tambah
            </button>
        </div>

                <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterForm">
                <div class="flex items-end gap-4 flex-wrap">
                    <div class="filter-item" style="flex: 0 0 180px;">
                        <label for="tanggal_dari">Tanggal Dari</label>
                        <input type="date" 
                               id="tanggal_dari" 
                               name="tanggal_dari" 
                               value="{{ request('tanggal_dari') }}"
                               class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 200px;">
                        <label for="no_penawaran">No Penawaran</label>
                        <input type="text" 
                               id="no_penawaran" 
                               name="no_penawaran" 
                               placeholder="Cari no penawaran..."
                               value="{{ request('no_penawaran') }}"
                               class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 250px;">
                        <label for="nama_perusahaan">Nama Perusahaan</label>
                        <input type="text" 
                               id="nama_perusahaan" 
                               name="nama_perusahaan" 
                               placeholder="Cari nama perusahaan..."
                               value="{{ request('nama_perusahaan') }}"
                               class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 0 0 150px;">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="filter-input">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                        </select>
                    </div>

                    <div class="filter-item" style="flex: 0 0 180px;">
                        <label for="pic_admin">PIC Admin</label>
                        <select id="pic_admin" name="pic_admin" class="filter-input">
                            <option value="">Semua PIC</option>
                            @foreach($picAdmins as $pic)
                                <option value="{{ $pic }}" {{ request('pic_admin') == $pic ? 'selected' : '' }}>
                                    {{ $pic }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions" style="flex: 0 0 auto;">
                        <button type="button" id="resetFilter"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition flex items-center gap-2 text-sm">
                            <x-lucide-refresh-cw class="w-4 h-4" />
                            Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
        

        <!-- Results Info -->
        <div id="resultsInfo" class="mb-4"></div>

        <!-- Table Container with Loading Overlay -->
        <div class="bg-white shadow rounded-lg loading-overlay" id="tableContainer">
            <div class="loading-spinner">
                <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div id="tableContent">
                @include('penawaran.table-content', ['penawarans' => $penawarans, 'totalRecords' => $totalRecords])
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContent" class="mt-6">
            {{ $penawarans->appends(request()->query())->links('penawaran.pagination') }}
        </div>
    </div>

    <!-- Slide-over Form (tetap sama) -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg p-8 transition-transform transform translate-x-full"
            id="formPanel">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Tambah Penawaran</h2>
                <button id="closeForm" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('penawaran.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Perihal</label>
                    <input type="text" name="perihal" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Lokasi</label>
                    <input type="text" name="lokasi" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">PIC Perusahaan</label>
                    <input type="text" name="pic_perusahaan" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">PIC Admin</label>
                    <input type="text" name="pic_admin" class="w-full border rounded px-3 py-2 text-sm"
                        value="{{ Auth::user()->name }}" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">No Penawaran</label>
                    <div class="flex items-center space-x-2">
                        <span
                            class="text-sm text-gray-600 bg-gray-100 px-3 py-2 rounded border">PIB/SS-SBY/JK/{{ Auth::id() }}-</span>
                        <input type="text" name="no_penawaran_suffix" class="flex-1 border rounded px-3 py-2 text-sm"
                            placeholder="VII/2025" required>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 w-full p-4 bg-white border-t">
                    <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition w-full text-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Slide-over logic
        const btnTambah = document.getElementById('btnTambah');
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const closeForm = document.getElementById('closeForm');

        btnTambah.addEventListener('click', function() {
            formSlide.classList.remove('hidden');
            setTimeout(() => {
                formPanel.classList.remove('translate-x-full');
                formPanel.classList.add('translate-x-0');
            }, 10);
        });

        function closeModal() {
            formPanel.classList.remove('translate-x-0');
            formPanel.classList.add('translate-x-full');
            setTimeout(() => {
                formSlide.classList.add('hidden');
            }, 400);
        }

        closeForm.addEventListener('click', closeModal);

        formSlide.addEventListener('click', function(e) {
            if (e.target === formSlide) {
                closeModal();
            }
        });

        // AJAX Filter Logic
        let filterTimeout;
        const tableContainer = document.getElementById('tableContainer');
        const tableContent = document.getElementById('tableContent');
        const resultsInfo = document.getElementById('resultsInfo');

        function showLoading() {
            tableContainer.classList.add('loading');
        }

        function hideLoading() {
            tableContainer.classList.remove('loading');
        }

                // Add sort functionality
        function attachSortListeners() {
            document.querySelectorAll('.sort-button').forEach(button => {
                button.addEventListener('click', function() {
                    const column = this.getAttribute('data-column');
                    const direction = this.getAttribute('data-direction');
                    
                    // Get current filter values
                    const formData = new FormData(document.getElementById('filterForm'));
                    const params = new URLSearchParams(formData);
                    params.set('sort', column);
                    params.set('direction', direction);
                    
                    showLoading();
                    
                    fetch(`{{ route('penawaran.filter') }}?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        tableContent.innerHTML = data.table;
                        resultsInfo.innerHTML = data.info;
                        document.getElementById('paginationContent').innerHTML = data.pagination;
                        hideLoading();

                        // Re-attach all listeners
                        attachPaginationListeners();
                        attachSortListeners();

                        // Update URL
                        const newUrl = new URL(window.location);
                        params.forEach((value, key) => {
                            if (value) {
                                newUrl.searchParams.set(key, value);
                            } else {
                                newUrl.searchParams.delete(key);
                            }
                        });
                        window.history.pushState({}, '', newUrl.toString());
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                    });
                });
            });
        }

        // Update existing functions to include sort listeners
        function performFilter() {
            showLoading();
            
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData);
            
            // Preserve current sort
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('sort')) {
                params.set('sort', urlParams.get('sort'));
            }
            if (urlParams.get('direction')) {
                params.set('direction', urlParams.get('direction'));
            }

            fetch(`{{ route('penawaran.filter') }}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                tableContent.innerHTML = data.table;
                resultsInfo.innerHTML = data.info;
                document.getElementById('paginationContent').innerHTML = data.pagination;
                hideLoading();

                // Attach pagination event listeners
                attachPaginationListeners();
                attachSortListeners();

                // Update URL tanpa refresh
                const newUrl = new URL(window.location);
                params.forEach((value, key) => {
                    if (value) {
                        newUrl.searchParams.set(key, value);
                    } else {
                        newUrl.searchParams.delete(key);
                    }
                });
                window.history.pushState({}, '', newUrl.toString());
            })
            .catch(error => {
                console.error('Error:', error);
                hideLoading();
            });
        }

        function attachPaginationListeners() {
            document.querySelectorAll('.pagination-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page');
                    
                    // Add current filter values to pagination request
                    const formData = new FormData(document.getElementById('filterForm'));
                    const params = new URLSearchParams(formData);
                    params.set('page', page);
                    
                    // Preserve sort
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('sort')) {
                        params.set('sort', urlParams.get('sort'));
                    }
                    if (urlParams.get('direction')) {
                        params.set('direction', urlParams.get('direction'));
                    }

                    showLoading();
                    
                    fetch(`{{ route('penawaran.filter') }}?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        tableContent.innerHTML = data.table;
                        resultsInfo.innerHTML = data.info;
                        document.getElementById('paginationContent').innerHTML = data.pagination;
                        hideLoading();

                        // Re-attach listeners for new pagination
                        attachPaginationListeners();
                        attachSortListeners();

                        // Update URL
                        const newUrl = new URL(window.location);
                        params.forEach((value, key) => {
                            if (value) {
                                newUrl.searchParams.set(key, value);
                            } else {
                                newUrl.searchParams.delete(key);
                            }
                        });
                        window.history.pushState({}, '', newUrl.toString());
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                    });
                });
            });
        }

        // Initial attachment
        document.addEventListener('DOMContentLoaded', function() {
            attachPaginationListeners();
            attachSortListeners();
        });

        // Auto filter untuk text inputs
        document.querySelectorAll('.filter-input').forEach(input => {
            if (input.type === 'text') {
                input.addEventListener('input', function() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(() => {
                        performFilter();
                    }, 800); // Delay 800ms untuk mengurangi request
                });
            } else {
                // Langsung filter untuk date dan select
                input.addEventListener('change', function() {
                    performFilter();
                });
            }
        });

        // Reset filter
        document.getElementById('resetFilter').addEventListener('click', function() {
            document.getElementById('filterForm').reset();
            performFilter();
        });
    </script>
@endpush