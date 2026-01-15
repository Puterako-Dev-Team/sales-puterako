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
            <h1 class="text-xl font-bold">Persetujuan Rekap</h1>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <form id="filterFormApproveRekap">
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
                        <label for="pic_admin" class="text-xs font-semibold mb-1">Dibuat Oleh</label>
                        <select id="pic_admin" name="pic_admin"
                            class="filter-input border rounded px-3 py-2 text-sm focus:ring focus:ring-green-200">
                            <option value="">Semua</option>
                            @foreach ($picAdmins ?? [] as $pic)
                                <option value="{{ $pic }}" {{ request('pic_admin') == $pic ? 'selected' : '' }}>
                                    {{ $pic }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col" style="flex: 0 0 auto;">
                        <label class="text-xs opacity-0 mb-1">&nbsp;</label>
                        <button type="button" id="resetFilterApproveRekap"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition flex items-center gap-2 text-sm">
                            <x-lucide-refresh-cw class="w-4 h-4" />
                            Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg loading-overlay" id="tableContainer" style="position:relative;">
            <div class="loading-spinner" id="approveRekapLoadingSpinner" style="display:none;">
                <svg class="animate-spin h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div id="tableContent">
                @include('rekap.approve-table', ['rekaps' => $rekaps])
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Approve -->
    <div id="confirmApproveModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow p-6 w-full max-w-sm mx-4">
            <h3 class="text-lg font-semibold mb-2 flex items-center gap-2">
                <x-lucide-check-circle class="w-5 h-5 text-green-500" />
                Setujui Rekap?
            </h3>
            <p class="text-sm text-gray-600 mb-4">Anda yakin ingin menyetujui rekap <strong id="approveRekapName"></strong>?</p>
            <div class="flex justify-end gap-3">
                <button id="btnCancelApprove" class="px-4 py-2 border rounded text-sm hover:bg-gray-50">Batal</button>
                <button id="btnConfirmApprove" class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">Setujui</button>
            </div>
        </div>
    </div>


    <script>
        const filterInputs = document.querySelectorAll('.filter-input');
        const filterForm = document.getElementById('filterFormApproveRekap');
        const resetBtn = document.getElementById('resetFilterApproveRekap');
        const tableContainer = document.getElementById('tableContainer');
        const loadingSpinner = document.getElementById('approveRekapLoadingSpinner');
        const confirmApproveModal = document.getElementById('confirmApproveModal');
        const confirmRejectModal = document.getElementById('confirmRejectModal');
        const btnCancelApprove = document.getElementById('btnCancelApprove');
        const confirmApproveModal = document.getElementById('confirmApproveModal');
        const btnCancelApprove = document.getElementById('btnCancelApprove');
        const btnConfirmApprove = document.getElementById('btnConfirmApprove');
        const approveRekapName = document.getElementById('approveRekapName');

        let approveTargetId = null;

        function showLoading() {
            tableContainer.classList.add('loading');
            loadingSpinner.style.display = 'block';
        }

        function hideLoading() {
            tableContainer.classList.remove('loading');
            loadingSpinner.style.display = 'none';
        }

        async function fetchTable(params = {}) {
            showLoading();
            try {
                const queryString = new URLSearchParams(params).toString();
                const response = await fetch(`{{ route('rekap.approve-list') }}?${queryString}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await response.text();
                document.getElementById('tableContent').innerHTML = html;
                hideLoading();
            } catch (error) {
                console.error(error);
                hideLoading();
            }
        }

        // Filter handler
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                const params = Object.fromEntries(new FormData(filterForm));
                fetchTable(params);
            });
        });

        filterInputs.forEach(input => {
            if (input.type === 'text') {
                input.addEventListener('keyup', () => {
                    const params = Object.fromEntries(new FormData(filterForm));
                    fetchTable(params);
                });
            }
        });

        // Reset filter
        resetBtn.addEventListener('click', () => {
            filterForm.reset();
            fetchTable();
        });

        // Approve button handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-approve-rekap')) {
                const btn = e.target.closest('.btn-approve-rekap');
                const id = btn.dataset.id;
                const row = btn.closest('tr');
                const namaRekap = row.querySelector('td:nth-child(3)').textContent;
                
                approveTargetId = id;
                approveRekapName.textContent = namaRekap;
                confirmApproveModal.classList.remove('hidden');
                confirmApproveModal.classList.add('flex');
            }
        });


        // Close modals
        btnCancelApprove.addEventListener('click', () => {
            confirmApproveModal.classList.add('hidden');
            confirmApproveModal.classList.remove('flex');
            approveTargetId = null;
        });



        // Confirm approve
        btnConfirmApprove.addEventListener('click', () => {
            if (!approveTargetId) return;

            fetch(`{{ url('/rekap') }}/${approveTargetId}/approve`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                confirmApproveModal.classList.add('hidden');
                confirmApproveModal.classList.remove('flex');
                if (window.toast) {
                    window.toast({
                        type: 'success',
                        title: 'Sukses',
                        message: data.message || 'Rekap berhasil disetujui'
                    });
                }
                // Reload table
                const params = Object.fromEntries(new FormData(filterForm));
                fetchTable(params);
                approveTargetId = null;
            })
            .catch(err => {
                console.error(err);
                if (window.toast) {
                    window.toast({
                        type: 'error',
                        title: 'Error',
                        message: 'Gagal menyetujui rekap'
                    });
                }
            });
        });


        // Close modals when clicking outside
        confirmApproveModal.addEventListener('click', function(e) {
            if (e.target === confirmApproveModal) {
                confirmApproveModal.classList.add('hidden');
                confirmApproveModal.classList.remove('flex');
                approveTargetId = null;
            }
        });


    </script>
@endsection
