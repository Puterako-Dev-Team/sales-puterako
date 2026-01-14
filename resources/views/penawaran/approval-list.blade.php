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
                        <input type="date" id="tanggal_dari" name="tanggal_dari" value=""
                            class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 200px;">
                        <label for="no_penawaran">No Penawaran</label>
                        <input type="text" id="no_penawaran" name="no_penawaran" placeholder="Cari no penawaran..."
                            value="" class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 1 1 250px;">
                        <label for="nama_perusahaan">Nama Perusahaan</label>
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" placeholder="Cari nama perusahaan..."
                            value="" class="filter-input">
                    </div>

                    <div class="filter-item" style="flex: 0 0 180px;">
                        <label for="pic_admin">PIC Admin</label>
                        <select id="pic_admin" name="pic_admin" class="filter-input">
                            <option value="">Semua PIC</option>
                           
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

    <div>
        @if($requests->isEmpty())
            <p class="text-gray-600">Tidak ada permintaan verifikasi untuk disetujui.</p>
        @else
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
                        @foreach($requests as $index => $req)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
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
    const approveButtons = document.querySelectorAll('.approve-btn');
    const modal = document.getElementById('approveModal');
    const modalNo = document.getElementById('modalNoPenawaran');
    const modalCompany = document.getElementById('modalPerusahaan');
    const modalVersi = document.getElementById('modalVersi');
    const btnCancel = document.getElementById('modalCancel');
    const btnConfirm = document.getElementById('modalConfirm');
    let pendingAction = null;

    const notyfInstance = window.notyf || new Notyf({
        duration: 2500,
        position: { x: 'right', y: 'top' }
    });

    function getCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
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

    approveButtons.forEach(btn => {
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
