@extends('layouts.app')

@section('content')
    <style>
        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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

        .filter-item input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .filter-item input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
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

        #formPanelMitra {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .error-text {
            display: none;
        }

        .error-text.show {
            display: block;
        }

        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .input-error:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2) !important;
        }
    </style>

    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">List Mitra</h1>
            <button id="btnTambahMitra"
                class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
                <x-lucide-plus class="w-5 h-5 inline" />
                Tambah Mitra
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterForm">
                <div class="flex items-end gap-4 flex-wrap">
                    <div class="filter-item" style="flex: 1 1 300px;">
                        <label for="q">Cari Nama/Kota/Alamat</label>
                        <input type="text" id="q" name="q" placeholder="Cari nama perusahaan, kota, alamat..."
                            value="{{ request('q') }}" class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 0 0 200px;">
                        <label for="provinsi_filter">Provinsi</label>
                        <input type="text" id="provinsi_filter" name="provinsi_filter" placeholder="Cari provinsi..."
                            value="{{ request('provinsi_filter') }}" class="filter-input">
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

        <!-- Table Container with Loading -->
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
                @include('mitra.table-content', ['mitras' => $mitras])
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContent" class="mt-6">
            {{ $mitras->appends(request()->query())->links('penawaran.pagination') }}
        </div>
    </div>

    <!-- Slide-over Form (smooth animation) -->
    <div id="formSlideMitra" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg p-8 transform translate-x-full"
            id="formPanelMitra">
            <div class="flex justify-between items-center mb-4">
                <h2 id="mitraFormTitle" class="text-xl font-bold">Tambah Mitra</h2>
                <button id="closeFormMitra" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <form id="formMitra">
                @csrf
                <input type="hidden" id="mitraMethodField" name="_method" value="">
                <input type="hidden" id="mitraEditId" value="">
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Nama Perusahaan</label>
                    <input type="text" name="nama_mitra" id="f_nama_mitra" class="w-full border rounded px-3 py-2 text-sm"
                        required>
                    <div id="error_nama_mitra" class="error-text text-red-500 text-xs mt-1 hidden"></div>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Provinsi</label>
                    <select name="provinsi" id="provinsiSelect" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Pilih Provinsi...</option>
                    </select>
                    <div id="error_provinsi" class="error-text text-red-500 text-xs mt-1 hidden"></div>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Kota/Kabupaten</label>
                    <select name="kota" id="kotaSelect" class="w-full border rounded px-3 py-2 text-sm" required disabled>
                        <option value="">Pilih Kota/Kabupaten...</option>
                    </select>
                    <div id="error_kota" class="error-text text-red-500 text-xs mt-1 hidden"></div>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Alamat</label>
                    <textarea name="alamat" id="f_alamat" rows="3"
                        class="w-full border rounded px-3 py-2 text-sm"></textarea>
                    <div id="error_alamat" class="error-text text-red-500 text-xs mt-1 hidden"></div>
                </div>
                <div class="absolute bottom-0 left-0 w-full p-4 bg-white border-t">
                    <button type="submit"
                        class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold mb-2">Hapus Mitra?</h3>
            <p class="text-sm text-gray-600 mb-4">Data mitra akan dihapus permanen.</p>
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
            store: "{{ route('mitra.store') }}",
            filter: "{{ route('mitra.filter') }}",
            base: "{{ url('mitra') }}"
        };

        // Wilayah Indonesia (EMSIFA)
        const EMSIFA = 'https://www.emsifa.com/api-wilayah-indonesia/api';
        let provincesLoaded = false;
        const regencyCache = new Map();

        /* ================== ELEMEN ================== */
        const btnTambahMitra = document.getElementById('btnTambahMitra');
        const formSlideMitra = document.getElementById('formSlideMitra');
        const formPanelMitra = document.getElementById('formPanelMitra');
        const closeFormMitra = document.getElementById('closeFormMitra');
        const formMitra = document.getElementById('formMitra');
        const mitraFormTitle = document.getElementById('mitraFormTitle');
        const mitraMethodField = document.getElementById('mitraMethodField');
        const mitraEditId = document.getElementById('mitraEditId');

        const f_nama_mitra = document.getElementById('f_nama_mitra');
        const f_alamat = document.getElementById('f_alamat');
        const provinsiSelect = document.getElementById('provinsiSelect');
        const kotaSelect = document.getElementById('kotaSelect');

        const tableContainer = document.getElementById('tableContainer');
        const tableContent = document.getElementById('tableContent');
        const paginationWrap = document.getElementById('paginationContent');
        const resultsInfo = document.getElementById('resultsInfo');

        const confirmModal = document.getElementById('confirmModal');
        const btnCancelDelete = document.getElementById('btnCancelDelete');
        const btnConfirmDelete = document.getElementById('btnConfirmDelete');
        let deleteTargetId = null;
        let filterTimeoutId;

        /* ================== UTIL ================== */
        function toastSafe(payload) {
            if (window.toast) toast(payload); else console.log(payload);
        }
        function showLoading() { tableContainer.classList.add('loading'); }
        function hideLoading() { tableContainer.classList.remove('loading'); }
        function pushUrl(params) {
            const url = new URL(window.location);
            [...url.searchParams.keys()].forEach(k => url.searchParams.delete(k));
            params.forEach((v, k) => { if (v) url.searchParams.set(k, v); });
            window.history.pushState({}, '', url.toString());
        }

        /* ================== WILAYAH INDONESIA ================== */
        function titleCase(s) {
            return s.toLowerCase().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        }
        function formatRegionName(name) {
            let t = titleCase(name);
            return t.replace(/^Kabupaten\s+/i, 'Kab. ');
        }
        async function loadProvincesOnce() {
            if (provincesLoaded) return;
            try {
                const res = await fetch(`${EMSIFA}/provinces.json`);
                const data = await res.json();
                data.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = formatRegionName(p.name);
                    provinsiSelect.appendChild(opt);
                });
                provincesLoaded = true;
            } catch (e) {
                console.error('Load provinces failed', e);
            }
        }
        async function loadRegencies(provinceId) {
            kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten...</option>';
            kotaSelect.disabled = true;
            if (!provinceId) return;
            try {
                let regencies = regencyCache.get(provinceId);
                if (!regencies) {
                    const res = await fetch(`${EMSIFA}/regencies/${provinceId}.json`);
                    regencies = await res.json();
                    regencyCache.set(provinceId, regencies);
                }
                regencies.forEach(k => {
                    const opt = document.createElement('option');
                    opt.value = formatRegionName(k.name);
                    opt.textContent = formatRegionName(k.name);
                    kotaSelect.appendChild(opt);
                });
                kotaSelect.disabled = false;
            } catch (e) {
                console.error('Load regencies failed', e);
            }
        }
        provinsiSelect.addEventListener('change', (e) => {
            loadRegencies(e.target.value);
        });

        /* ================== SLIDE FORM ================== */
        function openSlide() {
            formSlideMitra.classList.remove('hidden');
            requestAnimationFrame(() => {
                formPanelMitra.classList.remove('translate-x-full');
                formPanelMitra.classList.add('translate-x-0');
            });
            loadProvincesOnce();
        }
        function closeSlide() {
            formPanelMitra.classList.remove('translate-x-0');
            formPanelMitra.classList.add('translate-x-full');
            setTimeout(() => formSlideMitra.classList.add('hidden'), 400);
        }
        function resetForm() {
            formMitra.reset();
            mitraMethodField.value = '';
            mitraEditId.value = '';
            kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten...</option>';
            kotaSelect.disabled = true;
            provinsiSelect.value = '';
        }
        function setupAdd() {
            resetForm();
            mitraFormTitle.textContent = 'Tambah Mitra';
            formMitra.dataset.mode = 'add';
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
                    mitraFormTitle.textContent = 'Edit Mitra';
                    formMitra.dataset.mode = 'edit';
                    mitraMethodField.value = 'PUT';
                    mitraEditId.value = id;

                    f_nama_mitra.value = d.nama_mitra ?? '';
                    f_alamat.value = d.alamat ?? '';

                    // Set provinsi & kota
                    if (d.provinsi) {
                        // Cari provinsi by name, set value by id
                        Array.from(provinsiSelect.options).forEach(opt => {
                            if (opt.textContent === d.provinsi) {
                                provinsiSelect.value = opt.value;
                                loadRegencies(opt.value).then(() => {
                                    kotaSelect.value = d.kota ?? '';
                                });
                            }
                        });
                    }

                    openSlide();
                })
                .catch(e => {
                    toastSafe({ type: 'error', title: 'Error', message: e.message });
                });
        }

        /* ================== SUBMIT FORM (AJAX) ================== */
        formMitra.addEventListener('submit', function (e) {
            e.preventDefault();
            const mode = formMitra.dataset.mode || 'add';
            const fd = new FormData(formMitra);

            // Provinsi: simpan nama (bukan ID)
            const provText = provinsiSelect.value ?
                provinsiSelect.options[provinsiSelect.selectedIndex].text :
                '';
            fd.set('provinsi', provText);

            if (mode === 'edit') {
                fd.append('_method', 'PUT');
            }

            const submitBtn = formMitra.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

            const url = mode === 'add' ? ROUTES.store : `${ROUTES.base}/${mitraEditId.value}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            })
                .then(r => {
                    // Handle both success (200) and validation error (422)
                    return r.json().then(data => ({ status: r.status, data }));
                })
                .then(({ status, data }) => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');

                    if (status === 422) {
                        // Validation error - tampilkan pesan tapi jangan tutup modal
                        toastSafe(data.notify ?? {
                            type: 'error',
                            title: 'Error',
                            message: 'Data duplikat atau tidak valid'
                        });
                        return; // Jangan tutup modal & jangan reload
                    }

                    if (data.success) {
                        closeSlide();
                        performFilter();
                        notyf.success(data.message || 'Mitra berhasil disimpan');
                        toastSafe(data.notify ?? {
                            type: 'success',
                            title: 'Sukses',
                            message: mode === 'add' ? 'Mitra ditambahkan' : 'Mitra diperbarui'
                        });
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    toastSafe({
                        type: 'error',
                        title: 'Error',
                        message: 'Gagal simpan data'
                    });
                });
        });

        /* ================== ERROR HANDLING HELPERS ================== */
        function clearAllErrors() {
            // Clear error texts
            document.querySelectorAll('.error-text').forEach(el => {
                el.classList.remove('show');
                el.classList.add('hidden');
                el.textContent = '';
            });

            // Clear error input styling
            document.querySelectorAll('.input-error').forEach(el => {
                el.classList.remove('input-error');
            });
        }

        function showFieldError(fieldName, message) {
            const errorEl = document.getElementById(`error_${fieldName}`);
            const inputEl = document.getElementById(`f_${fieldName}`) ||
                document.getElementById(`${fieldName}Select`);

            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
                errorEl.classList.add('show');
            }

            if (inputEl) {
                inputEl.classList.add('input-error');

                // Auto clear error saat user mulai edit
                const clearError = () => {
                    errorEl?.classList.remove('show');
                    errorEl?.classList.add('hidden');
                    inputEl.classList.remove('input-error');
                    inputEl.removeEventListener('input', clearError);
                    inputEl.removeEventListener('change', clearError);
                };

                inputEl.addEventListener('input', clearError);
                inputEl.addEventListener('change', clearError);
            }
        }

        function showValidationErrors(errors) {
            Object.keys(errors).forEach(field => {
                const messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                showFieldError(field, messages[0]); // tampilkan error pertama
            });
        }

        /* ================== SUBMIT FORM (UPDATED WITH FIELD ERRORS) ================== */
        formMitra.addEventListener('submit', function (e) {
            e.preventDefault();
            clearAllErrors(); // Clear previous errors

            const mode = formMitra.dataset.mode || 'add';
            const fd = new FormData(formMitra);

            // Provinsi: simpan nama (bukan ID)
            const provText = provinsiSelect.value ?
                provinsiSelect.options[provinsiSelect.selectedIndex].text :
                '';
            fd.set('provinsi', provText);

            if (mode === 'edit') {
                fd.append('_method', 'PUT');
            }

            const submitBtn = formMitra.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

            const url = mode === 'add' ? ROUTES.store : `${ROUTES.base}/${mitraEditId.value}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            })
                .then(r => {
                    return r.json().then(data => ({ status: r.status, data }));
                })
                .then(({ status, data }) => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');

                    if (status === 422) {
                        // Validation error - show field errors
                        if (data.errors) {
                            showValidationErrors(data.errors);
                        }

                        // Show toast notification
                        toastSafe(data.notify ?? {
                            type: 'error',
                            title: 'Error',
                            message: 'Periksa data yang diinputkan'
                        });
                        return; // Jangan tutup modal
                    }

                    if (data.success) {
                        clearAllErrors();
                        closeSlide();
                        performFilter();
                        toastSafe(data.notify ?? {
                            type: 'success',
                            title: 'Sukses',
                            message: mode === 'add' ? 'Mitra ditambahkan' : 'Mitra diperbarui'
                        });
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                    toastSafe({
                        type: 'error',
                        title: 'Error',
                        message: 'Gagal simpan data'
                    });
                });
        });

        /* ================== CLEAR ERRORS WHEN OPENING FORM ================== */
        function setupAdd() {
            resetForm();
            clearAllErrors(); // Clear errors saat buka form add
            mitraFormTitle.textContent = 'Tambah Mitra';
            formMitra.dataset.mode = 'add';
            openSlide();
        }

        function setupEdit(id) {
            clearAllErrors(); // Clear errors saat buka form edit
            fetch(`${ROUTES.base}/${id}/edit`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => {
                    if (!r.ok) throw new Error('Gagal load data');
                    return r.json();
                })
                .then(d => {
                    mitraFormTitle.textContent = 'Edit Mitra';
                    formMitra.dataset.mode = 'edit';
                    mitraMethodField.value = 'PUT';
                    mitraEditId.value = id;

                    f_nama_mitra.value = d.nama_mitra ?? '';
                    f_alamat.value = d.alamat ?? '';

                    // Set provinsi & kota
                    if (d.provinsi) {
                        Array.from(provinsiSelect.options).forEach(opt => {
                            if (opt.textContent === d.provinsi) {
                                provinsiSelect.value = opt.value;
                                loadRegencies(opt.value).then(() => {
                                    kotaSelect.value = d.kota ?? '';
                                });
                            }
                        });
                    }

                    openSlide();
                })
                .catch(e => {
                    toastSafe({ type: 'error', title: 'Error', message: e.message });
                });
        }

        /* ================== HAPUS ================== */
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
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ _method: 'DELETE' })
            })
                .then(r => r.json().catch(() => ({})))
                .then(res => {
                    notyf.success(res.message ?? 'Mitra berhasil dihapus');
                    closeConfirmDelete();
                    performFilter();
                    toastSafe(res.notify ?? { type: 'success', title: 'Berhasil', message: 'Mitra dihapus' });
                })
                .catch(() => {
                    closeConfirmDelete();
                    performFilter();
                    toastSafe({ type: 'error', title: 'Error', message: 'Gagal hapus' });
                });
        });

        /* ================== EVENT DELEGATION ================== */
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

        /* ================== FILTER / SORT / PAGINATION ================== */
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
            fetchList(params);
        }

        /* ================== FILTER INPUT LISTENERS ================== */
        document.querySelectorAll('#filterForm .filter-input').forEach(el => {
            el.addEventListener('input', () => {
                clearTimeout(filterTimeoutId);
                filterTimeoutId = setTimeout(() => performFilter(), 700);
            });
        });
        document.getElementById('resetFilter').addEventListener('click', () => {
            document.getElementById('filterForm').reset();
            performFilter();
        });

        /* ================== INIT ================== */
        btnTambahMitra.addEventListener('click', setupAdd);
        closeFormMitra.addEventListener('click', closeSlide);
        formSlideMitra.addEventListener('click', e => {
            if (e.target === formSlideMitra) closeSlide();
        });

        document.addEventListener('DOMContentLoaded', () => {
            attachPaginationListeners();
            attachSortListeners();
        });
    </script>
@endpush