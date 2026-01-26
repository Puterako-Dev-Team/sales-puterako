{{-- filepath: resources/views/rekap-kategori/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <style>
        .filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
        }

        .filter-item label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .filter-item input,
        .filter-item select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .filter-item input:focus,
        .filter-item select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay.loading::after {
            display: flex;
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #formPanel {
            max-height: 100vh;
            overflow-y: auto;
        }

        #formPanel::-webkit-scrollbar {
            width: 6px;
        }

        #formPanel::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #formPanel::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #formPanel::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>

    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Master Kategori Rekap</h1>
                <p class="text-gray-600 mt-1">Kelola data kategori rekap</p>
            </div>
            <button id="btnTambah" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Kategori
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterForm">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="q">Cari Kategori</label>
                        <input type="text" id="q" name="q" class="filter-input" placeholder="Nama kategori..." value="{{ request('q') }}">
                    </div>
                    <div class="filter-item">
                        <button type="button" id="resetFilter" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div id="resultsInfo" class="mb-4 text-sm text-gray-600"></div>

        <!-- Table Container with Loading -->
        <div class="bg-white shadow rounded-lg loading-overlay" id="tableContainer">
            <div class="loading-spinner">
                <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div id="tableContent">
                @include('rekap-kategori.table-content', ['kategoris' => $kategoris])
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContent" class="mt-6">
            @include('components.paginator', [
                'paginator' => $kategoris->withPath(route('rekap-kategori.filter'))
            ])
        </div>
    </div>

    <!-- Slide-over Form -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full" id="formPanel">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="formTitle" class="text-lg font-semibold text-gray-900">Tambah Kategori</h2>
                    <button id="closeForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="kategoriForm" class="flex-1 flex flex-col">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="">
                    <input type="hidden" id="editId" name="id" value="">

                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                            <input type="text" id="f_nama" name="nama" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Masukkan nama kategori">
                            <div id="errorNama" class="text-red-500 text-sm mt-2 hidden"></div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 p-6">
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors">
                                Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const btnTambah = document.getElementById('btnTambah');
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const closeForm = document.getElementById('closeForm');
        const kategoriForm = document.getElementById('kategoriForm');
        const filterForm = document.getElementById('filterForm');
        const resetFilter = document.getElementById('resetFilter');
        const tableContainer = document.getElementById('tableContainer');

        // Show form for tambah
        btnTambah.addEventListener('click', () => {
            document.getElementById('formTitle').textContent = 'Tambah Kategori';
            document.getElementById('methodField').value = '';
            document.getElementById('editId').value = '';
            kategoriForm.reset();
            document.getElementById('errorNama').classList.add('hidden');
            formSlide.classList.remove('hidden');
            setTimeout(() => formPanel.classList.remove('translate-x-full'), 10);
        });

        // Close form
        closeForm.addEventListener('click', closeFormPanel);
        formSlide.addEventListener('click', (e) => {
            if (e.target === formSlide) closeFormPanel();
        });

        function closeFormPanel() {
            formPanel.classList.add('translate-x-full');
            setTimeout(() => formSlide.classList.add('hidden'), 300);
        }

        // Submit form
        kategoriForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nama = document.getElementById('f_nama').value;
            const editId = document.getElementById('editId').value;
            const method = document.getElementById('methodField').value || 'POST';

            try {
                const url = editId 
                    ? `/rekap-kategori/${editId}` 
                    : `{{ route('rekap-kategori.store') }}`;

                const formData = new FormData(kategoriForm);
                
                const response = await fetch(url, {
                    method: method === 'PUT' ? 'POST' : method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    window.notyf.success(data.message || 'Berhasil disimpan');
                    closeFormPanel();
                    loadTable();
                } else {
                    if (data.errors && data.errors.nama) {
                        document.getElementById('errorNama').textContent = data.errors.nama[0];
                        document.getElementById('errorNama').classList.remove('hidden');
                    }
                }
            } catch (error) {
                window.notyf.error('Terjadi kesalahan');
                console.error(error);
            }
        });

        // Filter
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loadTable();
        });

        document.getElementById('q').addEventListener('input', () => {
            loadTable();
        });

        resetFilter.addEventListener('click', () => {
            document.getElementById('q').value = '';
            loadTable();
        });

        // Load table
        async function loadTable() {
            tableContainer.classList.add('loading');
            const params = new URLSearchParams(new FormData(filterForm));

            try {
                const response = await fetch(`{{ route('rekap-kategori.filter') }}?${params}`);
                const data = await response.json();

                document.getElementById('tableContent').innerHTML = data.table;
                document.getElementById('paginationContent').innerHTML = data.pagination;

                // Update results info
                const total = new URLSearchParams(params).toString() ? 'hasil pencarian' : 'total data';
                document.getElementById('resultsInfo').innerHTML = `Menampilkan data kategori rekap`;
            } catch (error) {
                window.notyf.error('Gagal memuat data');
                console.error(error);
            } finally {
                tableContainer.classList.remove('loading');
            }
        }

        // Edit
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-edit')) {
                const id = e.target.closest('.btn-edit').dataset.id;
                fetchAndEdit(id);
            }
        });

        async function fetchAndEdit(id) {
            try {
                const response = await fetch(`/rekap-kategori/${id}/edit`);
                const data = await response.json();

                document.getElementById('formTitle').textContent = 'Edit Kategori';
                document.getElementById('methodField').value = 'PUT';
                document.getElementById('editId').value = data.id;
                document.getElementById('f_nama').value = data.nama;
                document.getElementById('errorNama').classList.add('hidden');
                
                formSlide.classList.remove('hidden');
                setTimeout(() => formPanel.classList.remove('translate-x-full'), 10);
            } catch (error) {
                window.notyf.error('Gagal memuat data');
                console.error(error);
            }
        }

        // Delete
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-delete')) {
                const id = e.target.closest('.btn-delete').dataset.id;
                if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                    deleteKategori(id);
                }
            }
        });

        async function deleteKategori(id) {
            try {
                const response = await fetch(`/rekap-kategori/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    }
                });

                if (response.ok) {
                    window.notyf.success('Kategori berhasil dihapus');
                    loadTable();
                } else {
                    const data = await response.json();
                    window.notyf.error(data.message || 'Gagal menghapus kategori');
                }
            } catch (error) {
                window.notyf.error('Terjadi kesalahan');
                console.error(error);
            }
        }

        // Initial load
        loadTable();
    </script>
@endsection
