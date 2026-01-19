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
                <h1 class="text-2xl font-bold text-gray-900">Kelola Satuan</h1>
                <p class="text-gray-600 mt-1">Manage satuan data</p>
            </div>
            <button id="btnTambah" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Satuan
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterForm">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="q">Cari Satuan</label>
                        <input type="text" id="q" name="q" class="filter-input" placeholder="Nama satuan..." value="{{ request('q') }}">
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
                @include('satuan.table-content', ['satuans' => $satuans])
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContent" class="mt-6">
            @include('components.paginator', [
                'paginator' => $satuans->withPath(route('satuan.filter'))
            ])
        </div>
    </div>

    <!-- Slide-over Form -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full" id="formPanel">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="formTitle" class="text-lg font-semibold text-gray-900">Tambah Satuan</h2>
                    <button id="closeForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="satuanForm" class="flex-1 flex flex-col">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="">
                    <input type="hidden" id="editId" name="id" value="">

                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Satuan</label>
                            <input type="text" id="f_nama" name="nama" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Masukkan nama satuan">
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

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
            <p class="text-gray-600 mb-6">Yakin ingin menghapus satuan ini?</p>
            <div class="flex gap-3">
                <button id="btnCancelDelete" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded transition-colors">
                    Batal
                </button>
                <button id="btnConfirmDelete" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded transition-colors">
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
    store: "{{ route('satuan.store') }}",
    filter: "{{ route('satuan.filter') }}",
    base: "{{ url('satuan') }}"
};

/* ================== ELEMEN FORM / MODAL ================== */
const btnTambah = document.getElementById('btnTambah');
const formSlide = document.getElementById('formSlide');
const formPanel = document.getElementById('formPanel');
const closeFormBtn = document.getElementById('closeForm');
const satuanForm = document.getElementById('satuanForm');
const methodField = document.getElementById('methodField');
const editIdField = document.getElementById('editId');
const formTitle = document.getElementById('formTitle');

const f_nama = document.getElementById('f_nama');

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
function showLoading(){ tableContainer.classList.add('loading'); }
function hideLoading(){ tableContainer.classList.remove('loading'); }

/* ================== SLIDE FORM ================== */
function openSlide(){
    formSlide.classList.remove('hidden');
    requestAnimationFrame(()=>{
        formPanel.classList.remove('translate-x-full');
        formPanel.classList.add('translate-x-0');
    });
}
function closeSlide(){
    formPanel.classList.remove('translate-x-0');
    formPanel.classList.add('translate-x-full');
    setTimeout(()=>formSlide.classList.add('hidden'), 350);
}

function resetForm(){
    satuanForm.reset();
    methodField.value = '';
    editIdField.value = '';
}

function setupAdd(){
    resetForm();
    formTitle.textContent = 'Tambah Satuan';
    satuanForm.dataset.mode = 'add';
    satuanForm.action = ROUTES.store;
    openSlide();
}

function setupEdit(id){
    fetch(`${ROUTES.base}/${id}/edit`, {
        headers:{'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => {
        if(!r.ok) throw new Error('Gagal load data');
        return r.json();
    })
    .then(d => {
        formTitle.textContent = 'Edit Satuan';
        satuanForm.dataset.mode = 'edit';
        satuanForm.action = `${ROUTES.base}/${id}`;
        methodField.value = 'PUT';
        editIdField.value = id;

        f_nama.value = d.nama || '';

        openSlide();
    })
    .catch(e=>{
        window.notyf.error('Gagal memuat data satuan untuk diedit');
    });
}

/* ================== SUBMIT FORM (AJAX) ================== */
satuanForm.addEventListener('submit', function(e){
    e.preventDefault();
    const mode = satuanForm.dataset.mode || 'add';

    const fd = new FormData(satuanForm);
    if(mode === 'edit'){
        fd.append('_method', 'PUT');
    }

    const submitBtn = satuanForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70','cursor-not-allowed');

    fetch(satuanForm.action, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
    .then(r => r.json().catch(()=>({})))
    .then(res => {
        if(res.success){
            window.notyf.success(res.message || 'Satuan berhasil disimpan');
            closeSlide();
            performFilter();
        } else {
            window.notyf.error(res.message || 'Gagal menyimpan satuan');
        }
    })
    .catch(err=>{
        window.notyf.error('Gagal menyimpan satuan');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-70','cursor-not-allowed');
    });
});

/* ================== HAPUS SATUAN ================== */
function openConfirmDelete(id){
    deleteTargetId = id;
    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
}
function closeConfirmDelete(){
    confirmModal.classList.add('hidden');
    confirmModal.classList.remove('flex');
    deleteTargetId = null;
}

btnCancelDelete.addEventListener('click', closeConfirmDelete);
btnConfirmDelete.addEventListener('click', ()=>{
    if(!deleteTargetId) return;

    fetch(`${ROUTES.base}/${deleteTargetId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({_method:'DELETE'})
    })
    .then(r=>r.json().catch(()=>({})))
    .then(res=>{
        if(res.success){
            window.notyf.success(res.message || 'Satuan berhasil dihapus');
            closeConfirmDelete();
            performFilter();
        } else {
            window.notyf.error(res.message || 'Gagal menghapus satuan');
        }
    })
    .catch(()=>{
        window.notyf.error('Gagal menghapus satuan');
    });
});

/* ================== EVENT DELEGATION ================== */
document.addEventListener('click', e=>{
    const editBtn = e.target.closest('.btn-edit');
    if(editBtn){
        const id = editBtn.dataset.id;
        setupEdit(id);
    }

    const delBtn = e.target.closest('.btn-delete');
    if(delBtn){
        const id = delBtn.dataset.id;
        openConfirmDelete(id);
    }
});

/* ================== FILTER FUNCTIONS ================== */
function performFilter(){
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);

    showLoading();
    fetch(`${ROUTES.filter}?${params.toString()}`, {
        headers:{'X-Requested-With':'XMLHttpRequest'}
    })
    .then(r=>r.json())
    .then(data=>{
        tableContent.innerHTML = data.table;
        paginationWrap.innerHTML = data.pagination;
        resultsInfo.innerHTML = data.info || '';
        attachPaginationListeners();
    })
    .catch(e=>{
        console.error('Filter error:', e);
    })
    .finally(()=>{
        hideLoading();
    });
}

function attachPaginationListeners(){
    document.querySelectorAll('.pagination-link').forEach(a=>{
        a.addEventListener('click', e=>{
            e.preventDefault();
            const url = new URL(a.href);

            showLoading();
            fetch(url.toString(), {
                headers:{'X-Requested-With':'XMLHttpRequest'}
            })
            .then(r=>r.json())
            .then(data=>{
                tableContent.innerHTML = data.table;
                paginationWrap.innerHTML = data.pagination;
                resultsInfo.innerHTML = data.info || '';
                attachPaginationListeners();
            })
            .finally(()=>hideLoading());
        });
    });
}

/* ================== FILTER INPUT LISTENERS ================== */
document.querySelectorAll('#filterForm .filter-input').forEach(el=>{
    if(el.type === 'text'){
        el.addEventListener('input', ()=>{
            clearTimeout(filterTimeoutId);
            filterTimeoutId = setTimeout(performFilter, 500);
        });
    } else {
        el.addEventListener('change', performFilter);
    }
});

document.getElementById('resetFilter').addEventListener('click', ()=>{
    document.getElementById('filterForm').reset();
    performFilter();
});

/* ================== OPEN / CLOSE FORM BUTTONS ================== */
btnTambah.addEventListener('click', setupAdd);
closeFormBtn.addEventListener('click', closeSlide);

/* ================== INIT ================== */
document.addEventListener('DOMContentLoaded', ()=>{
    attachPaginationListeners();

    // Set initial results info
    const totalSatuans = {{ $satuans->total() }};
    if(totalSatuans > 0){
        resultsInfo.innerHTML = "Menampilkan {{ $satuans->firstItem() }} sampai {{ $satuans->lastItem() }} dari {{ $satuans->total() }} data";
    }
});
</script>
@endpush
