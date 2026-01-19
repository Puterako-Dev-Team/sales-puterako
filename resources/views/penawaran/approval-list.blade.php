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

        .approval-table {
            border-collapse: collapse;
            table-layout: fixed;
        }

        .approval-table th,
        .approval-table td {
            padding: 0.75rem 0.75rem;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
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
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold">Approve List</h1>
        </div>
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
            @include('penawaran.approval-table', [
                'requests' => $requests,
                'userRole' => $userRole,
            ])
        </div>
    </div>

    <!-- Pagination -->
    <div id="paginationContent" class="mt-6">
        @include('components.paginator', ['paginator' => $requests])
    </div>

<!-- Modal Konfirmasi Approve -->
<div id="approveModal" class="modal-overlay">
    <div class="modal-card">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Konfirmasi Approve</h3>
        <p class="text-sm text-gray-600 mb-4">Anda yakin ingin menyetujui permintaan ini?</p>
        <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700 mb-4">
            <div><span class="font-semibold">No Penawaran:</span> <span id="modalNoPenawaran">-</span></div>
            <div><span class="font-semibold">Perusahaan:</span> <span id="modalPerusahaan">-</span></div>
            <div><span class="font-semibold">Versi:</span> <span id="modalVersi">-</span></div>
        </div>
        <div class="flex justify-end gap-3">
            <button id="modalCancel" class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-100">Batal</button>
            <button id="modalConfirm" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Approve</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('filterForm');
    const tableContainer = document.getElementById('tableContainer');
    const tableContent = document.getElementById('tableContent');
    const paginationContent = document.getElementById('paginationContent');
    const resultsInfo = document.getElementById('resultsInfo');
    const resetFilterBtn = document.getElementById('resetFilter');
    let pendingAction = null;

    const notyfInstance = window.notyf || new Notyf({
        duration: 2500,
        position: { x: 'right', y: 'top' }
    });

    function getCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    }

    function showLoading() {
        if (tableContainer) tableContainer.classList.add('loading');
    }

    function hideLoading() {
        if (tableContainer) tableContainer.classList.remove('loading');
    }

    function openModal(data) {
        const modal = document.getElementById('approveModal');
        pendingAction = data;
        document.getElementById('modalNoPenawaran').textContent = data.no;
        document.getElementById('modalPerusahaan').textContent = data.company;
        document.getElementById('modalVersi').textContent = data.version;
        modal.classList.add('active');
    }

    function closeModal() {
        const modal = document.getElementById('approveModal');
        modal.classList.remove('active');
        pendingAction = null;
    }

    function attachSortListeners() {
        document.querySelectorAll('.sort-button').forEach(btn => {
            btn.addEventListener('click', function () {
                const column = this.dataset.column;
                const direction = this.dataset.direction;
                const formData = new FormData(filterForm);
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
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                params.set('page', page);

                // keep sort
                const cur = new URLSearchParams(window.location.search);
                if (cur.get('sort')) params.set('sort', cur.get('sort'));
                if (cur.get('direction')) params.set('direction', cur.get('direction'));

                fetchList(params);
            });
        });
    }

    function attachApproveButtons() {
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal({
                    url: btn.dataset.url,
                    button: btn,
                    no: btn.dataset.no,
                    company: btn.dataset.company,
                    version: btn.dataset.version
                });
            });
        });
    }

    function fetchList(params) {
        showLoading();
        fetch(`{{ route('penawaran.approve-list') }}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(r => r.json())
            .then(data => {
                tableContent.innerHTML = data.table;
                paginationContent.innerHTML = data.pagination;
                if (resultsInfo) resultsInfo.innerHTML = data.info || '';
                hideLoading();
                attachSortListeners();
                attachPaginationListeners();
                attachApproveButtons();
            })
            .catch(e => {
                console.error(e);
                hideLoading();
            });
    }

    function performFilter() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        // keep sort
        const cur = new URLSearchParams(window.location.search);
        if (cur.get('sort')) params.set('sort', cur.get('sort'));
        if (cur.get('direction')) params.set('direction', cur.get('direction'));
        fetchList(params);
    }

    // Filter input listeners
    const filterInputs = document.querySelectorAll('.filter-input');
    let filterTimeout;

    filterInputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(performFilter, 500);
        });

        input.addEventListener('change', () => {
            if (input.type !== 'text') {
                performFilter();
            }
        });
    });

    // Reset filter button
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            filterForm.reset();
            performFilter();
        });
    }

    // Modal handlers
    const modal = document.getElementById('approveModal');
    const btnCancel = document.getElementById('modalCancel');
    const btnConfirm = document.getElementById('modalConfirm');

    btnCancel.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    btnConfirm.addEventListener('click', async () => {
        if (!pendingAction) return;
        const { url, button } = pendingAction;

        try {
            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-not-allowed');

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrf(),
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal approve');
            }

            notyfInstance.success(data.message || 'Berhasil di-approve');

            // Reload the list
            performFilter();
        } catch (err) {
            notyfInstance.error(err.message || 'Gagal approve');
            if (pendingAction?.button) {
                pendingAction.button.disabled = false;
                pendingAction.button.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        } finally {
            closeModal();
        }
    });

    // Initial binding of listeners
    attachSortListeners();
    attachPaginationListeners();
    attachApproveButtons();
});
</script>
@endpush
@endsection
