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
                <h1 class="text-2xl font-bold text-gray-900">Kelola Users</h1>
                <p class="text-gray-600 mt-1">Manage users and their access levels</p>
            </div>
            <button id="btnTambah" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah User
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterForm">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="search">Cari User</label>
                        <input type="text" id="search" name="search" class="filter-input" placeholder="Nama, email, atau departemen..." value="{{ request('search') }}">
                    </div>
                    <div class="filter-item">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="filter-input">
                            <option value="">Semua Role</option>
                            <option value="administrator" {{ request('role') == 'administrator' ? 'selected' : '' }}>Administrator</option>
                            <option value="direktur" {{ request('role') == 'direktur' ? 'selected' : '' }}>Direktur</option>
                            <option value="manager" {{ request('role') == 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="supervisor" {{ request('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="kantor">Kantor</label>
                        <select id="kantor" name="kantor" class="filter-input">
                            <option value="">Semua Kantor</option>
                            <option value="Jakarta" {{ request('kantor') == 'Jakarta' ? 'selected' : '' }}>Jakarta</option>
                            <option value="Surabaya" {{ request('kantor') == 'Surabaya' ? 'selected' : '' }}>Surabaya</option>
                        </select>
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
                @include('users.table-content', ['users' => $users])
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContent" class="mt-6">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Slide-over Form -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full" id="formPanel">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 id="formTitle" class="text-lg font-semibold text-gray-900">Tambah User</h2>
                    <button id="closeForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form id="userForm" class="flex-1 flex flex-col">
                    @csrf
                    <input type="hidden" id="methodField" name="_method" value="">
                    <input type="hidden" id="editId" name="id" value="">

                    <div class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                            <input type="text" id="f_name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="f_email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" id="f_password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <small class="text-gray-500 text-xs mt-1">Kosongkan jika tidak ingin mengubah password</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                            <input type="password" id="f_password_confirmation" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <select id="f_role" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Role</option>
                                <option value="administrator">Administrator</option>
                                <option value="direktur">Direktur</option>
                                <option value="manager">Manager</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departemen</label>
                            <select id="f_departemen" name="departemen" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Departemen</option>
                                @foreach(\App\Enums\Department::cases() as $dept)
                                    <option value="{{ $dept->value }}">{{ $dept->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kantor</label>
                            <select id="f_kantor" name="kantor" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Kantor</option>
                                <option value="Jakarta">Jakarta</option>
                                <option value="Surabaya">Surabaya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">No. HP</label>
                            <input type="text" id="f_nohp" name="nohp" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
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
            <p class="text-gray-600 mb-6">Yakin ingin menghapus user ini?</p>
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
    store: "{{ route('users.store') }}",
    filter: "{{ route('users.filter') }}",
    base: "{{ url('users') }}"
};

/* ================== ELEMEN FORM / MODAL ================== */
const btnTambah = document.getElementById('btnTambah');
const formSlide = document.getElementById('formSlide');
const formPanel = document.getElementById('formPanel');
const closeFormBtn = document.getElementById('closeForm');
const userForm = document.getElementById('userForm');
const methodField = document.getElementById('methodField');
const editIdField = document.getElementById('editId');
const formTitle = document.getElementById('formTitle');

const f_name = document.getElementById('f_name');
const f_email = document.getElementById('f_email');
const f_password = document.getElementById('f_password');
const f_password_confirmation = document.getElementById('f_password_confirmation');
const f_role = document.getElementById('f_role');
const f_departemen = document.getElementById('f_departemen');
const f_kantor = document.getElementById('f_kantor');
const f_nohp = document.getElementById('f_nohp');

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
function toastSafe(payload){
    if (window.toast) toast(payload); else console.log(payload);
}
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
    userForm.reset();
    methodField.value = '';
    editIdField.value = '';
}

function setupAdd(){
    resetForm();
    formTitle.textContent = 'Tambah User';
    userForm.dataset.mode = 'add';
    userForm.action = ROUTES.store;
    f_password.required = true;
    f_password_confirmation.required = true;
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
        formTitle.textContent = 'Edit User';
        userForm.dataset.mode = 'edit';
        userForm.action = `${ROUTES.base}/${id}`;
        methodField.value = 'PUT';
        editIdField.value = id;

        f_name.value = d.name || '';
        f_email.value = d.email || '';
        f_role.value = d.role || '';
        f_departemen.value = d.departemen || '';
        f_kantor.value = d.kantor || '';
        f_nohp.value = d.nohp || '';
        
        f_password.required = false;
        f_password_confirmation.required = false;
        
        openSlide();
    })
    .catch(e=>{
        toastSafe({type:'error',title:'Error',message:e.message});
    });
}

/* ================== SUBMIT FORM (AJAX) ================== */
userForm.addEventListener('submit', function(e){
    e.preventDefault();
    const mode = userForm.dataset.mode || 'add';

    const fd = new FormData(userForm);
    if(mode === 'edit'){
        fd.append('_method', 'PUT');
    }

    const submitBtn = userForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70','cursor-not-allowed');

    fetch(userForm.action, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
    .then(r => r.json().catch(()=>({})))
    .then(res => {
        if(res.notify){
            toastSafe(res.notify);
            if(res.notify.type === 'success'){
                closeSlide();
                performFilter();
            }
        }
    })
    .catch(err=>{
        toastSafe({type:'error',title:'Error',message:'Gagal simpan data'});
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-70','cursor-not-allowed');
    });
});

/* ================== HAPUS USER ================== */
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
        toastSafe(res.notify ?? {type:'success',title:'Berhasil',message:'User dihapus'});
        closeConfirmDelete();
        performFilter();
    })
    .catch(()=>{
        toastSafe({type:'error',title:'Error',message:'Gagal hapus'});
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
        tableContent.innerHTML = data.html;
        paginationWrap.innerHTML = data.pagination;
        resultsInfo.innerHTML = data.info;
        attachPaginationListeners();
        attachSortListeners();
    })
    .catch(e=>{
        console.error('Filter error:', e);
    })
    .finally(()=>{
        hideLoading();
    });
}

function attachSortListeners(){
    document.querySelectorAll('.sort-button').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const column = btn.dataset.column;
            const direction = btn.dataset.direction;
            
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams(formData);
            params.set('sort', column);
            params.set('direction', direction);
            
            fetch(`${ROUTES.filter}?${params.toString()}`, {
                headers:{'X-Requested-With':'XMLHttpRequest'}
            })
            .then(r=>r.json())
            .then(data=>{
                tableContent.innerHTML = data.html;
                paginationWrap.innerHTML = data.pagination;
                resultsInfo.innerHTML = data.info;
                attachPaginationListeners();
                attachSortListeners();
            })
            .catch(console.error);
        });
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
                tableContent.innerHTML = data.html;
                paginationWrap.innerHTML = data.pagination;
                resultsInfo.innerHTML = data.info;
                attachPaginationListeners();
                attachSortListeners();
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
document.getElementById('cancelBtn').addEventListener('click', closeSlide);
formSlide.addEventListener('click', e=>{
    if(e.target === formSlide) closeSlide();
});

/* ================== INIT ================== */
document.addEventListener('DOMContentLoaded', ()=>{
    attachPaginationListeners();
    attachSortListeners();
    
    // Set initial results info
    const totalUsers = {{ $users->total() }};
    if(totalUsers > 0){
        resultsInfo.innerHTML = "Menampilkan {{ $users->firstItem() }} sampai {{ $users->lastItem() }} dari {{ $users->total() }} data";
    }
});
</script>
@endpush