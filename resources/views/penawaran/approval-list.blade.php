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

    <!-- Table Container with Loading Overlay -->
    <div class="bg-white shadow rounded-lg loading-overlay" id="tableContainer">
        <div class="loading-spinner">
            <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full text-sm approval-table">
            <colgroup>
                <col style="width: 6%">
                <col style="width: 18%">
                <col style="width: 8%">
                <col style="width: 20%">
                <col style="width: 16%">
                <col style="width: 14%">
                <col style="width: 10%">
                <col style="width: 8%">
            </colgroup>
            <thead>
                <tr class="bg-green-500 text-white">
                    <th class="px-3 py-3 font-semibold text-center rounded-tl-md">No</th>
                    <th class="px-3 py-3 font-semibold text-left">No Penawaran</th>
                    <th class="px-3 py-3 font-semibold text-left">Versi</th>
                    <th class="px-3 py-3 font-semibold text-left">Perusahaan</th>
                    <th class="px-3 py-3 font-semibold text-left">Diminta Oleh</th>
                    <th class="px-3 py-3 font-semibold text-left">Dibuat</th>
                    <th class="px-3 py-3 font-semibold text-left">Status</th>
                    <th class="px-3 py-3 font-semibold text-center rounded-tr-md">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $index => $req)
                        <tr class="border-b transition hover:bg-gray-50 text-gray-800">
                            <td class="px-3 py-3 text-center">{{ $index + 1 }}</td>
                            <td class="px-3 py-3">
                                @if($req->penawaran)
                                    <a href="{{ route('penawaran.show', ['id' => $req->penawaran_id, 'version' => $req->version->version ?? 0]) }}" class="text-green-600 hover:underline">
                                        {{ $req->penawaran->no_penawaran }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $req->version->version ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $req->penawaran->nama_perusahaan ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $req->requestedBy->name ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $req->requested_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full
                                    @if($req->status === 'fully_approved') bg-green-100 text-green-800
                                    @elseif($req->status === 'manager_approved') bg-blue-100 text-blue-800
                                    @elseif($req->status === 'supervisor_approved') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ str_replace('_', ' ', ucfirst($req->status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @php
                                    $canApprove = false;
                                    $approveRoute = null;
                                    if ($userRole === 'supervisor' && !$req->approved_by_supervisor) {
                                        $canApprove = true;
                                        $approveRoute = route('export-approval.approve-supervisor', $req->id);
                                    }
                                        if ($userRole === 'manager' && $req->approved_by_supervisor && !$req->approved_by_manager) {
                                            $canApprove = true;
                                            $approveRoute = route('export-approval.approve-manager', $req->id);
                                        }
                                        if ($userRole === 'direktur' && $req->approved_by_manager && !$req->approved_by_direktur) {
                                            $canApprove = true;
                                            $approveRoute = route('export-approval.approve-direktur', $req->id);
                                        }
                                    @endphp

                                    @if($canApprove)
                                        <button type="button"
                                            class="approve-btn bg-green-500 text-white px-3 py-2 rounded hover:bg-green-600 transition text-xs font-semibold"
                                            data-url="{{ $approveRoute }}"
                                            data-id="{{ $req->id }}"
                                            data-no="{{ $req->penawaran->no_penawaran ?? '-' }}"
                                            data-company="{{ $req->penawaran->nama_perusahaan ?? '-' }}"
                                            data-version="{{ $req->version->version ?? '-' }}">
                                            Approve
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">Tidak ada aksi</span>
                                    @endif
                                </td>
                            </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8">
                            <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                                <x-lucide-search-x class="w-8 h-8" />
                                <span>Belum ada permintaan verifikasi</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <div id="paginationContent" class="mt-6">

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
    const approveButtons = document.querySelectorAll('.approve-btn');
    const modal = document.getElementById('approveModal');
    const modalNo = document.getElementById('modalNoPenawaran');
    const modalCompany = document.getElementById('modalPerusahaan');
    const modalVersi = document.getElementById('modalVersi');
    const btnCancel = document.getElementById('modalCancel');
    const btnConfirm = document.getElementById('modalConfirm');
    const resetFilterBtn = document.getElementById('resetFilter');
    const tableContainer = document.getElementById('tableContainer');
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
        pendingAction = data;
        modalNo.textContent = data.no;
        modalCompany.textContent = data.company;
        modalVersi.textContent = data.version;
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
        pendingAction = null;
    }

    // Filter functionality
    function applyFilters() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                params.append(key, value);
            }
        }

        showLoading();

        fetch(`{{ route('penawaran.approve-list') }}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the HTML and update the table
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newOverflowContainer = doc.querySelector('.overflow-x-auto');

            if (newOverflowContainer && tableContainer) {
                // Find the overflow-x-auto div inside tableContainer and update it
                const currentOverflow = tableContainer.querySelector('.overflow-x-auto');
                if (currentOverflow) {
                    currentOverflow.innerHTML = newOverflowContainer.innerHTML;
                } else {
                    tableContainer.innerHTML = newOverflowContainer.innerHTML;
                }

                // Re-bind approve buttons
                bindApproveButtons();
            }

            // Update URL without page reload
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.pushState({}, '', newUrl);
        })
        .catch(error => {
            console.error('Filter error:', error);
            notyfInstance.error('Gagal memuat data yang difilter');
        })
        .finally(() => {
            hideLoading();
        });
    }

    function bindApproveButtons() {
        const newApproveButtons = document.querySelectorAll('.approve-btn');
        newApproveButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                openModal({
                    url: btn.dataset.url,
                    row: btn.closest('tr'),
                    badge: btn.closest('tr').querySelector('span.inline-block'),
                    button: btn,
                    no: btn.dataset.no,
                    company: btn.dataset.company,
                    version: btn.dataset.version
                });
            });
        });
    }

    // Initial binding of approve buttons
    bindApproveButtons();

    // Filter input listeners
    const filterInputs = document.querySelectorAll('.filter-input');
    let filterTimeout;

    filterInputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(applyFilters, 500);
        });

        input.addEventListener('change', () => {
            if (input.type !== 'text') {
                applyFilters();
            }
        });
    });

    // Reset filter button
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', () => {
            document.getElementById('filterForm').reset();
            applyFilters();
        });
    }

    btnCancel.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    btnConfirm.addEventListener('click', async () => {
        if (!pendingAction) return;
        const { url, row, badge, button } = pendingAction;

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

            if (badge) {
                badge.textContent = 'Approved';
                badge.className = 'inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
            }

            const tbody = row.parentElement;
            tbody.appendChild(row);

            button.textContent = 'Sudah disetujui';
            button.disabled = true;
            button.className = 'bg-gray-300 text-gray-600 px-3 py-2 rounded text-xs font-semibold cursor-not-allowed';
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
});
</script>
@endpush
@endsection
