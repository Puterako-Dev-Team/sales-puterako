@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Follow-Up Schedule</h1>
            <p class="text-sm text-gray-600 mt-1">Kelola jadwal follow-up otomatis penawaran</p>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari</label>
                <input type="text" 
                       name="search" 
                       id="searchInput"
                       value="{{ request('search') }}"
                       placeholder="No Penawaran atau Perihal"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" 
                        id="statusFilter"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Aktif</option>
                    <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Berhenti</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    <x-lucide-search class="w-4 h-4 inline mr-1" />
                    Filter
                </button>
                <button type="button" 
                        id="resetFilter"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    <x-lucide-x class="w-4 h-4 inline mr-1" />
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Table Container -->
    <div id="tableContainer" class="bg-white rounded-lg shadow-sm">
        @include('followup.table-content', ['schedules' => $schedules])
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $schedules->links('components.paginator') }}
    </div>
</div>

<!-- Modal Start New Cycle -->
<div id="newCycleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Start New Cycle</h3>
            <form id="newCycleForm">
                <input type="hidden" id="cycleScheduleId" name="schedule_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Mulai</label>
                    <input type="date" 
                           name="start_date" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Interval (Hari)</label>
                    <input type="number" 
                           name="interval_days" 
                           min="1" 
                           max="90" 
                           value="7"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Reminders</label>
                    <input type="number" 
                           name="max_reminders" 
                           min="1" 
                           max="20" 
                           value="5"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="button" 
                            id="closeCycleModal"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        Start Cycle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Config Modal -->
<div id="editConfigModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Edit Konfigurasi Follow-Up</h3>
            <button id="closeEditModal" class="text-gray-400 hover:text-gray-600">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>
        <form id="editConfigForm" class="p-6 space-y-4 -mt-6">
            <input type="hidden" id="editScheduleId" name="schedule_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Interval (hari)</label>
                <input type="number" id="editIntervalDays" name="interval_days" min="1" max="90"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Maksimal Reminder per Cycle</label>
                <input type="number" id="editMaxReminders" name="max_reminders" min="1" max="20"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="flex gap-3 ">
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 ">
                    Update Konfigurasi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pause Modal -->
<div id="pauseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Pause Follow-Up Schedule</h3>
            <button id="closePauseModal" class="text-gray-400 hover:text-gray-600">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>
        <form id="pauseForm" class="p-6">
            <input type="hidden" id="pauseScheduleId" name="schedule_id">
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <x-lucide-alert-triangle class="w-5 h-5 text-red-600 mr-2" />
                    <p class="text-sm text-red-800">Schedule akan dihentikan sementara. Reminder tidak akan dikirim sampai di-resume kembali.</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    <x-lucide-pause class="w-4 h-4 inline mr-1" />
                    Pause Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resume Modal -->
<div id="resumeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Resume Follow-Up Schedule</h3>
            <button id="closeResumeModal" class="text-gray-400 hover:text-gray-600">
                <x-lucide-x class="w-5 h-5" />
            </button>
        </div>
        <form id="resumeForm" class="p-6">
            <input type="hidden" id="resumeScheduleId" name="schedule_id">
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <x-lucide-check-circle class="w-5 h-5 text-green-600 mr-2" />
                    <p class="text-sm text-green-800">Schedule akan dilanjutkan. Reminder akan dikirim sesuai jadwal berikutnya.</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    <x-lucide-play class="w-4 h-4 inline mr-1" />
                    Resume Schedule
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter handling
    const filterForm = document.getElementById('filterForm');
    const resetBtn = document.getElementById('resetFilter');
    
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadTable();
    });

    resetBtn.addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        loadTable();
    });

    // Load table via AJAX
    function loadTable() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        
        fetch(`{{ route('followup.index') }}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('tableContainer').innerHTML = html;
        });
    }

    // Modal handling
    const modal = document.getElementById('newCycleModal');
    const closeModal = document.getElementById('closeCycleModal');
    
    closeModal.addEventListener('click', () => modal.classList.add('hidden'));
    
    // Delegate event for dynamic buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-new-cycle')) {
            const btn = e.target.closest('.btn-new-cycle');
            const penawaranId = btn.dataset.penawaranId;
            document.getElementById('cycleScheduleId').value = penawaranId;
            modal.classList.remove('hidden');
        }
    });

    // Submit new cycle
    document.getElementById('newCycleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const penawaranId = document.getElementById('cycleScheduleId').value;
        
        fetch(`/followup/penawaran/${penawaranId}/start-new-cycle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            window.notyf.success(data.message);
            modal.classList.add('hidden');
            loadTable();
        })
        .catch(error => {
            window.notyf.error('Gagal memulai cycle baru');
        });
    });

    // Edit Config Modal
    const editModal = document.getElementById('editConfigModal');
    const closeEditModal = document.getElementById('closeEditModal');

    closeEditModal.addEventListener('click', () => editModal.classList.add('hidden'));

    // Handle edit button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit')) {
            const btn = e.target.closest('.btn-edit');
            const penawaranId = btn.dataset.penawaranId;
            const interval = btn.dataset.interval;
            const maxReminders = btn.dataset.maxReminders;
            const notes = btn.dataset.notes;
            
            document.getElementById('editScheduleId').value = penawaranId;
            document.getElementById('editIntervalDays').value = interval;
            document.getElementById('editMaxReminders').value = maxReminders;
            
            editModal.classList.remove('hidden');
        }
    });

    // Submit edit config
    document.getElementById('editConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const penawaranId = document.getElementById('editScheduleId').value;
        
        fetch(`{{ url('followup/penawaran') }}/${penawaranId}/update-config`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            window.notyf.success(data.message);
            editModal.classList.add('hidden');
            loadTable();
        })
        .catch(error => {
            window.notyf.error('Gagal mengupdate konfigurasi');
        });
    });

    // Pause Modal
    const pauseModal = document.getElementById('pauseModal');
    const closePauseModal = document.getElementById('closePauseModal');

    closePauseModal.addEventListener('click', () => pauseModal.classList.add('hidden'));

    // Resume Modal
    const resumeModal = document.getElementById('resumeModal');
    const closeResumeModal = document.getElementById('closeResumeModal');

    closeResumeModal.addEventListener('click', () => resumeModal.classList.add('hidden'));

    // Handle pause button
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-pause')) {
            const btn = e.target.closest('.btn-pause');
            const penawaranId = btn.dataset.penawaranId;
            
            document.getElementById('pauseScheduleId').value = penawaranId;
            pauseModal.classList.remove('hidden');
        }
    });

    // Handle resume button
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-resume')) {
            const btn = e.target.closest('.btn-resume');
            const penawaranId = btn.dataset.penawaranId;
            
            document.getElementById('resumeScheduleId').value = penawaranId;
            resumeModal.classList.remove('hidden');
        }
    });

    // Submit pause
    document.getElementById('pauseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const penawaranId = document.getElementById('pauseScheduleId').value;
        
        fetch(`{{ url('followup/penawaran') }}/${penawaranId}/pause`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            window.notyf.success(data.message);
            pauseModal.classList.add('hidden');
            loadTable();
        })
        .catch(error => {
            window.notyf.error('Gagal menghentikan schedule');
        });
    });

    // Submit resume
    document.getElementById('resumeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const penawaranId = document.getElementById('resumeScheduleId').value;
        
        fetch(`{{ url('followup/penawaran') }}/${penawaranId}/resume`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            window.notyf.success(data.message);
            resumeModal.classList.add('hidden');
            loadTable();
        })
        .catch(error => {
            window.notyf.error('Gagal melanjutkan schedule');
        });
    });
});
</script>
@endpush
@endsection