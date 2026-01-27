@extends('layouts.app')

@section('title', 'Kelola Libur Nasional')

@section('content')
<div class="container px-8 py-8 mx-auto">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">Kelola Libur Nasional</h1>
    </div>

    <div class="p-6 mb-6 bg-white rounded-lg shadow">
        <div class="flex flex-wrap gap-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="filter-item" style="flex: 0 0 180px;">
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" id="month" class="px-4 py-2 border rounded w-full" onchange="this.form.submit()">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (isset($month) ? $month : now()->month) == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="filter-item" style="flex: 0 0 180px;">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="px-4 py-2 border rounded w-full" onchange="this.form.submit()">
                    <option value="nasional" {{ (isset($status) ? $status : 'nasional') == 'nasional' ? 'selected' : '' }}>Libur Nasional</option>
                    <option value="cuti" {{ (isset($status) ? $status : 'nasional') == 'cuti' ? 'selected' : '' }}>Cuti Bersama</option>
                    <option value="all" {{ (isset($status) ? $status : 'nasional') == 'all' ? 'selected' : '' }}>Semua</option>
                </select>
            </div>
            <div class="filter-item" style="flex: 0 0 180px;">
                <label for="yearFilter" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select id="yearFilter" name="year" class="px-4 py-2 border border-gray-300 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="2025" {{ request('year', now()->year) == 2025 ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ request('year', now()->year) == 2026 ? 'selected' : '' }}>2026</option>
                    <option value="2027" {{ request('year', now()->year) == 2027 ? 'selected' : '' }}>2027</option>
                    <option value="2028" {{ request('year', now()->year) == 2028 ? 'selected' : '' }}>2028</option>
                </select>
            </div>
            <!-- Tambah filter lain di sini jika perlu -->
        </form>
        <button onclick="syncHolidays()" class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700 transition-colors h-10 self-end">
            <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Sync
        </button>
    </div>
    </div>

    @include('users.holiday-table', ['holidays' => $holidays])

    <div class="mt-4">
        {{ $holidays->links('components.paginator') }}
    </div>
</div>

<!-- Modal Loading Sync -->
<div id="syncModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex flex-col items-center text-center">
                <svg class="w-12 h-12 animate-spin text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sync Holidays</h3>
                <p id="syncMessage" class="text-gray-600">Mengambil data libur dari API...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Success/Error -->
<div id="resultModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex flex-col items-center text-center">
                <div id="resultIcon" class="mb-4"></div>
                <h3 id="resultTitle" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                <p id="resultMessage" class="text-gray-600 mb-4"></p>
                <button onclick="closeResultModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ubah Status -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Ubah Status Libur</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="editHolidayForm" class="p-6 space-y-4">
            <input type="hidden" id="editHolidayId">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                <input type="text" id="editTanggal" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Libur</label>
                <input type="text" id="editNama" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="editStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="1">Libur Nasional</option>
                    <option value="0">Cuti Bersama/Hari Kerja</option>
                </select>
                <p class="mt-2 text-xs text-gray-500">
                    <strong>Libur Nasional:</strong> Sales tidak dapat login<br>
                    <strong>Cuti Bersama/Hari Kerja:</strong> Sales dapat login (jam 08:00 - 17:00)
                </p>
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>



@endsection

@push('scripts')
<script>
// Load holidays on page load
$(document).ready(function() {
    loadHolidays();
});

// Load holidays from database
function loadHolidays() {
    const year = $('#yearFilter').val();
    
    $.ajax({
        url: '{{ route("holidays.getData") }}',
        method: 'GET',
        data: { year: year },
        success: function(response) {
            if (response.success) {
                renderHolidaysTable(response.data);
                renderPagination(response.pagination);
            }
        },
        error: function(xhr) {
            showResultModal('error', 'Gagal!', 'Gagal memuat data libur');
        }
    });
}

function renderPagination(pagination) {
    let html = '';
    if (pagination.last_page > 1) {
        html += `<div class="flex items-center justify-between bg-white px-4 py-3 border-t">`;
        // Info kiri
        html += `<div><p class="text-sm text-gray-700">Showing ${pagination.from ?? 0} to ${pagination.to ?? 0} of ${pagination.total} entries</p></div>`;
        // Tombol kanan
        html += `<div class="flex items-center space-x-2">`;
        if (pagination.current_page > 1) {
            html += `<button onclick="loadHolidays(${pagination.current_page - 1})" class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition">Previous</button>`;
        } else {
            html += `<button disabled class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border rounded cursor-not-allowed">Previous</button>`;
        }
        // Nomor halaman
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === pagination.current_page) {
                html += `<button class="px-3 py-1 text-sm text-white bg-blue-600 border border-blue-600 rounded">${i}</button>`;
            } else {
                html += `<button onclick="loadHolidays(${i})" class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition">${i}</button>`;
            }
        }
        if (pagination.current_page < pagination.last_page) {
            html += `<button onclick="loadHolidays(${pagination.current_page + 1})" class="px-3 py-1 text-sm text-gray-700 bg-white border rounded hover:bg-gray-50 transition">Next</button>`;
        } else {
            html += `<button disabled class="px-3 py-1 text-sm text-gray-400 bg-gray-100 border rounded cursor-not-allowed">Next</button>`;
        }
        html += `</div></div>`;
    }
    $('#paginationContainer').html(html);

    // Info "Showing x to y of z entries"
    $('#paginationInfo').text(
        `Showing ${pagination.from ?? 0} to ${pagination.to ?? 0} of ${pagination.total} entries`
    );
}

// Render holidays table
function renderHolidaysTable(holidays) {
    const tbody = $('#holidayTableBody');
    tbody.empty();

    if (holidays.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="py-8 text-center">
                    <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Belum ada data libur untuk tahun ini</span>
                        <button onclick="syncHolidays()" class="mt-2 px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">
                            Sync dari API
                        </button>
                    </div>
                </td>
            </tr>
        `);
        return;
    }

    holidays.forEach(holiday => {
        const tanggal = new Date(holiday.tanggal_libur);
        const formattedDate = tanggal.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const statusBadge = holiday.libur_nasional
            ? '<span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Libur Nasional</span>'
            : '<span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Cuti Bersama</span>';

        tbody.append(`
            <tr class="border-b hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <div class="flex items-center">
                        <span class="font-medium">${formattedDate}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-600">${holiday.nama_libur}</td>
                <td class="px-4 py-3">${statusBadge}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex gap-2 justify-center">
                        <button onclick="showEditModal(${holiday.id}, '${holiday.tanggal_libur}', '${holiday.nama_libur.replace(/'/g, "\\'")}', ${holiday.libur_nasional})" 
                                class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600 transition-colors" 
                                title="Ubah Status">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

// Sync holidays from API
function syncHolidays() {
    const year = $('#yearFilter').val();
    
    $('#syncMessage').text(`Mengambil data libur tahun ${year} dari API...`);
    $('#syncModal').removeClass('hidden');

    $.ajax({
        url: '{{ route("holidays.sync") }}',
        method: 'POST',
        data: {
            year: year,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#syncModal').addClass('hidden');
            showResultModal('success', 'Berhasil!', response.message);
            loadHolidays();
        },
        error: function(xhr) {
            $('#syncModal').addClass('hidden');
            showResultModal('error', 'Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan');
        }
    });
}

// Show result modal
function showResultModal(type, title, message) {
    const iconHtml = type === 'success' 
        ? '<svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        : '<svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    
    $('#resultIcon').html(iconHtml);
    $('#resultTitle').text(title);
    $('#resultMessage').text(message);
    $('#resultModal').removeClass('hidden');
}

function closeResultModal() {
    $('#resultModal').addClass('hidden');
}

// Show edit modal
function showEditModal(id, tanggal, nama, isLiburNasional) {
    $('#editHolidayId').val(id);
    
    const date = new Date(tanggal);
    const formattedDate = date.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    $('#editTanggal').val(formattedDate);
    $('#editNama').val(nama);
    $('#editStatus').val(isLiburNasional ? '1' : '0');
    
    $('#editModal').removeClass('hidden');
}

// Close edit modal
function closeEditModal() {
    $('#editModal').addClass('hidden');
    $('#editHolidayForm')[0].reset();
}

// Submit edit form
$('#editHolidayForm').on('submit', function(e) {
    e.preventDefault();
    
    const id = $('#editHolidayId').val();
    const liburNasional = $('#editStatus').val(); 

    $.ajax({
        url: `/users/atur-hari-kerja/${id}`,
        method: 'PUT',
        data: {
            libur_nasional: liburNasional,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            closeEditModal();
            showResultModal('success', 'Berhasil!', response.message);
            window.location.reload();
            loadHolidays();
        },
        error: function(xhr) {
            showResultModal('error', 'Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan');
        }
    });
});
</script>
@endpush