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

        #formPanel {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            max-height: 100vh;
        }

        /* Custom Scrollbar */
        #formPanel::-webkit-scrollbar {
            width: 6px;
        }

        #formPanel::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        #formPanel::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        #formPanel::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Form content wrapper untuk proper spacing */
        .form-content {
            padding-bottom: 20px;
            /* Space untuk button yang fixed */
        }

        /* Fixed bottom button */
        .form-footer {
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 1rem;
            margin-top: auto;
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
                        <input type="date" id="tanggal_dari" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                            class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 200px;">
                        <label for="no_penawaran">No Penawaran</label>
                        <input type="text" id="no_penawaran" name="no_penawaran" placeholder="Cari no penawaran..."
                            value="{{ request('no_penawaran') }}" class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 250px;">
                        <label for="nama_perusahaan">Nama Perusahaan</label>
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" placeholder="Cari nama perusahaan..."
                            value="{{ request('nama_perusahaan') }}" class="filter-input">
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
                <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
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

    <!-- Slide-over Form dengan Scrollbar -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full"
            id="formPanel">

            <!-- Header (Fixed) -->
            <div class="sticky top-0 bg-white border-b border-gray-100 p-6 z-10">
                <div class="flex justify-between items-center">
                    <h2 id="formTitle" class="text-xl font-bold">Tambah Penawaran</h2>
                    <button id="closeForm" class="text-gray-500 hover:text-gray-700 p-1 hover:bg-gray-100 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Form Content (Scrollable) -->
            <div class="form-content p-6">
                <form id="penawaranForm" method="POST" action="{{ route('penawaran.store') }}">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="">
                    <input type="hidden" id="editId" value="">

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Perihal</label>
                            <input type="text" name="perihal" id="f_perihal"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Nama Perusahaan</label>
                            <select name="nama_perusahaan" id="f_nama_perusahaan"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required>
                                <option value="">Pilih Perusahaan...</option>
                                @foreach($mitras as $mitra)
                                    <option value="{{ $mitra['nama'] }}" data-kota="{{ $mitra['kota'] }}"
                                        data-provinsi="{{ $mitra['provinsi'] }}">
                                        {{ $mitra['display'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Lokasi</label>
                            <input type="text" name="lokasi" id="f_lokasi"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required readonly>
                            <p class="text-xs text-gray-500 mt-1">Akan otomatis terisi jika pilih dari Mitra</p>
                        </div>

                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">PIC Perusahaan</label>
                            <input type="text" name="pic_perusahaan" id="f_pic_perusahaan"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                required>
                        </div>

                        <div class="add-only" id="noPenawaranGroup">
                            <label class="block mb-2 font-medium text-sm text-gray-700">No Penawaran</label>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="text-sm text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-300 whitespace-nowrap">
                                    PIB/SS-SBY/JK/{{ Auth::id() }}-
                                </span>
                                <input type="text" name="no_penawaran_suffix" id="f_no_penawaran_suffix"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="VII/2025">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Otomatis dibuat saat tambah. Disembunyikan saat edit.</p>
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <button type="submit"
                            class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            Simpan Penawaran
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Button (Fixed) -->
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold mb-2">Hapus Penawaran?</h3>
            <p class="text-sm text-gray-600 mb-4">Data akan dihapus sementara (soft delete) dan bisa dipulihkan.</p>
            <div class="flex justify-end gap-3">
                <button id="btnCancelDelete" class="px-4 py-2 border rounded text-sm">Batal</button>
                <button id="btnConfirmDelete" class="px-4 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        /* ================== KONFIG DASAR ================== */
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const ROUTES = {
            store: "{{ route('penawaran.store') }}",
            filter: "{{ route('penawaran.filter') }}",
            base: "{{ url('penawaran') }}"
        };
        const NO_PREFIX = "PIB/SS-SBY/JK/{{ Auth::id() }}-";

        /* ================== ELEMEN FORM / MODAL ================== */
        const btnTambah = document.getElementById('btnTambah');
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const closeFormBtn = document.getElementById('closeForm');
        const penawaranForm = document.getElementById('penawaranForm');
        const methodField = document.getElementById('methodField');
        const editIdField = document.getElementById('editId');
        const formTitle = document.getElementById('formTitle');
        const grpNoPenawaran = document.getElementById('noPenawaranGroup');

        const f_perihal = document.getElementById('f_perihal');
        const f_nama_perusahaan = document.getElementById('f_nama_perusahaan');
        const f_lokasi = document.getElementById('f_lokasi');
        const f_pic_perusahaan = document.getElementById('f_pic_perusahaan');
        const f_no_suffix = document.getElementById('f_no_penawaran_suffix');

        /* ================== MODAL HAPUS ================== */
        const confirmModal = document.getElementById('confirmModal');
        const btnCancelDelete = document.getElementById('btnCancelDelete');
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        let deleteTargetId = null;

        /* ================== TABLE / FILTER ================== */
        const tableContainer = document.getElementById('tableContainer');
        const tableContent = document.getElementById('tableContent');
        const paginationWrap = document.getElementById('paginationContent');
        const resultsInfo = document.getElementById('resultsInfo');
        let filterTimeoutId;

        /* ================== UTIL ================== */
        function toastSafe(payload) {
            if (window.toast) toast(payload); else console.log(payload);
        }
        function showLoading() { tableContainer.classList.add('loading'); }
        function hideLoading() { tableContainer.classList.remove('loading'); }
        function currentUrlParams() {
            return new URLSearchParams(window.location.search);
        }
        function pushUrl(params) {
            const url = new URL(window.location);
            // bersihkan dulu
            [...url.searchParams.keys()].forEach(k => url.searchParams.delete(k));
            params.forEach((v, k) => { if (v) url.searchParams.set(k, v); });
            window.history.pushState({}, '', url.toString());
        }

        /* ================== SLIDE FORM ================== */
        function openSlide() {
            formSlide.classList.remove('hidden');
            requestAnimationFrame(() => {
                formPanel.classList.remove('translate-x-full');
                formPanel.classList.add('translate-x-0');
            });
        }
        function closeSlide() {
            formPanel.classList.remove('translate-x-0');
            formPanel.classList.add('translate-x-full');
            setTimeout(() => formSlide.classList.add('hidden'), 350);
        }

        /* ================== PERUSAHAAN DROPDOWN LOGIC ================== */
        f_nama_perusahaan.addEventListener('change', function () {
            const selectedValue = this.value;
            const selectedOption = this.options[this.selectedIndex];

            if (selectedValue) {
                // Auto-fill lokasi dari data mitra
                const kota = selectedOption.dataset.kota;
                const provinsi = selectedOption.dataset.provinsi;

                if (kota) {
                    const lokasi = provinsi ? `${kota}, ${provinsi}` : kota;
                    f_lokasi.value = lokasi;
                    f_lokasi.readOnly = true;
                    f_lokasi.classList.add('bg-gray-100');
                }
            } else {
                // Reset lokasi jika tidak ada pilihan
                f_lokasi.value = '';
                f_lokasi.readOnly = false;
                f_lokasi.classList.remove('bg-gray-100');
            }
        });

        function resetForm() {
            penawaranForm.reset();
            methodField.value = '';
            editIdField.value = '';

            // Reset lokasi state
            f_lokasi.readOnly = false;
            f_lokasi.classList.remove('bg-gray-100');
        }

        function setupAdd() {
            resetForm();
            formTitle.textContent = 'Tambah Penawaran';
            grpNoPenawaran.classList.remove('hidden');
            penawaranForm.dataset.mode = 'add';
            penawaranForm.action = ROUTES.store;
            openSlide();
        }

        function setupEdit(id) {
            fetch(`${ROUTES.base}/${id}/edit`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => {
                    if (!r.ok) throw new Error('Gagal load data');
                    return r.json();
                })
                .then(d => {
                    formTitle.textContent = 'Edit Penawaran';
                    penawaranForm.dataset.mode = 'edit';
                    penawaranForm.action = `${ROUTES.base}/${id}`;
                    methodField.value = 'PUT';
                    editIdField.value = id;

                    // isi field
                    f_perihal.value = d.perihal ?? '';
                    f_lokasi.value = d.lokasi ?? '';
                    f_pic_perusahaan.value = d.pic_perusahaan ?? '';
                    f_no_suffix.value = '';

                    // Set nama perusahaan dari dropdown
                    const namaPerusahaan = d.nama_perusahaan ?? '';
                    f_nama_perusahaan.value = namaPerusahaan;

                    // Trigger change event untuk auto-fill lokasi jika ada match
                    f_nama_perusahaan.dispatchEvent(new Event('change'));

                    grpNoPenawaran.classList.add('hidden');
                    openSlide();
                })
                .catch(e => {
                    toastSafe({ type: 'error', title: 'Error', message: e.message });
                });
        }

        /* ================== SUBMIT FORM (AJAX) ================== */
        penawaranForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const mode = penawaranForm.dataset.mode || 'add';

            const fd = new FormData(penawaranForm);

            if (mode === 'add') {
                const suffix = (f_no_suffix.value || '').trim();
                if (suffix) {
                    fd.append('no_penawaran', NO_PREFIX + suffix);
                }
            } else {
                fd.append('_method', 'PUT');
            }

            const submitBtn = penawaranForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

            fetch(penawaranForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            })
                .then(r => r.json().catch(() => ({})))
                .then(res => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    closeSlide();
                    performFilter();
                    if (res.notify) {
                        toastSafe(res.notify);
                    } else {
                        toastSafe({ type: 'success', title: 'Sukses', message: mode === 'add' ? 'Penawaran ditambahkan' : 'Penawaran diperbarui' });
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    toastSafe({ type: 'error', title: 'Error', message: 'Gagal simpan data' });
                });
        });

        /* ================== HAPUS (SOFT DELETE) ================== */
        function openConfirmDelete(id) {
            deleteTargetId = id;
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
        }
        function closeConfirmDelete() {
            confirmModal.classList.add('hidden');
            confirmModal.classList.remove('flex');
            deleteTargetId = null;
        }
        btnCancelDelete.addEventListener('click', closeConfirmDelete);
        confirmModal.addEventListener('click', e => {
            if (e.target === confirmModal) closeConfirmDelete();
        });
        btnConfirmDelete.addEventListener('click', () => {
            if (!deleteTargetId) return;
            fetch(`${ROUTES.base}/${deleteTargetId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            })
                .then(r => r.json().catch(() => ({})))
                .then(res => {
                    notyf.success(res.message ?? 'Penawaran berhasil dihapus');
                    closeConfirmDelete();
                    performFilter();
                    toastSafe(res.notify ?? { type: 'success', title: 'Berhasil', message: 'Penawaran dihapus (soft)' });
                })
                .catch(() => {
                    notyf.error(res.message ?? 'Mitra gagal dihapus');
                    closeConfirmDelete();
                    performFilter();
                    toastSafe({ type: 'error', title: 'Error', message: 'Gagal hapus' });
                });
        });

        /* ================== EVENT DELEGATION UNTUK EDIT / HAPUS ================== */
        document.addEventListener('click', e => {
            const editBtn = e.target.closest('.btn-edit');
            if (editBtn) {
                e.preventDefault();
                setupEdit(editBtn.dataset.id);
                return;
            }
            const delBtn = e.target.closest('.btn-delete');
            if (delBtn) {
                e.preventDefault();
                openConfirmDelete(delBtn.dataset.id);
            }
        });

        /* ================== FILTER / SORT / PAGINATION (AJAX) ================== */
        function attachSortListeners() {
            document.querySelectorAll('.sort-button').forEach(btn => {
                btn.addEventListener('click', function () {
                    const column = this.dataset.column;
                    const direction = this.dataset.direction;
                    const formData = new FormData(document.getElementById('filterForm'));
                    const params = new URLSearchParams(formData);
                    params.set('sort', column);
                    params.set('direction', direction);
                    fetchList(params);
                });
            });
        }
        function attachPaginationListeners() {
            document.querySelectorAll('.pagination-link').forEach(a => {
                a.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page') || 1;
                    const formData = new FormData(document.getElementById('filterForm'));
                    const params = new URLSearchParams(formData);
                    params.set('page', page);

                    // keep sort
                    const cur = currentUrlParams();
                    if (cur.get('sort')) params.set('sort', cur.get('sort'));
                    if (cur.get('direction')) params.set('direction', cur.get('direction'));

                    fetchList(params);
                });
            });
        }
        function fetchList(params) {
            showLoading();
            fetch(`${ROUTES.filter}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    tableContent.innerHTML = data.table;
                    paginationWrap.innerHTML = data.pagination;
                    if (resultsInfo) resultsInfo.innerHTML = data.info || '';
                    hideLoading();
                    attachPaginationListeners();
                    attachSortListeners();
                    pushUrl(params);
                })
                .catch(e => {
                    console.error(e);
                    hideLoading();
                });
        }
        function performFilter() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData);
            // keep sort
            const cur = currentUrlParams();
            if (cur.get('sort')) params.set('sort', cur.get('sort'));
            if (cur.get('direction')) params.set('direction', cur.get('direction'));
            fetchList(params);
        }

        /* ================== FILTER INPUT LISTENERS ================== */
        document.querySelectorAll('#filterForm .filter-input').forEach(el => {
            if (el.type === 'text') {
                el.addEventListener('input', () => {
                    clearTimeout(filterTimeoutId);
                    filterTimeoutId = setTimeout(() => performFilter(), 700);
                });
            } else {
                el.addEventListener('change', performFilter);
            }
        });
        const resetBtn = document.getElementById('resetFilter');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                document.getElementById('filterForm').reset();
                performFilter();
            });
        }

        /* ================== OPEN / CLOSE FORM BUTTONS ================== */
        btnTambah.addEventListener('click', setupAdd);
        closeFormBtn.addEventListener('click', closeSlide);
        formSlide.addEventListener('click', e => {
            if (e.target === formSlide) closeSlide();
        });

        /* ================== INIT ================== */
        document.addEventListener('DOMContentLoaded', () => {
            attachPaginationListeners();
            attachSortListeners();
        });
    </script>
@endpush