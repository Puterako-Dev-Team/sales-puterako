{{-- filepath: resources/views/rekap/list.blade.php --}}
@extends('layouts.app')

@section('content')
    <style>
        .loading-overlay {
            position: relative;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            display: none;
        }

        .loading-overlay.loading .loading-spinner {
            display: block;
        }

        .loading-overlay.loading #tableContent {
            opacity: 0.3;
            pointer-events: none;
        }

        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">Daftar Rekap</h1>
            <button id="btnTambahRekap"
                class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
                <x-lucide-plus class="w-5 h-5 inline" />
                Tambah Rekap
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-card ">
            <form id="filterFormRekap">
                <div class="flex items-end gap-4 flex-wrap">
                    <div class="flex flex-col" style="flex: 0 0 160px;">
                        <label for="tanggal_dari" class="text-xs font-semibold mb-1">Tanggal Dari</label>
                        <input type="date" id="tanggal_dari" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                    </div>
                    <div class="flex flex-col" style="flex: 1 1 180px;">
                        <label for="no_penawaran" class="text-xs font-semibold mb-1">No Penawaran</label>
                        <input type="text" id="no_penawaran" name="no_penawaran" placeholder="Cari no penawaran..."
                            value="{{ request('no_penawaran') }}"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                    </div>
                    <div class="flex flex-col" style="flex: 1 1 220px;">
                        <label for="nama_perusahaan" class="text-xs font-semibold mb-1">Nama Perusahaan</label>
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan"
                            placeholder="Cari nama perusahaan..." value="{{ request('nama_perusahaan') }}"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                    </div>
                    <div class="flex flex-col" style="flex: 0 0 150px;">
                        <label for="nama_rekap" class="text-xs font-semibold mb-1">Nama Rekap</label>
                        <input type="text" id="nama_rekap" name="nama_rekap" placeholder="Cari nama rekap..."
                            value="{{ request('nama_rekap') }}"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                    </div>
                    <div class="flex flex-col" style="flex: 0 0 150px;">
                        <label for="pic_admin" class="text-xs font-semibold mb-1">PIC Admin</label>
                        <select id="pic_admin" name="pic_admin"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                            <option value="">Semua PIC</option>
                            @foreach ($picAdmins ?? [] as $pic)
                                <option value="{{ $pic }}" {{ request('pic_admin') == $pic ? 'selected' : '' }}>
                                    {{ $pic }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col" style="flex: 0 0 auto;">
                        <label class="text-xs opacity-0 mb-1">&nbsp;</label>
                        <button type="button" id="resetFilterRekap"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition flex items-center gap-2 text-sm">
                            <x-lucide-refresh-cw class="w-4 h-4" />
                            Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg loading-overlay" id="tableContainer" style="position:relative;">
            <div class="loading-spinner" id="rekapLoadingSpinner" style="display:none;">
                <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div id="tableContent">
                @include('rekap.table-content', ['rekaps' => $rekaps])
            </div>
        </div>

        <div class="mt-6">
            @include('rekap.pagination', ['rekaps' => $rekaps])
        </div>
    </div>

    <!-- Slide-over Form Rekap -->
    <div id="formSlideRekap" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full"
            id="formPanelRekap">
            <div class="sticky top-0 bg-white border-b border-gray-100 p-6 z-10">
                <div class="flex justify-between items-center">
                    <h2 id="formTitleRekap" class="text-xl font-bold">Tambah Rekap</h2>
                    <button id="closeFormRekap" class="text-gray-500 hover:text-gray-700 p-1 hover:bg-gray-100 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24"
                            fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <form id="rekapForm" method="POST" action="{{ route('rekap.store') }}">
                    @csrf
                    <input type="hidden" id="rekapMethodField" name="_method" value="">
                    <input type="hidden" id="rekapEditId" value="">
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Nama Rekap</label>
                            <input type="text" name="nama" id="f_nama_rekap"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">No Penawaran <span class="text-gray-500 text-xs">(Opsional)</span></label>
                            <select name="penawaran_id" id="f_penawaran_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">- Pilih Penawaran (Opsional) -</option>
                                @foreach ($penawarans as $p)
                                    <option value="{{ $p->id_penawaran }}" data-perusahaan="{{ $p->nama_perusahaan }}">
                                        {{ $p->no_penawaran }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Nama Perusahaan</label>
                            <input type="text" name="nama_perusahaan" id="f_nama_perusahaan"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50"
                                required>
                            <small class="text-gray-500 text-xs mt-1 block">Isi dari dropdown No Penawaran atau ketik manual</small>
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <button type="submit"
                            class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 font-medium">
                            Simpan Rekap
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmDeleteModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-sm mx-4">
            <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
                <x-lucide-alert-circle class="w-5 h-5 text-red-500" />
                Hapus Rekap?
            </h3>
            <p class="text-sm text-gray-600 mb-4">Anda yakin ingin menghapus rekap <strong id="deleteRekapName"></strong>? Data akan dipindahkan ke trash dan dapat dipulihkan kembali.</p>
            <div class="flex justify-end gap-3">
                <button id="btnCancelDelete" class="px-4 py-2 border rounded text-sm hover:bg-gray-50">Batal</button>
                <button id="btnConfirmDelete" class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">Hapus</button>
            </div>
        </div>
    </div>

    <script>
        const btnTambahRekap = document.getElementById('btnTambahRekap');
        const formSlideRekap = document.getElementById('formSlideRekap');
        const formPanelRekap = document.getElementById('formPanelRekap');
        const closeFormRekap = document.getElementById('closeFormRekap');
        const rekapForm = document.getElementById('rekapForm');
        const f_penawaran_id = document.getElementById('f_penawaran_id');
        const f_nama_perusahaan = document.getElementById('f_nama_perusahaan');
        const formTitleRekap = document.getElementById('formTitleRekap');
        const rekapMethodField = document.getElementById('rekapMethodField');
        const rekapEditId = document.getElementById('rekapEditId');

        // Modal delete
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');
        const btnCancelDelete = document.getElementById('btnCancelDelete');
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        const deleteRekapName = document.getElementById('deleteRekapName');

        let deleteTargetId = null;
        let deleteTargetName = null;
        const CSRF_TOKEN = '{{ csrf_token() }}';

        function openForm(mode = 'add', data = null) {
            formTitleRekap.textContent = mode === 'add' ? 'Tambah Rekap' : 'Edit Rekap';
            rekapMethodField.value = mode === 'edit' ? 'PUT' : '';
            rekapEditId.value = data?.id || '';

            if (mode === 'add') {
                rekapForm.reset();
                f_nama_perusahaan.value = '';
                f_penawaran_id.value = '';
                f_nama_perusahaan.readOnly = false;
                f_nama_perusahaan.classList.remove('bg-gray-100', 'cursor-not-allowed');
                f_nama_perusahaan.classList.add('bg-gray-50');
            } else if (mode === 'edit' && data) {
                // Populate form dengan data yang di-fetch
                const namaRekapInput = document.getElementById('f_nama_rekap');
                if (namaRekapInput) {
                    namaRekapInput.value = data.nama || '';
                }
                if (f_penawaran_id) {
                    f_penawaran_id.value = data.penawaran_id || '';
                }
                if (f_nama_perusahaan) {
                    f_nama_perusahaan.value = data.nama_perusahaan || '';
                }
                
                // Set read-only status based on penawaran_id
                if (data.penawaran_id) {
                    f_nama_perusahaan.readOnly = true;
                    f_nama_perusahaan.classList.add('bg-gray-100', 'cursor-not-allowed');
                    f_nama_perusahaan.classList.remove('bg-gray-50');
                } else {
                    f_nama_perusahaan.readOnly = false;
                    f_nama_perusahaan.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    f_nama_perusahaan.classList.add('bg-gray-50');
                }
            }

            formSlideRekap.classList.remove('hidden');
            requestAnimationFrame(() => {
                formPanelRekap.classList.remove('translate-x-full');
                formPanelRekap.classList.add('translate-x-0');
            });
        }

        function closeForm() {
            formPanelRekap.classList.remove('translate-x-0');
            formPanelRekap.classList.add('translate-x-full');
            setTimeout(() => formSlideRekap.classList.add('hidden'), 350);
        }

        btnTambahRekap.addEventListener('click', function() {
            openForm('add');
        });

        closeFormRekap.addEventListener('click', closeForm);

        formSlideRekap.addEventListener('click', function(e) {
            if (e.target === formSlideRekap) {
                closeForm();
            }
        });

        // Form submit untuk add/edit
        rekapForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const isEdit = rekapEditId.value;
            const action = isEdit 
                ? "{{ url('rekap') }}/" + rekapEditId.value 
                : "{{ route('rekap.store') }}";
            
            const formData = new FormData(this);
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            const submitBtn = rekapForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.success) {
                    closeForm();
                    if (window.toast) {
                        window.toast({
                            type: 'success',
                            title: 'Sukses',
                            message: isEdit ? 'Rekap berhasil diperbarui' : 'Rekap berhasil ditambahkan'
                        });
                    }
                    // Reload table
                    const params = Object.fromEntries(new FormData(document.getElementById('filterFormRekap')));
                    fetchRekapTable(params);
                } else {
                    if (window.toast) {
                        window.toast({
                            type: 'error',
                            title: 'Error',
                            message: data.message || 'Gagal simpan data'
                        });
                    }
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                if (window.toast) {
                    window.toast({
                        type: 'error',
                        title: 'Error',
                        message: 'Gagal simpan data'
                    });
                }
            });
        });

        // Auto-fill nama perusahaan dari penawaran
        f_penawaran_id.addEventListener('change', function() {
            var perusahaan = this.options[this.selectedIndex].getAttribute('data-perusahaan');
            f_nama_perusahaan.value = perusahaan || '';
            
            // Jika ada penawaran dipilih, buat read-only dan background gray
            if (this.value) {
                f_nama_perusahaan.readOnly = true;
                f_nama_perusahaan.classList.add('bg-gray-100', 'cursor-not-allowed');
                f_nama_perusahaan.classList.remove('bg-gray-50');
            } else {
                // Jika tidak ada penawaran, buat editable
                f_nama_perusahaan.readOnly = false;
                f_nama_perusahaan.classList.remove('bg-gray-100', 'cursor-not-allowed');
                f_nama_perusahaan.classList.add('bg-gray-50');
                f_nama_perusahaan.value = '';
            }
        });

        // Event delegation untuk edit button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-rekap')) {
                const btn = e.target.closest('.btn-edit-rekap');
                
                // Cegah jika button disabled
                if (btn.disabled) {
                    if (window.toast) {
                        window.toast({
                            type: 'warning',
                            title: 'Perhatian',
                            message: 'Rekap yang sudah disetujui tidak bisa diedit'
                        });
                    }
                    return;
                }
                
                const id = btn.dataset.id;
                
                fetch("{{ url('rekap') }}/" + id + "/edit", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        openForm('edit', data);
                    }
                })
                .catch(err => console.error(err));
            }
        });

        // Event delegation untuk delete button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-rekap')) {
                const btn = e.target.closest('.btn-delete-rekap');
                const id = btn.dataset.id;
                const row = btn.closest('tr');
                const namaRekap = row.querySelector('td:nth-child(3)').textContent;
                
                deleteTargetId = id;
                deleteTargetName = namaRekap;
                deleteRekapName.textContent = namaRekap;
                
                confirmDeleteModal.classList.remove('hidden');
                confirmDeleteModal.classList.add('flex');
            }
        });

        btnCancelDelete.addEventListener('click', function() {
            confirmDeleteModal.classList.add('hidden');
            confirmDeleteModal.classList.remove('flex');
            deleteTargetId = null;
        });

        confirmDeleteModal.addEventListener('click', function(e) {
            if (e.target === confirmDeleteModal) {
                confirmDeleteModal.classList.add('hidden');
                confirmDeleteModal.classList.remove('flex');
                deleteTargetId = null;
            }
        });

        btnConfirmDelete.addEventListener('click', function() {
            if (!deleteTargetId) return;

            const btn = btnConfirmDelete;
            btn.disabled = true;

            fetch("{{ url('rekap') }}/" + deleteTargetId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                confirmDeleteModal.classList.add('hidden');
                confirmDeleteModal.classList.remove('flex');
                
                if (data.success) {
                    if (window.toast) {
                        window.toast({
                            type: 'success',
                            title: 'Sukses',
                            message: 'Rekap berhasil dihapus'
                        });
                    }
                    // Reload table
                    const params = Object.fromEntries(new FormData(document.getElementById('filterFormRekap')));
                    fetchRekapTable(params);
                } else {
                    if (window.toast) {
                        window.toast({
                            type: 'error',
                            title: 'Error',
                            message: data.message || 'Gagal hapus data'
                        });
                    }
                }
                deleteTargetId = null;
            })
            .catch(err => {
                btn.disabled = false;
                if (window.toast) {
                    window.toast({
                        type: 'error',
                        title: 'Error',
                        message: 'Gagal hapus data'
                    });
                }
            });
        });

        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
@endsection

@push('scripts')
    <script>
        const tableContainer = document.getElementById('tableContainer');
        const tableContent = document.getElementById('tableContent');
        const paginationContainer = document.querySelector('.mt-6');

        function showLoading() {
            tableContainer.classList.add('loading');
            document.getElementById('rekapLoadingSpinner').style.display = 'block';
        }

        function hideLoading() {
            tableContainer.classList.remove('loading');
            document.getElementById('rekapLoadingSpinner').style.display = 'none';
        }

        function fetchRekapTable(params = {}) {
            showLoading();
            let url = "{{ route('rekap.list') }}";
            if (Object.keys(params).length > 0) {
                url += '?' + new URLSearchParams(params).toString();
            }
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.querySelector('#tableContent').innerHTML;
                    const newPagination = doc.querySelector('.mt-6').innerHTML;
                    tableContent.innerHTML = newTable;
                    paginationContainer.innerHTML = newPagination;
                    hideLoading();
                })
                .catch(() => hideLoading());
        }

        document.querySelectorAll('#filterFormRekap .filter-input').forEach(el => {
            if (el.type === 'text') {
                el.addEventListener('input', function() {
                    clearTimeout(window.filterTimeoutId);
                    window.filterTimeoutId = setTimeout(() => {
                        const params = Object.fromEntries(new FormData(document.getElementById(
                            'filterFormRekap')));
                        fetchRekapTable(params);
                    }, 700);
                });
            } else {
                el.addEventListener('change', function() {
                    const params = Object.fromEntries(new FormData(document.getElementById(
                        'filterFormRekap')));
                    fetchRekapTable(params);
                });
            }
        });
        document.getElementById('resetFilterRekap').addEventListener('click', function() {
            document.getElementById('filterFormRekap').reset();
            fetchRekapTable({});
        });

        // Pagination AJAX
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('pagination-link')) {
                e.preventDefault();
                showLoading();
                fetch(e.target.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        tableContainer.innerHTML = doc.querySelector('#tableContainer').innerHTML;
                        paginationContainer.innerHTML = doc.querySelector('.mt-6').innerHTML;
                        hideLoading();
                    })
                    .catch(() => hideLoading());
            }
        });
    </script>
@endpush
