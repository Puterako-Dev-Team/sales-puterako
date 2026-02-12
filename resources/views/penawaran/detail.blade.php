@extends('layouts.app')
@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 mx-auto container"
        role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif
@section('content')
    <style>
        /* Custom styling untuk jspreadsheet */
        .jexcel_content {
            max-height: 600px;
            overflow-y: auto;
        }

        .jexcel>thead>tr>td {
            color: black !important;
            font-weight: bold;
            text-align: start;
        }

        .section-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        /* Style untuk disabled state */
        .spreadsheet-disabled {
            opacity: 0.7;
        }

        .spreadsheet-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-draft {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-lost {
            background: #fecaca;
            color: #dc2626;
        }

        .status-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-po {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        /* Custom styling untuk Notyf */
        .notyf__toast--error {
            background: #ef4444 !important;
            color: white !important;
        }

        .notyf__toast--error .notyf__icon {
            color: white !important;
        }

        .notyf__toast {
            padding: 16px 20px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        /* Tab button styling untuk locked state */
        .tab-btn.locked {
            opacity: 0.5;
            cursor: not-allowed;
            color: #9ca3af !important;
        }

        .tab-btn.locked:hover {
            color: #9ca3af !important;
        }

        #rekapSpreadsheet {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px;
            background: #fff;
        }
    </style>

    <div class="flex items-center p-8 text-gray-600 -mb-8">
        @if(Auth::user()->role === 'manager')
            <a href="{{ route('penawaran.approve-list') }}" class="flex items-center hover:text-green-600">
                <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                List Penawaran
            </a>
        @else
            <a href="{{ route('penawaran.list') }}" class="flex items-center hover:text-green-600">
                <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                List Penawaran
            </a>
        @endif
        <span class="mx-2">/</span>
        <span class="font-semibold">Detail Penawaran</span>
    </div>
    <?php
    $versions = \App\Models\PenawaranVersion::where('penawaran_id', $penawaran->id_penawaran)->orderBy('version')->get();
    $activeVersion = request('version') ?? ($versions->max('version') ?? 0); ?>

    <div class="flex items-center justify-end gap-4 mx-auto p-8 container">
        <form method="GET" action="{{ route('penawaran.show') }}" class="flex items-center gap-2">
            <label class="font-semibold">Lihat Versi:</label>
            <input type="hidden" name="id" value="{{ $penawaran->id_penawaran }}">
            <select name="version" onchange="this.form.submit()" class="border rounded px-3 py-2">
                @if($versions->isEmpty())
                    <option value="">Belum ada versi</option>
                @else
                    @foreach ($versions as $v)
                        <option value="{{ $v->version }}" {{ $v->version == $activeVersion ? 'selected' : '' }}>
                            @if($v->version == 0)
                                Versi Awal (0)
                            @else
                                Revisi {{ $v->version }}
                            @endif
                        </option>
                    @endforeach
                @endif
            </select>
        </form>

        <!-- Form terpisah untuk button buat revisi -->
        <form method="POST" action="{{ route('penawaran.createRevision', ['id' => $penawaran->id_penawaran]) }}">
            @csrf
            <button type="submit" class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:shadow-lg font-semibold">
                + Tambah Revisi
            </button>
        </form>


        <!-- Pindahkan button status keluar dari form -->
        <div class="flex gap-2 -mt-4">
            @if(Auth::user()->role !== 'manager')
            <button type="button" id="logActivityBtn"
                class="bg-blue-500 text-white px-2 py-2 rounded hover:bg-blue-600 font-semibold relative"
                title="Logging Activity">
                <x-lucide-clipboard-list class="w-6 h-6 inline-block" />
                <span id="unreadBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
            </button>
            @endif
            <button type="button" onclick="openStatusModal('draft')"
                class="bg-[#FFA500] text-white px-2 py-2 rounded hover:shadow-lg font-semibold"
                title="Set as Draft">
                <x-lucide-file-edit class="w-6 h-6 inline-block" />
            </button>
            <button type="button" onclick="openStatusModal('lost')"
                class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600 font-semibold"
                title="Mark as Lost">
                <x-lucide-badge-x class="w-6 h-6 inline-block" />
            </button>
            <button type="button" onclick="openStatusModal('success')"
                class="bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 font-semibold"
                title="Mark as Submit">
                <x-lucide-badge-check class="w-6 h-6 inline-block" />
            </button>
            <button type="button" onclick="openStatusModal('po')"
                class="text-white px-2 py-2 rounded hover:shadow-lg font-semibold"
                style="background-color: #804cb2;"
                title="Mark As Purchase Order">
                <x-lucide-shopping-cart class="w-6 h-6 inline-block" />
            </button>
        </div>
    </div>
    <!-- Modal untuk update status -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96 max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold">Update Status Penawaran</h3>
                <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <form id="statusForm" method="POST" action="{{ route('penawaran.updateStatus', $penawaran->id_penawaran) }}">
                @csrf
                <input type="hidden" id="statusInput" name="status" value="">

                <div class="mb-4">
                    <label for="noteInput" class="block text-sm font-medium text-gray-700 mb-2">
                        Catatan Status:
                    </label>
                    <textarea id="noteInput" name="note" rows="4"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Masukkan catatan untuk perubahan status..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeStatusModal()"
                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 text-white rounded hover:opacity-90">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal untuk Activity Log -->
    <div id="activityLogModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-[600px] max-w-full mx-4 max-h-[80vh] flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Laporan Progress</h3>
                <button type="button" onclick="closeActivityLogModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div id="activityLogContent" class="overflow-y-auto flex-1">
                <div class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500 mt-2">Loading...</p>
                </div>
            </div>
            
            <div id="loadMoreContainer" class="hidden text-center mt-3">
                <button type="button" id="loadMoreBtn" onclick="loadMoreActivities()"
                    class="px-4 py-2 text-blue-600 hover:text-blue-700 font-medium">
                    Load More
                </button>
            </div>

            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeActivityLogModal()"
                    class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto p-8 -mt-12">
        <!-- Detail Penawaran (Header) -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex flex-wrap gap-8">
                <!-- Kolom 1 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">Informasi Penawaran</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">No. Penawaran:</span> {{ $penawaran->no_penawaran }}@if($activeVersion > 0)-Rev{{ $activeVersion }}
            @endif
                    </div>
                        <div><span class="font-medium">Perihal:</span> {{ $penawaran->perihal }}</div>
                        <div><span class="font-medium">Status:</span>
                            <span class="status-badge status-{{ $penawaran->status }}">
                                @if($penawaran->status === 'po')
                                    Purchase Order
                                @elseif($penawaran->status === 'success')
                                    Submit
                                @else
                                    {{ ucfirst($penawaran->status) }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kolom 2 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">Informasi Perusahaan</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Perusahaan:</span> {{ $penawaran->nama_perusahaan }}</div>
                        <div><span class="font-medium">Lokasi:</span> {{ $penawaran->lokasi }}</div>
                        <div><span class="font-medium">PIC Perusahaan:</span> {{ $penawaran->pic_perusahaan ?? 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Kolom 3 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">PIC Admin</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Nama:</span> {{ $penawaran->user ? $penawaran->user->name : 'N/A' }}
                        </div>
                        <div><span class="font-medium">Email:</span>
                            {{ $penawaran->user ? $penawaran->user->email : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            @php
                $tipe = $penawaran->tipe ?? null;
                $showPenawaran = empty($tipe) || $tipe === 'barang';
                $showJasa = empty($tipe) || $tipe === 'soc';
            @endphp
            @php
                $activeTab = $showPenawaran ? 'penawaran' : ($showJasa ? 'Jasa' : 'preview');
            @endphp
            <div class="flex border-b mb-4">
                @if($showPenawaran)
                <button
                    class="tab-btn px-4 py-2 font-semibold {{ $activeTab === 'penawaran' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-600' }} hover:text-green-600 focus:outline-none"
                    data-tab="penawaran">Penawaran</button>
                @endif
                @if($showJasa)
                <button class="tab-btn px-4 py-2 font-semibold {{ $activeTab === 'Jasa' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-600' }} hover:text-green-600 focus:outline-none"
                    data-tab="Jasa">Rincian Jasa</button>
                @endif
                <button class="tab-btn px-4 py-2 font-semibold {{ $activeTab === 'preview' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-600' }} hover:text-green-600 focus:outline-none"
                    data-tab="preview">Preview</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="rekap">Rincian Rekap</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="dokumen">Dokumen Pendukung</button>
            </div>

            <div id="tabContent">
                <!-- Panel Penawaran -->
                @if($showPenawaran)
                <div class="tab-panel" data-tab="penawaran">
                    <!-- Template Selection (Global) -->
                    <div class="p-2 rounded-lg mb-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                {{-- <!-- ADD: PPN input -->
                                <div class="flex items-center">
                                    <label class="block text-sm font-semibold mr-2">PPN (%)</label>
                                    <input type="number" id="ppnInput" class="border rounded px-3 py-2 bg-white w-24"
                                        min="0" step="0.1" value="11">
                                    <span class="ml-1 text-sm text-gray-600">%</span>
                                </div> --}}
                            </div>
                            <div class="flex gap-2">
                                <button id="editModeBtn"
                                    class="flex items-center bg-[#FFA500] text-white px-3 py-2 rounded hover:bg-orange-600 transition text-sm font-semibold shadow-md">
                                    <x-lucide-pencil class="w-4 h-4 mr-2" />
                                    Edit Data
                                </button>
                                <div class="flex gap-2 items-center">
                                    <button id="cancelEditBtn"
                                        class="flex items-center bg-gray-500 text-white px-3 py-2 rounded hover:bg-gray-600 transition text-sm font-semibold shadow-md">
                                        <x-lucide-x class="w-4 h-4 mr-2 " />
                                        Batal
                                    </button>
                                    <button id="saveAllBtn"
                                        class="flex items-center bg-[#67BC4B] text-white px-6 py-2 rounded hover:bg-green-700 transition text-sm font-semibold shadow-md">
                                        <x-lucide-save class="w-4 h-4 mr-2" />
                                        Simpan Data
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Button Tambah Section -->
                        <div class="mb-4 mt-6">
                            <button id="addSectionBtn"
                                class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:shadow-lg transition text-sm font-semibold shadow-md">
                                Tambah Section Baru
                            </button>
                        </div>

                        <!-- Container untuk semua section -->
                        <div id="sectionsContainer"></div>

                        <div class="mt-6 p-4 bg-gray-50 rounded-lg border-1 border-gray-200">
                            <div class="space-y-3">
                                <!-- Input PPN -->
                                <div class="flex justify-between items-center">
                                    <label class="text-sm font-semibold text-gray-700">PPN (%):</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" id="ppnInput"
                                            class="border rounded px-3 py-2 bg-white w-24 text-right" min="0" step="0.01"
                                            value="{{ $versionRow->ppn_persen ?? 11 }}">
                                        <span class="text-sm text-gray-600">%</span>
                                    </div>
                                </div>

                                {{-- <!-- Best Price toggle + input -->
                                <div class="flex items-center gap-4 mt-3">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" id="isBestPrice" {{ $penawaran->is_best_price ?? false ?
                                        'checked' : '' }} />
                                        <span class="text-sm font-medium">Gunakan Best Price</span>
                                    </label>

                                    <div class="flex items-center ml-4">
                                        <input type="text" id="bestPriceInput"
                                            class="border rounded px-3 py-2 bg-white w-40 text-right" placeholder="0"
                                            value="{{ number_format($penawaran->best_price ?? 0, 2, ',', '.') }}">
                                        <span class="ml-2 text-sm text-gray-600">Rp</span>
                                    </div>
                                </div> --}}

                                <!-- Total -->
                                <div class="flex justify-between items-center text-md font-semibold">
                                    <span>Total:</span>
                                    <span>Rp <span id="totalKeseluruhan">0</span></span>
                                </div>

                                <!-- Best Price display (hidden by default; JS toggles) -->
                                <div id="bestPriceDisplayRow"
                                    class="flex justify-between items-center text-md font-semibold" style="display:none;">
                                    <span>Best Price:</span>
                                    <span>Rp <span id="bestPriceDisplay">0</span></span>
                                </div>

                                <!-- PPN Nominal -->
                                <div class="flex justify-between items-center text-md font-semibold">
                                    <span>PPN (<span id="ppnPersenDisplay">{11}</span>%):</span>
                                    <span>Rp <span id="ppnNominal">0</span></span>
                                </div>

                                <!-- Grand Total -->
                                <div
                                    class="flex justify-between items-center text-md font-bold pt-3 border-t-2 border-gray-400">
                                    <span>Grand Total:</span>
                                    <span>Rp <span id="grandTotal">0</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-xs text-gray-700">
                            💡 <strong>Tips:</strong>
                            <ul class="list-disc ml-5 mt-1">
                                <li>Setiap section bisa punya area pemasangan berbeda</li>
                                <li>Copy dari Excel → Pilih cell → Paste (Ctrl+V)</li>
                                <li>Harga Total otomatis dihitung dari QTY × Harga Satuan</li>
                                <li>Klik "Hapus Section" untuk menghapus section yang tidak dibutuhkan</li>
                                <li><strong>Mode Edit:</strong> Klik tombol "Edit Data" untuk mengubah data yang sudah
                                    tersimpan</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
                <!-- Panel Jasa -->
                @if($showJasa)
                <div class="tab-panel {{ $activeTab === 'Jasa' ? '' : 'hidden' }}" data-tab="Jasa">
                    <div class="flex gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Profit (%)</label>
                            <input type="number" id="jasaProfitInput" class="border rounded px-3 py-2 bg-white w-24" min="0"
                                step="0.1" value="{{ $versionRow->jasa_profit_percent ?? 0 }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">PPH (%)</label>
                            <input type="number" id="jasaPphInput" class="border rounded px-3 py-2 bg-white w-24" min="0"
                                step="0.1" value="{{ $versionRow->jasa_pph_percent ?? 0 }}">
                        </div>
                        <div class="flex-1 flex justify-end items-end gap-2 mb-4">
                            <button id="jasaAddSectionBtn"
                                class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm font-semibold shadow-md hidden">
                                Tambah Section Jasa
                            </button>
                            <button id="jasaEditModeBtn"
                                class="flex items-center bg-[#FFA500] text-white px-3 py-2 rounded hover:bg-orange-600 transition text-sm font-semibold shadow-md">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Edit Data Jasa
                            </button>
                            <button id="jasaCancelEditBtn"
                                class="flex items-center bg-gray-500 text-white px-3 py-2 rounded hover:bg-gray-600 transition text-sm font-semibold shadow-md hidden">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                Batal
                            </button>
                            <button id="jasaSaveAllBtn"
                                class="flex items-center bg-[#67BC4B] text-white px-6 py-2 rounded hover:bg-green-700 transition text-sm font-semibold shadow-md">
                                <x-lucide-save class="w-4 h-4 mr-2" />
                                Simpan Data Jasa
                            </button>
                        </div>
                    </div>
                    <div id="jasaSectionsContainer"></div>

                    <div class="w-full lg:w-72 mb-4">
                        <div class="bg-white border rounded p-3 text-sm shadow-sm">
                            {{-- <div class="flex justify-between">
                                <div class="text-gray-600">Total Jasa</div>
                                <div class="font-semibold">Rp <span id="jasaOverallTotal">0</span></div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="text-gray-600">PPH Total</div>
                                <div>Rp <span id="jasaOverallPph">0</span></div>
                            </div> --}}

                            <div class="w-full lg:w-72 mb-4">
                                <div class="bg-white text-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="font-bold text-md">Total Jasa Awal</div>
                                        <div class="font-bold text-green-600 text-md">Rp <span
                                                id="jasaOverallGrand">0</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" id="jasaUseBpjs" {{ $versionRow->jasa_use_bpjs ?? false ? 'checked' : '' }} class="rounded">
                                            <label for="jasaUseBpjs" class="font-semibold text-md cursor-pointer">BPJS Konstruksi</label>
                                            <span class="ml-1 text-md">(
                                                <span id="jasaBpjsPercent">{{ $versionRow->jasa_bpjsk_percent ?? 0 }}</span> %
                                                )</span>
                                        </div>
                                        <div class="font-semibold text-blue-700 text-md">Rp
                                            <span id="jasaBpjsValue">0</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="font-bold text-md">Total Jasa Setelah BPJS</div>
                                        <div class="font-bold text-green-600 text-md">Rp
                                            <span id="jasaGrandTotal">0</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" id="jasaUsePembulatan" {{ $versionRow->jasa_use_pembulatan ?? false ? 'checked' : '' }} class="rounded">
                                            <label for="jasaUsePembulatan" class="font-semibold text-md cursor-pointer">Pembulatan</label>
                                        </div>
                                        <input type="text" id="jasaPembulatanInput" 
                                            value="{{ number_format($versionRow->jasa_pembulatan_final ?? 0, 0, ',', '.') }}"
                                            class="border rounded px-2 py-1 w-32 text-right font-semibold text-purple-700"
                                            placeholder="0" {{ ($versionRow->jasa_use_pembulatan ?? false) ? '' : 'disabled' }}>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="font-bold text-md">Total Jasa Final</div>
                                        <div class="font-bold text-purple-600 text-md">Rp
                                            <span id="jasaFinalTotal">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const input = document.getElementById('ringkasanJasa');
                            const previewSpan = document.getElementById('ringkasanJasaPreview');
                            if (input && previewSpan) {
                                input.addEventListener('input', function () {
                                    previewSpan.textContent = input.value;
                                });
                            }
                        });
                    </script>
                </div>
                @endif
                <!-- Panel Preview -->
                <div class="tab-panel {{ $activeTab === 'preview' ? '' : 'hidden' }}" data-tab="preview">
                    <style>
                        @media print {

                                {
                                font-family: "Arial", "Roboto", sans-serif !important;
                                -webkit-print-color-adjust: exact;
                                color-adjust: exact;
                            }

                            body * {
                                visibility: hidden !important;
                            }

                            #previewContent,
                            #previewContent * {
                                visibility: visible !important;
                            }

                            #previewContent {
                                position: absolute !important;
                                left: 0;
                                top: 0;
                                width: 100%;
                                background: white;
                                margin: 0;
                                padding: 0;
                            }

                            .no-print,
                            nav,
                            header,
                            .tab-btn,
                            .border-b {
                                display: none !important;
                            }

                            .break-inside-avoid {
                                break-inside: auto !important;
                            }

                            table {
                                page-break-inside: auto;
                                border-collapse: collapse;
                            }

                            tr {
                                page-break-inside: avoid;
                                page-break-after: auto;
                            }

                            thead {
                                display: table-header-group;
                            }

                            tfoot {
                                display: table-footer-group;
                            }

                            .mb-8 {
                                margin-bottom: 1rem !important;
                            }

                            @page {
                                margin: 1cm;
                            }
                        }
                        .color-1 { color: #000000; } /* Hitam */
                        .color-2 { color: #8e44ad; } /* Ungu */
                        .color-3 { color: #2980b9; } /* Biru */

                        .by-user {
                            font-style: italic;
                            font-weight: bold;
                            color: #3498db;
                        }
                    </style>

                    <!-- Action Buttons & Slider -->
                    <div class="mb-4 no-print">
                        <!-- Validation Messages -->
                        <div id="previewValidationMessages" class="mb-4"></div>

                        <!-- Check user role -->
                        @php
                            $userRole = Auth::user()->role ?? 'staff'; // Assuming users table has role column
                            $isApprover = in_array($userRole, ['supervisor', 'manager', 'direktur']);
                            $approval = \App\Models\ExportApprovalRequest::where('penawaran_id', $penawaran->id_penawaran)
                                ->where('version_id', $activeVersionId ?? null)
                                ->first();
                        @endphp

                        @if($isApprover)
                            <!-- Approver Role: Direct Export Button -->
                            <div class="text-right">
                                <a href="{{ route('penawaran.exportPdf', ['id' => $penawaran->id_penawaran, 'version' => $activeVersion]) }}"
                                    target="_blank"
                                    class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition font-semibold shadow-md">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                        </path>
                                    </svg>
                                    Export PDF
                                </a>
                            </div>
                        @else
                            <!-- Staff Role: Validation + Slider + Disabled Export Button -->
                            <!-- Hidden fields for verification request -->
                            <input type="hidden" id="penawaranId" value="{{ $penawaran->id_penawaran }}">
                            <input type="hidden" id="versionId" value="{{ $activeVersionId }}">
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <!-- Left: Required Fields Check -->
                                <div id="previewValidation" class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                                            <p class="text-sm font-semibold text-yellow-800 mb-2">⚠️ Sebelum request verifikasi, pastikan:</p>
                                            <ul class="text-sm text-yellow-700 space-y-1">
                                                @if($showJasa)
                                                <li id="checkRingkasan" class="flex items-center"><span class="mr-2">❌</span> Ringkasan Jasa sudah diisi</li>
                                                @endif
                                                <li id="checkNotes" class="flex items-center"><span class="mr-2">❌</span> Notes sudah diisi</li>
                                            </ul>
                                        </div>

                                <!-- Right: Slider Verification -->
                                <div class="rounded-lg p-4" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
                                    <p id="verificationHeaderText" class="text-sm font-semibold mb-3 text-center" style="color: #166534;">Geser ke kanan untuk request verifikasi</p>
                                    <div class="relative h-12 bg-white border-2 rounded-full flex items-center cursor-grab active:cursor-grabbing select-none" 
                                        id="verificationSlider"
                                        style="touch-action: none; border-color: #22c55e;">
                                        <!-- Track filled -->
                                        <div id="sliderTrack" class="absolute h-full rounded-full transition-all"
                                            style="width: 0%; z-index: 1; background: linear-gradient(to right, #22c55e, #16a34a);"></div>
                                        
                                        <!-- Thumb/Slider button -->
                                        <div id="sliderThumb"
                                            class="absolute h-10 w-10 bg-white rounded-full shadow-lg flex items-center justify-center cursor-grab active:cursor-grabbing transition-all z-10"
                                            style="left: 4px; border: 2px solid #22c55e;">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #22c55e;">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                        
                                        <!-- Text -->
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span id="sliderText" class="text-sm font-semibold pointer-events-none" style="color: #374151;">Geser untuk request</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Verifikasi Jabatan Manajerial -->
                            @php
                                $roles = [
                                    'supervisor' => [
                                        'label' => 'Supervisor',
                                        'approved_by' => 'approved_by_supervisor',
                                        'approved_at' => 'approved_at_supervisor',
                                    ],
                                    'manager' => [
                                        'label' => 'Manajer',
                                        'approved_by' => 'approved_by_manager',
                                        'approved_at' => 'approved_at_manager',
                                    ],
                                    'direktur' => [
                                        'label' => 'Direktur',
                                        'approved_by' => 'approved_by_direktur',
                                        'approved_at' => 'approved_at_direktur',
                                    ],
                                ];
                            @endphp

                            <div class="grid grid-cols-3 gap-4 mb-4">
                                @foreach ($roles as $roleKey => $roleData)
                                    @php
                                        $approvedBy = $approval ? $approval->{$roleData['approved_by']} : null;
                                        $approvedAt = $approval ? $approval->{$roleData['approved_at']} : null;
                                        $isApproved = $approvedBy && $approvedAt;
                                        
                                        // Check if direktur approval was done by supervisor (as representative)
                                        $isRepresentative = false;
                                        if ($roleKey === 'direktur' && $approvedBy) {
                                            $approver = \App\Models\User::find($approvedBy);
                                            $isRepresentative = $approver && $approver->role === 'supervisor';
                                        }
                                        
                                        if ($approvedBy) {
                                            $approver = \App\Models\User::find($approvedBy);
                                            // Jika direktur approval diwakili supervisor, cari dan tampilkan nama user dengan role direktur
                                            if ($isRepresentative) {
                                                $direkturUser = \App\Models\User::where('role', 'direktur')->first();
                                                $approverName = $direkturUser ? $direkturUser->name : 'Direktur';
                                            } else {
                                                $approverName = $approver ? $approver->name : 'Unknown';
                                            }
                                        } else {
                                            $approverName = '-';
                                        }
                                    @endphp

                                    <div class="border rounded-lg p-4" style="border-color: {{ $isApproved ? '#22c55e' : '#cbd5e1' }}; background-color: {{ $isApproved ? '#f0fdf4' : '#f8fafc' }};">
                                        <!-- Status Icon & Text -->
                                        <div class="flex items-center justify-center mb-3">
                                            @if ($isApproved)
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full" style="background-color: #22c55e;">
                                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <span class="ml-2 text-sm font-semibold text-green-700">Disetujui</span>
                                            @else
                                                <div class="flex items-center justify-center w-10 h-10 rounded-full" style="background-color: #e2e8f0;">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <span class="ml-2 text-sm font-semibold text-gray-500">Menunggu</span>
                                            @endif
                                        </div>

                                        <!-- Role -->
                                        <div class="text-center mb-3">
                                            <p class="text-sm font-semibold text-gray-700">{{ $roleData['label'] }}</p>
                                        </div>

                                        <!-- Separator -->
                                        <div class="border-t mb-3" style="border-color: {{ $isApproved ? '#dcfce7' : '#e2e8f0' }};"></div>

                                        <!-- Nama -->
                                        <div class="mb-2">
                                            <p class="text-xs text-gray-500 mb-1">Nama:</p>
                                            <p class="text-sm font-semibold text-gray-800">{{ $approverName }}</p>
                                            
                                            @if ($isRepresentative)
                                                <p class="text-xs italic text-blue-600 mt-1">
                                                    <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                    </svg>
                                                    Approval diwakili supervisor
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Tanggal Approved -->
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Tanggal Disetujui:</p>
                                            <p class="text-sm font-semibold text-gray-800">
                                                @if ($approvedAt)
                                                    {{ \Carbon\Carbon::parse($approvedAt)->locale('id')->translatedFormat('d F Y') }}
                                                @else
                                                    -
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Export Button: enabled only when fully approved -->
                            <div class="text-right">
                                @if($approval && $approval->status === 'fully_approved')
                                    <a href="{{ route('penawaran.exportPdf', ['id' => $penawaran->id_penawaran, 'version' => $activeVersion]) }}"
                                        target="_blank"
                                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition font-semibold shadow-md">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                            </path>
                                        </svg>
                                        Export PDF
                                    </a>
                                @else
                                    <button type="button"
                                        disabled
                                        class="bg-gray-400 text-white px-6 py-2 rounded cursor-not-allowed font-semibold shadow-md opacity-60"
                                        title="Tombol akan aktif setelah disetujui oleh supervisor, manager, atau direktur">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                            </path>
                                        </svg>
                                        Export PDF (Belum diverifikasi)
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="bg-white  rounded-lg p-8" id="previewContent">
                        <!-- Header -->
                        <div class="mb-8">
                            <div class="">
                                <img src="{{ asset('assets/banner.png') }}" alt="Kop Perusahaan"
                                    class=" w-full h-auto object-cover" style="max-height:140px; display:block;" />
                            </div>
                        </div>

                        <!-- Info Penawaran -->
                        <div class="mb-6">
                            <p class="mb-1">
                                <span class="font-semibold">Surabaya,</span>
                                {{ \Carbon\Carbon::parse($penawaran->created_at ?? now())->locale('id')->translatedFormat('d F Y') }}
                            </p>
                            <p class="mb-4">
                                <span class="font-semibold">Kepada Yth:</span><br>
                                <strong>{{ $penawaran->nama_perusahaan }}</strong><br>
                                @if ($penawaran->pic_perusahaan)
                                    Up. {{ $penawaran->pic_perusahaan }}
                                @endif
                            </p>
                            <p class="mb-1"><span class="font-semibold">Perihal:</span> {{ $penawaran->perihal }}
                            </p>
                            <p class="mb-1"><span class="font-semibold">No:</span> {{ $penawaran->no_penawaran }}@if ($activeVersion > 0)-Rev{{ $activeVersion }}
            @endif
                            </p>
                            
                            <p class="mb-4"><strong>Dengan Hormat,</strong></p>
                            <p class="mb-6">
                                Bersama ini kami PT. Puterako Inti Buana memberitahukan
                                {{ $penawaran->perihal }}
                                dengan perincian sebagai berikut:
                            </p>
                        </div>

                        <!-- Sections -->
                        @php
                            $groupedSections = collect($sections)->groupBy('nama_section');
                            $sectionNumber = 0;

                            function convertToRoman($num)
                            {
                                $map = [
                                    'M' => 1000,
                                    'CM' => 900,
                                    'D' => 500,
                                    'CD' => 400,
                                    'C' => 100,
                                    'XC' => 90,
                                    'L' => 50,
                                    'XL' => 40,
                                    'X' => 10,
                                    'IX' => 9,
                                    'V' => 5,
                                    'IV' => 4,
                                    'I' => 1,
                                ];
                                $result = '';
                                foreach ($map as $roman => $value) {
                                    while ($num >= $value) {
                                        $result .= $roman;
                                        $num -= $value;
                                    }
                                }
                                return $result;
                            }
                        @endphp

                        @foreach ($groupedSections as $namaSection => $sectionGroup)
                            @php 
                            $sectionNumber++;
                            $fontClass = 'color-' . ($row->color_code ?? 1); @endphp
                            <div class="mb-8 break-inside-avoid">
                                <h3 class="font-bold text-lg mb-3">
                                    {{ convertToRoman($sectionNumber) }}.
                                    {{ $namaSection ?: 'Section ' . $sectionNumber }}
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 text-sm">
                                        <thead class="bg-gray-100">
                                <tr >
                                                <th class="border border-gray-300 px-3 py-2 text-center w-12">No</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center">Tipe</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center">Deskripsi</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-16">Qty</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-20">Satuan
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-32">Harga
                                                    Satuan
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-32">Harga
                                                    Total
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-32" style="color: #000000; font-weight: bold;">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $subtotal = 0;
                                                $rowNum = 1;
                                            @endphp
                                            @foreach (collect($sectionGroup)->groupBy('area') as $area => $areaRows)
                                                @if ($area)
                                                    <tr>
                                                        <td colspan="8"
                                                            style="background:#67BC4B;font-weight:bold; color: white; text-align: center; padding: 8px;">
                                                            {{ $area }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @foreach ($areaRows as $section)
                                                    @foreach ($section['data'] as $row)
                                                        @php $subtotal += $row['harga_total']; @endphp
                                                        <tr class="color-{{ $row['color_code'] ?? 1 }}">
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ $row['no'] }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {!! nl2br(e($row['tipe'])) !!}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {!! nl2br(e($row['deskripsi'])) !!}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ number_format($row['qty'], 0) }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ $row['satuan'] }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-right">
                                                                @if ((int) ($row['is_judul'] ?? 0) === 1)
                                                                    {{-- Kosong jika is_judul --}}
                                                                @elseif ((int) $row['is_mitra'] === 1)
                                                                    <span style="color:#3498db;font-weight:bold;font-style:italic;">
                                                                        by User
                                                                    </span>
                                                                @else
                                                                    {{ $row['harga_satuan'] > 0 ? 'Rp ' . number_format($row['harga_satuan'], 0, ',', '.') : '' }}
                                                                @endif
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-right">
                                                                @if ((int) ($row['is_judul'] ?? 0) === 1)
                                                                    {{-- Kosong jika is_judul --}}
                                                                @elseif ((int) $row['is_mitra'] === 1)
                                                                    <span style="color:#3498db;font-weight:bold;font-style:italic;">
                                                                        by User
                                                                    </span>
                                                                @else
                                                                    {{ $row['harga_total'] > 0 ? 'Rp ' . number_format($row['harga_total'], 0, ',', '.') : '' }}
                                                                @endif
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center" style="color: #000000;">{{ $row['delivery_time'] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="6" class="text-center font-bold bg-gray-50">Subtotal
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-right font-bold">
                                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endforeach

                        <!-- Tabel Jasa Detail (hanya sekali di bawah semua section) -->
                        @if($showJasa)
                        @php
                            // Hitung jasa final value - gunakan pembulatan jika diaktifkan
                            $jasaFinalValue = 0;
                            if (($versionRow->jasa_use_pembulatan ?? false) && ($versionRow->jasa_pembulatan_final ?? 0) > 0) {
                                $jasaFinalValue = $versionRow->jasa_pembulatan_final;
                            } else {
                                $jasaFinalValue = $versionRow->jasa_grand_total ?? 0;
                            }
                        @endphp
                        <div class="mb-8 break-inside-avoid">
                            <h3 class="font-bold text-lg mb-3">
                                {{ convertToRoman($sectionNumber + 1) }}. Biaya Quotation Jasa
                            </h3>
                            <table class="w-full border-collapse border border-gray-300 text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-3 py-2 text-center w-12">No</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center">Deskripsi</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center w-16">Qty</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center w-20">Satuan</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center w-32">Harga Satuan</th>
                                        <th class="border border-gray-300 px-3 py-2 text-right w-32">Harga Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-gray-300 px-3 py-2 text-center">1</td>
                                        <td class="border border-gray-300 px-3 py-2">
                                            <pre
                                                class="whitespace-pre-wrap font-sans text-sm m-0">{{ $versionRow->jasa_ringkasan ?? '' }}</pre>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2 text-center">1</td>
                                        <td class="border border-gray-300 px-3 py-2 text-center">Lot</td>
                                        <td class="border border-gray-300 px-3 py-2 text-right">
                                            Rp {{ number_format($jasaFinalValue, 0, ',', '.') }}</td>
                                        <td class="border border-gray-300 px-3 py-2 text-right">
                                            Rp {{ number_format($jasaFinalValue, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-center font-bold bg-gray-50">Subtotal</td>
                                        <td class="border border-gray-300 px-3 py-2 text-right font-bold">
                                            Rp {{ number_format($jasaFinalValue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endif

                        <!-- Summary -->
                        <div class="mt-8 flex justify-end">
                            <div class="w-96">
                                <table class="w-full text-sm">
                                    <tr>
                                        <td class="py-2 font-semibold">Total</td>
                                        <td class="py-2 text-right">Rp
                                            @php
                                                // Hitung total dari section penawaran
                                                $totalPenawaran = 0;
                                                foreach ($sections as $section) {
                                                    foreach ($section['data'] as $row) {
                                                        $totalPenawaran += $row['harga_total'];
                                                    }
                                                }

                                                // Tambahkan jasa hanya jika tab jasa ditampilkan
                                                // Gunakan nilai pembulatan final jika diaktifkan
                                                $jasaTotalForSummary = 0;
                                                if (($versionRow->jasa_use_pembulatan ?? false) && ($versionRow->jasa_pembulatan_final ?? 0) > 0) {
                                                    $jasaTotalForSummary = $versionRow->jasa_pembulatan_final;
                                                } else {
                                                    $jasaTotalForSummary = $versionRow->jasa_grand_total ?? 0;
                                                }
                                                $jasaTotal = ($showJasa ?? false) ? $jasaTotalForSummary : 0;
                                                // Total keseluruhan = total penawaran + jasa grand total (opsional)
                                                $totalKeseluruhan = $totalPenawaran + $jasaTotal;
                                            @endphp
                                            {{ number_format($totalKeseluruhan, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <!-- Best Price toggle + input -->
                                    <div class="flex items-center gap-4 mt-3">

                                        <form method="POST"
                                            action="{{ route('penawaran.saveBestPrice', ['id' => $penawaran->id_penawaran, 'version' => $activeVersion]) }}"
                                            class="flex items-center ml-4">
                                            @csrf
                                            <input type="hidden" name="version" value="{{ $activeVersion }}">
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" name="is_best_price" id="isBestPrice" value="1" {{ $isBest ?? false ? 'checked' : '' }} />
                                                <span class="text-sm font-medium">Best Price</span>
                                            </label>
                                            <span class="ml-2 text-sm text-gray-600">Rp</span>
                                            <input type="text" name="best_price" id="bestPriceInput"
                                                class="border rounded px-3 py-2 bg-white w-40 text-right" placeholder="0"
                                                value="{{ $isBest ? $bestPrice : 0 }}">
                                            <button type="submit"
                                                class="ml-4 bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition font-semibold shadow-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-circle-check-big-icon lucide-circle-check-big">
                                                    <path d="M21.801 10A10 10 0 1 1 17 3.335" />
                                                    <path d="m9 11 3 3L22 4" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Diskon toggle + input -->
                                    <div class="flex items-center gap-4 mt-3">
                                        <form method="POST"
                                            action="{{ route('penawaran.saveDiskon', ['id' => $penawaran->id_penawaran, 'version' => $activeVersion]) }}"
                                            class="flex items-center ml-4">
                                            @csrf
                                            <input type="hidden" name="version" value="{{ $activeVersion }}">
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" name="is_diskon" id="isDiskon" value="1" {{ $isDiskon ?? false ? 'checked' : '' }} />
                                                <span class="text-sm font-medium">Diskon</span>
                                            </label>
                                            <input type="text" name="diskon" id="diskonInput"
                                                class="border rounded px-3 py-2 bg-white w-20 text-right ml-2" placeholder="0"
                                                value="{{ $isDiskon ? $diskon : 0 }}">
                                            <span class="ml-1 text-sm text-gray-600">%</span>
                                            <button type="submit"
                                                class="ml-4 bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition font-semibold shadow-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-circle-check-big-icon lucide-circle-check-big">
                                                    <path d="M21.801 10A10 10 0 1 1 17 3.335" />
                                                    <path d="m9 11 3 3L22 4" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>

                                    @if ($isBest)
                                        <tr>
                                            <td class="py-2 font-semibold">Best Price</td>
                                            <td class="py-2 text-right">Rp
                                                {{ number_format($bestPrice, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($isDiskon && $diskon > 0)
                                        @php
                                            $baseAmountBeforeDiskon = $isBest && $bestPrice > 0 ? $bestPrice : $totalKeseluruhan;
                                            $diskonNominalCalc = ($baseAmountBeforeDiskon * $diskon) / 100;
                                            $afterDiskon = $baseAmountBeforeDiskon - $diskonNominalCalc;
                                        @endphp
                                        <tr>
                                            <td class="py-2 font-semibold text-red-600">Diskon {{ number_format($diskon, 0, ',', '.') }}%</td>
                                            <td class="py-2 text-right text-red-600">- Rp
                                                {{ number_format($diskonNominalCalc, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 font-semibold">After Diskon</td>
                                            <td class="py-2 text-right">Rp
                                                {{ number_format($afterDiskon, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="py-2 font-semibold">PPN {{ number_format($ppnPersen, 0, ',', '.') }}%
                                        </td>
                                        <td class="py-2 text-right">Rp
                                            @php
                                                $baseAmountForPPN = $isBest && $bestPrice > 0 ? $bestPrice : $totalKeseluruhan;
                                                // Hitung diskon sebagai persen
                                                if ($isDiskon && $diskon > 0) {
                                                    $diskonNominalPPN = ($baseAmountForPPN * $diskon) / 100;
                                                    $baseAmountForPPN = $baseAmountForPPN - $diskonNominalPPN;
                                                }
                                                $ppnNominal = ($baseAmountForPPN * $ppnPersen) / 100;
                                            @endphp
                                            {{ number_format($ppnNominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr class="border-t-2 border-gray-400">
                                        <td class="py-2 font-bold text-lg">Grand Total</td>
                                        <td class="py-2 text-right font-bold text-lg">Rp
                                            @php
                                                $grandTotalFinal = $baseAmountForPPN + $ppnNominal;
                                            @endphp
                                            {{ number_format($grandTotalFinal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Tabel Jasa di Preview -->
                        @if($showJasa)
                        <form method="POST"
                            action="{{ route('jasa.saveRingkasan', ['id_penawaran' => $penawaran->id_penawaran]) }}"
                            id="ringkasanForm">
                            @csrf
                            <input type="hidden" name="version" value="{{ $activeVersion }}">
                            <div class="mb-4">
                                <label for="ringkasan" class="font-bold mb-2 block">Ringkasan Jasa: <span class="text-red-600">*</span></label>
                                <textarea rows="7" class="border rounded w-full p-3 text-sm mb-2" name="ringkasan"
                                    id="ringkasan"
                                    placeholder="Masukkan Ringkasan Jasa"
                                    required>{{ old('ringkasan', $versionRow->jasa_ringkasan ?? '') }}</textarea>
                                <small class="text-gray-600">Ringkasan jasa harus diisi sebelum request verifikasi export PDF</small>
                            </div>
                            <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition font-semibold shadow-md">
                                Simpan Ringkasan Jasa
                            </button>
                        </form>
                        @endif

                        <!-- Notes -->
                        <div class="mt-8 mb-6">
                            <form method="POST" action="{{ route('penawaran.saveNotes', $penawaran->id_penawaran) }}" id="notesForm">
                                @csrf
                                <input type="hidden" name="version" value="{{ $activeVersion }}">
                                @php
                                    // Calculate grand total from backend data for form - MUST match preview calculation
                                    $totalPenawaran = collect($sections)->sum(fn($s) => collect($s['data'])->sum('harga_total'));
                                    // Gunakan nilai pembulatan final jika diaktifkan
                                    $jasaTotalForNotes = 0;
                                    if (($versionRow->jasa_use_pembulatan ?? false) && ($versionRow->jasa_pembulatan_final ?? 0) > 0) {
                                        $jasaTotalForNotes = $versionRow->jasa_pembulatan_final;
                                    } else {
                                        $jasaTotalForNotes = $versionRow->jasa_grand_total ?? 0;
                                    }
                                    $totalKeseluruhan = $totalPenawaran + $jasaTotalForNotes;
                                    $ppnPersen = $versionRow->ppn_persen ?? 11;
                                    $isBest = $versionRow->is_best_price ?? false;
                                    $bestPrice = $versionRow->best_price ?? 0;
                                    $baseAmountForPPN = $isBest ? $bestPrice : $totalKeseluruhan;
                                    $ppnNominal = ($baseAmountForPPN * $ppnPersen) / 100;
                                    $formGrandTotal = $baseAmountForPPN + $ppnNominal;
                                @endphp
                                <input type="hidden" name="grand_total_calculated" id="grand_total_calculated" value="{{ (int) $formGrandTotal }}">
                                <div class="mb-4">
                                    <label for="note" class="font-bold mb-2 block">Notes: <span class="text-red-600">*</span></label>
                                    <textarea rows="7" name="note" class="w-full border rounded px-3 py-2"
                                        id="note"
                                        placeholder="Masukkan catatan untuk versi {{ $activeVersion }} ini..."
                                        required>{{ old('note', $versionRow->notes ?? '') }}</textarea>
                                    <small class="text-gray-600">Notes harus diisi sebelum request verifikasi export PDF</small>
                                </div>
                                <button type="submit"
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 mt-2">
                                    Simpan Notes
                                </button>
                            </form>
                        </div>
                        <div class="mt-8 border-t pt-6">
                            <h4 class="font-bold mb-3">NOTE:</h4>
                            @if (!empty($versionRow->notes))
                                <pre
                                    class="whitespace-pre-wrap font-sans text-sm leading-relaxed">{{ $versionRow->notes }}</pre>
                            @else
                                <p class="text-gray-500 text-sm">Belum ada catatan untuk versi ini.</p>
                            @endif
                        </div>

                        <!-- Footer -->
                        <div class="mt-8">
                            <p class="mb-8">Demikian penawaran ini kami sampaikan</p>
                            <p class="font-semibold mb-1">Hormat kami,</p>
                            <div class="mt-16">
                                <p class="font-bold border-b border-gray-800 inline-block pb-1 w-48"></p>
                                <p class="text-sm mt-1">Junly Kodradjaya</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-panel hidden" data-tab="rekap">
                <div class="flex justify-end mb-4">
                    <button id="importRekapBtn" class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:shadow-lg font-semibold">
                        Import Data Rekap
                    </button>
                </div>
                <div id="rekapSpreadsheet"></div>
                <div id="rekapAccumulation" class="mt-4 p-4 border rounded bg-white">
                    <h4 class="font-bold mb-2">Akumulasi Total (Semua Area)</h4>
                    <div id="rekapAccumulationBody" class="text-sm text-gray-700 mt-4">
                        <div class="text-gray-500">Belum ada data rekap.</div>
                    </div>
                </div>
                </div>

                <!-- Dokumen Pendukung Tab -->
                <div class="tab-panel hidden" data-tab="dokumen">
                    <div class="space-y-6">
                        <!-- BoQ File Section -->
                        @if($penawaran->template_type === 'template_boq' && $penawaran->boq_file_path)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="font-bold text-blue-900 mb-4">File BoQ (Template BoQ)</h3>
                            <div class="flex items-center gap-4 p-4 bg-white border border-blue-200 rounded">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12c4.418 0 8-1.79 8-4s-3.582-4-8-4-8 1.79-8 4 3.582 4 8 4zm0 0v6m0 0c-4.418 0-8 1.79-8 4s3.582 4 8 4 8-1.79 8-4-3.582-4-8-4zm0 0V8"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">{{ basename($penawaran->boq_file_path) }}</p>
                                    <p class="text-sm text-gray-600">File BoQ dari penawaran</p>
                                </div>
                                <a href="{{ route('upload.download') }}?path={{ urlencode($penawaran->boq_file_path) }}" 
                                   class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    Download
                                </a>
                            </div>
                        </div>
                        @endif

                        <!-- Supporting Documents Section -->
                        <div class="border border-gray-200 rounded-lg p-6">
                            <h3 class="font-bold text-gray-900 mb-4">File Pendukung Tambahan</h3>
                            
                            <!-- Upload Form -->
                            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <form id="supportDocForm" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                                        <input type="file" id="supportDocFile" name="file"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-green-500"
                                            required>
                                        <p class="text-xs text-gray-500 mt-1">Format: PDF, Word, Excel, atau Gambar (Maks 10MB)</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                                        <textarea id="supportDocNotes" name="notes"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-green-500"
                                            rows="2" placeholder="Tambahkan catatan tentang file ini..."></textarea>
                                    </div>

                                    <button type="submit" id="uploadSupportDocBtn"
                                        class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
                                        Upload Dokumen Pendukung
                                    </button>
                                </form>
                            </div>

                            <!-- Documents List -->
                            <div id="supportDocsList">
                                @forelse($penawaran->supportingDocuments as $doc)
                                    <div class="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded mb-3" data-doc-id="{{ $doc->id }}">
                                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900">{{ $doc->original_filename }}</p>
                                            @if($doc->notes)
                                                <p class="text-sm text-gray-600 mt-1">{{ $doc->notes }}</p>
                                            @endif
                                            <p class="text-xs text-gray-500 mt-1">Diupload: {{ $doc->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ route('upload.download') }}?path={{ urlencode($doc->file_path) }}"
                                               class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm">
                                                Download
                                            </a>
                                            <button type="button" onclick="deleteSupportDoc({{ $doc->id }}, {{ $penawaran->id_penawaran }})"
                                                class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-gray-500 py-8">
                                        <p>Belum ada dokumen pendukung.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="importRekapModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-96 max-w-md mx-4">
                    <h2 class="text-lg font-bold mb-4">Pilih Data Rekap</h2>
                    <select id="rekapDropdown" class="w-full border rounded p-2 mb-4">
                        <option value="">-- Pilih Rekap --</option>
                    </select>
                    <div class="flex justify-end gap-2">
                        <button id="closeRekapModal" class="btn btn-secondary py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded">Batal</button>
                        <button id="loadRekapBtn" class="btn btn-primary py-2 px-4 bg-green-500 hover:bg-green-600 text-white rounded">Load</button>
                    </div>
                </div>
            </div>
            
            <!-- Comment Modal -->
            <div id="commentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-4">
                    <h2 id="commentModalTitle" class="text-lg font-bold mb-4">Insert Comment</h2>
                    <textarea id="commentModalTextarea" class="w-full border rounded p-3 mb-4 resize-y" rows="6" placeholder="Masukkan komentar..."></textarea>
                    <div class="flex justify-end gap-2">
                        <button id="commentModalCancel" class="btn btn-secondary py-2 px-4 bg-gray-500 hover:bg-gray-600 text-white rounded">Batal</button>
                        <button id="commentModalOk" class="btn btn-primary py-2 px-4 bg-blue-500 hover:bg-blue-600 text-white rounded">OK</button>
                    </div>
                </div>
            </div>

            <!-- Dokumen Pendukung Modal for Rekap -->
            <div id="rekapSupportDocModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Dokumen Pendukung Rekap</h2>
                        <button id="rekapSupportDocClose" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                    </div>
                    <div id="rekapSupportDocContent" class="space-y-4">
                        <!-- Documents will be rendered here -->
                    </div>
                    <div class="flex justify-end gap-2 mt-6 border-t pt-4">
                        <button id="rekapSupportDocCloseBtn" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Tutup</button>
                    </div>
                </div>
            </div>
@endsection

        <script>
            const activeVersion = {{ $activeVersion ?? 0 }};
            // Global variable to store calculated grand total
            let currentGrandTotal = 0;
        </script>
        <script>
            const satuanOptions = @json($satuans->pluck('nama'));
            const satuanMap = @json($satuans->mapWithKeys(function($s) { return [$s->id => $s->nama]; }));
        </script>

        @push('scripts')
            <script>
                // Data awal dari backend
                const initialSections = @json($sections);
                const hasExistingData = initialSections.length > 0;
                // Tipe penawaran: '' or null means default (all tabs)
                const tipe = '{{ $penawaran->tipe ?? '' }}';
                const showPenawaran = !tipe || tipe === 'barang';
                const showJasa = !tipe || tipe === 'soc';
            </script>
            <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
            <link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />
            <script src="https://jsuites.net/v4/jsuites.js"></script>
            <script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>

            <script>
                // Function untuk scroll ke cell yang dipilih (horizontal)
                function scrollToSelectedCell(instance, colIndex, rowIndex) {
                    const table = instance.querySelector('.jexcel');
                    if (!table) return;

                    const scrollWrapper = instance.closest('.spreadsheet-scroll-wrapper');
                    if (!scrollWrapper) return;

                    // Dapatkan cell yang dipilih
                    const cell = table.querySelector(`td[data-x="${colIndex}"]`);
                    if (!cell) return;

                    const cellRect = cell.getBoundingClientRect();
                    const wrapperRect = scrollWrapper.getBoundingClientRect();

                    // Cek apakah cell di luar viewport horizontal
                    const cellLeft = cell.offsetLeft;
                    const cellRight = cellLeft + cell.offsetWidth;
                    const wrapperScrollLeft = scrollWrapper.scrollLeft;
                    const wrapperVisibleWidth = scrollWrapper.clientWidth;

                    // Scroll ke kanan jika cell di luar viewport kanan
                    if (cellRight > wrapperScrollLeft + wrapperVisibleWidth) {
                        scrollWrapper.scrollTo({
                            left: cellRight - wrapperVisibleWidth + 50, // +50 untuk padding
                            behavior: 'smooth'
                        });
                    }
                    // Scroll ke kiri jika cell di luar viewport kiri
                    else if (cellLeft < wrapperScrollLeft) {
                        scrollWrapper.scrollTo({
                            left: cellLeft - 50, // -50 untuk padding
                            behavior: 'smooth'
                        });
                    }
                }

                function openStatusModal(status) {
                    const modal = document.getElementById('statusModal');
                    const modalTitle = document.getElementById('modalTitle');
                    const statusInput = document.getElementById('statusInput');
                    const submitBtn = document.getElementById('submitBtn');
                    const noteInput = document.getElementById('noteInput');

                    statusInput.value = status;
                    noteInput.value = '';

                    if (status === 'draft') {
                        modalTitle.textContent = 'Tandai Penawaran Draft';
                        submitBtn.textContent = 'Tandai Draft';
                        submitBtn.className = 'px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600';
                        noteInput.placeholder = 'Masukkan catatan untuk draft...';
                    } else if (status === 'success') {
                        modalTitle.textContent = 'Tandai Penawaran Submit';
                        submitBtn.textContent = 'Tandai Submit';
                        submitBtn.className = 'px-4 py-2 text-white bg-green-500 rounded hover:bg-green-600';
                        noteInput.placeholder = 'Masukkan catatan untuk submit penawaran...';
                    } else if (status === 'lost') {
                        modalTitle.textContent = 'Tandai Penawaran Gagal';
                        submitBtn.textContent = 'Tandai Gagal';
                        submitBtn.className = 'px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600';
                        noteInput.placeholder = 'Masukkan alasan penawaran gagal...';
                    } else if (status === 'po') {
                        modalTitle.textContent = 'Tandai Penawaran Purchase Order';
                        submitBtn.textContent = 'Tandai Purchase Order';
                        submitBtn.className = 'px-4 py-2 text-white rounded hover:shadow-lg';
                        submitBtn.style.backgroundColor = '#804cb2';
                        noteInput.placeholder = 'Masukkan catatan untuk purchase order...';
                    }

                    modal.classList.remove('hidden');
                }

                function closeStatusModal() {
                    const modal = document.getElementById('statusModal');
                    modal.classList.add('hidden');
                }

                // Capture calculated grand total for notes form
                function captureGrandTotal(e) {
                    e.preventDefault();
                    
                    // Use the raw grand total value calculated by JavaScript
                    const grandTotalValue = Math.round(currentGrandTotal) || 0;
                    document.getElementById('grand_total_calculated').value = grandTotalValue;
                    
                    console.log('🚀 captureGrandTotal() called');
                    console.log('   currentGrandTotal:', currentGrandTotal);
                    console.log('   grandTotalValue to send:', grandTotalValue);
                    console.log('   form hidden field now has:', document.getElementById('grand_total_calculated').value);
                    
                    // Submit the form
                    document.getElementById('notesForm').submit();
                }

                // Close modal when clicking outside
                document.getElementById('statusModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeStatusModal();
                    }
                });

                // Activity Log Modal Functions
                const penawaranId = {{ $penawaran->id_penawaran }};

                function checkUnreadActivities() {
                    const badge = document.getElementById('unreadBadge');
                    
                    fetch(`{{ route('penawaran.countUnreadActivities') }}?id=${penawaranId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.unread_count > 0) {
                                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error checking unread activities:', error);
                        });
                }

                function openActivityLogModal() {
                    const modal = document.getElementById('activityLogModal');
                    modal.classList.remove('hidden');
                    loadActivityLog();
                    
                    // Mark activities as read on server
                    fetch(`{{ route('penawaran.markActivitiesRead') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ id: penawaranId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hide badge
                            const badge = document.getElementById('unreadBadge');
                            badge.classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error marking activities as read:', error);
                    });
                }

                function closeActivityLogModal() {
                    const modal = document.getElementById('activityLogModal');
                    modal.classList.add('hidden');
                }

                let allActivities = [];
                let displayedCount = 0;
                const itemsPerPage = 7;

                function renderActivity(activity) {
                    let description = '';
                    if (activity.description === 'Exported PDF') {
                        description = `${activity.causer_name} melakukan export/print PDF`;
                        if (activity.properties && activity.properties.version !== undefined) {
                            description += ` (Rev ${activity.properties.version})`;
                        }
                    } else if (activity.description === 'Created revision') {
                        description = `${activity.causer_name} membuat revisi`;
                        if (activity.properties && activity.properties.new_version !== undefined) {
                            description += ` (Rev ${activity.properties.new_version})`;
                        }
                    } else if (activity.description === 'Edited penawaran') {
                        description = `${activity.causer_name} mengedit penawaran`;
                        if (activity.properties && activity.properties.version !== undefined) {
                            description += ` (Rev ${activity.properties.version})`;
                        }
                    } else {
                        description = `${activity.causer_name} - ${activity.description}`;
                    }
                    
                    let html = `<div class="border-b pb-2">`;
                    html += `<div class="text-sm text-gray-500 mb-1">${activity.created_at_formatted}</div>`;
                    html += `<div class="text-gray-700">• ${description}</div>`;
                    html += `</div>`;
                    return html;
                }

                function loadActivityLog() {
                    const contentDiv = document.getElementById('activityLogContent');
                    const loadMoreContainer = document.getElementById('loadMoreContainer');
                    const penawaranId = {{ $penawaran->id_penawaran }};

                    fetch(`{{ route('penawaran.showLog') }}?id=${penawaranId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.activities.length > 0) {
                                allActivities = data.activities;
                                displayedCount = 0;
                                
                                let html = '<div id="activityList" class="space-y-3">';
                                
                                // Show first 7 items
                                const initialItems = allActivities.slice(0, itemsPerPage);
                                initialItems.forEach(activity => {
                                    html += renderActivity(activity);
                                });
                                displayedCount = initialItems.length;
                                
                                html += '</div>';
                                contentDiv.innerHTML = html;
                                
                                // Show/hide load more button
                                if (allActivities.length > displayedCount) {
                                    loadMoreContainer.classList.remove('hidden');
                                } else {
                                    loadMoreContainer.classList.add('hidden');
                                }
                            } else {
                                contentDiv.innerHTML = '<p class="text-center text-gray-500 py-4">Belum ada aktivitas yang tercatat</p>';
                                loadMoreContainer.classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error loading activity log:', error);
                            contentDiv.innerHTML = '<p class="text-center text-red-500 py-4">Gagal memuat activity log</p>';
                            loadMoreContainer.classList.add('hidden');
                        });
                }

                function loadMoreActivities() {
                    const activityList = document.getElementById('activityList');
                    const loadMoreContainer = document.getElementById('loadMoreContainer');
                    
                    // Get next 7 items
                    const nextItems = allActivities.slice(displayedCount, displayedCount + itemsPerPage);
                    
                    nextItems.forEach(activity => {
                        const activityHtml = renderActivity(activity);
                        activityList.insertAdjacentHTML('beforeend', activityHtml);
                    });
                    
                    displayedCount += nextItems.length;
                    
                    // Hide load more button if all items are displayed
                    if (displayedCount >= allActivities.length) {
                        loadMoreContainer.classList.add('hidden');
                    }
                }

                // Bind activity log button
                document.getElementById('logActivityBtn').addEventListener('click', openActivityLogModal);

                // Check unread activities on page load
                checkUnreadActivities();

                // Close activity log modal when clicking outside
                document.getElementById('activityLogModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeActivityLogModal();
                    }
                });
            </script>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // =====================================================
                    // DEKLARASI VARIABEL
                    // =====================================================

                    // Variabel Penawaran
                    let sections = window.penawaranSections = [];
                    let sectionCounter = 0;
                    let isEditMode = !hasExistingData;
                    
                    // Comments storage: { sectionId: { 'row,col': 'comment text' } }
                    let sectionComments = {};
                    
                    // Create comment tooltip element
                    const commentTooltip = document.createElement('div');
                    commentTooltip.id = 'penawaran-comment-tooltip';
                    commentTooltip.style.cssText = `
                        position: fixed;
                        background: #fffde7;
                        border: 1px solid #fbc02d;
                        border-radius: 4px;
                        padding: 8px 12px;
                        font-size: 12px;
                        max-width: 250px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                        z-index: 10000;
                        display: none;
                        pointer-events: none;
                        word-wrap: break-word;
                    `;
                    document.body.appendChild(commentTooltip);
                    
                    // Comment Modal Helper Functions
                    let commentModalCallback = null;
                    
                    function openCommentModal(title, initialValue, callback) {
                        const modal = document.getElementById('commentModal');
                        const titleEl = document.getElementById('commentModalTitle');
                        const textarea = document.getElementById('commentModalTextarea');
                        
                        titleEl.textContent = title || 'Insert Comment';
                        textarea.value = initialValue || '';
                        commentModalCallback = callback;
                        
                        modal.classList.remove('hidden');
                        textarea.focus();
                    }
                    
                    function closeCommentModal() {
                        const modal = document.getElementById('commentModal');
                        modal.classList.add('hidden');
                        commentModalCallback = null;
                    }
                    
                    // Comment Modal Event Listeners
                    document.getElementById('commentModalCancel').addEventListener('click', closeCommentModal);
                    document.getElementById('commentModalOk').addEventListener('click', function() {
                        const textarea = document.getElementById('commentModalTextarea');
                        const value = textarea.value;
                        if (commentModalCallback) {
                            commentModalCallback(value);
                        }
                        closeCommentModal();
                    });
                    document.getElementById('commentModal').addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeCommentModal();
                        }
                    });
                    document.getElementById('commentModalTextarea').addEventListener('keydown', function(e) {
                        // Allow Ctrl+Enter to submit
                        if (e.ctrlKey && e.key === 'Enter') {
                            document.getElementById('commentModalOk').click();
                        }
                        // Allow Escape to cancel
                        if (e.key === 'Escape') {
                            closeCommentModal();
                        }
                    });
                    
                    function viewCommentModal(comment) {
                        const modal = document.getElementById('commentModal');
                        const titleEl = document.getElementById('commentModalTitle');
                        const textarea = document.getElementById('commentModalTextarea');
                        const okBtn = document.getElementById('commentModalOk');
                        const cancelBtn = document.getElementById('commentModalCancel');
                        
                        titleEl.textContent = 'View Comment';
                        textarea.value = comment || '';
                        textarea.readOnly = true;
                        textarea.style.backgroundColor = '#f5f5f5';
                        okBtn.style.display = 'none';
                        cancelBtn.textContent = 'Tutup';
                        commentModalCallback = null;
                        
                        modal.classList.remove('hidden');
                        
                        // Reset on close
                        const resetViewMode = function() {
                            textarea.readOnly = false;
                            textarea.style.backgroundColor = '';
                            okBtn.style.display = '';
                            cancelBtn.textContent = 'Batal';
                        };
                        
                        const originalCloseHandler = closeCommentModal;
                        closeCommentModal = function() {
                            resetViewMode();
                            const modalEl = document.getElementById('commentModal');
                            modalEl.classList.add('hidden');
                            commentModalCallback = null;
                            closeCommentModal = originalCloseHandler;
                        };
                    }
                    
                    // Comment helper functions
                    function getComment(sectionId, row, col) {
                        const key = `${row},${col}`;
                        return sectionComments[sectionId]?.[key] || null;
                    }
                    
                    function setComment(sectionId, row, col, comment) {
                        console.log('📝 setComment called:', { sectionId, row, col, comment });
                        if (!sectionComments[sectionId]) {
                            sectionComments[sectionId] = {};
                        }
                        const key = `${row},${col}`;
                        if (comment && comment.trim()) {
                            sectionComments[sectionId][key] = comment.trim();
                            console.log('✅ Comment stored:', { key, value: comment.trim(), allComments: JSON.stringify(sectionComments) });
                        } else {
                            delete sectionComments[sectionId][key];
                        }
                        updateCommentIndicators(sectionId);
                        if (typeof markAsUnsaved === 'function') {
                            markAsUnsaved();
                        }
                    }
                    
                    function deleteComment(sectionId, row, col) {
                        const key = `${row},${col}`;
                        if (sectionComments[sectionId]) {
                            delete sectionComments[sectionId][key];
                        }
                        updateCommentIndicators(sectionId);
                        if (typeof markAsUnsaved === 'function') {
                            markAsUnsaved();
                        }
                    }
                    
                    function updateCommentIndicators(sectionId) {
                        const sectionElement = document.getElementById(sectionId);
                        if (!sectionElement) return;
                        
                        const cells = sectionElement.querySelectorAll('tbody td');
                        cells.forEach(cell => {
                            // Remove existing indicator
                            const existingIndicator = cell.querySelector('.comment-indicator');
                            if (existingIndicator) existingIndicator.remove();
                            
                            // Get cell row/col
                            const row = cell.dataset.y;
                            const col = cell.dataset.x;
                            if (row === undefined || col === undefined) return;
                            
                            const comment = getComment(sectionId, parseInt(row), parseInt(col));
                            if (comment) {
                                // Add red triangle indicator
                                const indicator = document.createElement('div');
                                indicator.className = 'comment-indicator';
                                indicator.style.cssText = `
                                    position: absolute;
                                    top: 0;
                                    right: 0;
                                    width: 0;
                                    height: 0;
                                    border-left: 8px solid transparent;
                                    border-top: 8px solid #dc2626;
                                    pointer-events: none;
                                `;
                                cell.style.position = 'relative';
                                cell.appendChild(indicator);
                            }
                        });
                    }
                    
                    function bindCommentHoverEvents(sectionId) {
                        const sectionElement = document.getElementById(sectionId);
                        if (!sectionElement) return;
                        
                        setTimeout(() => {
                            const cells = sectionElement.querySelectorAll('tbody td');
                            cells.forEach(cell => {
                                cell.addEventListener('mouseenter', function(e) {
                                    const row = this.dataset.y;
                                    const col = this.dataset.x;
                                    if (row === undefined || col === undefined) return;
                                    
                                    const comment = getComment(sectionId, parseInt(row), parseInt(col));
                                    if (comment) {
                                        commentTooltip.textContent = comment;
                                        commentTooltip.style.display = 'block';
                                        commentTooltip.style.left = (e.clientX + 10) + 'px';
                                        commentTooltip.style.top = (e.clientY + 10) + 'px';
                                    }
                                });
                                
                                cell.addEventListener('mousemove', function(e) {
                                    if (commentTooltip.style.display === 'block') {
                                        commentTooltip.style.left = (e.clientX + 10) + 'px';
                                        commentTooltip.style.top = (e.clientY + 10) + 'px';
                                    }
                                });
                                
                                cell.addEventListener('mouseleave', function() {
                                    commentTooltip.style.display = 'none';
                                });
                            });
                            
                            updateCommentIndicators(sectionId);
                        }, 200);
                    }
                    
                    // Load comments from row data
                    function loadCommentsFromSectionData(sectionId, sectionData) {
                        if (!sectionData || !sectionData.data) return;
                        sectionComments[sectionId] = {};
                        sectionData.data.forEach((row, rowIndex) => {
                            if (row.comments && typeof row.comments === 'object') {
                                Object.keys(row.comments).forEach(col => {
                                    const key = `${rowIndex},${col}`;
                                    sectionComments[sectionId][key] = row.comments[col];
                                });
                            }
                        });
                    }
                    
                    // Convert sectionComments to per-row format for saving
                    function getCommentsForRow(sectionId, rowIndex) {
                        const comments = {};
                        if (!sectionComments[sectionId]) return null;
                        
                        Object.keys(sectionComments[sectionId]).forEach(key => {
                            const [row, col] = key.split(',').map(Number);
                            if (row === rowIndex) {
                                comments[col] = sectionComments[sectionId][key];
                            }
                        });
                        
                        return Object.keys(comments).length > 0 ? comments : null;
                    }

                    // Variabel Jasa
                    let jasaSections = [];
                    let jasaSectionCounter = 0;
                    let jasaInitialSections = [];
                    let jasaProfit = 0;
                    let jasaPph = 0;
                    let jasaIsEditMode = false;
                    
                    // Jasa Comments storage: { sectionId: { 'row,col': 'comment text' } }
                    let jasaComments = {};
                    
                    // Jasa Comment helper functions
                    function getJasaComment(sectionId, row, col) {
                        const key = `${row},${col}`;
                        return jasaComments[sectionId]?.[key] || null;
                    }
                    
                    function setJasaComment(sectionId, row, col, comment) {
                        console.log('📝 setJasaComment called:', { sectionId, row, col, comment });
                        if (!jasaComments[sectionId]) {
                            jasaComments[sectionId] = {};
                        }
                        const key = `${row},${col}`;
                        if (comment && comment.trim()) {
                            jasaComments[sectionId][key] = comment.trim();
                            console.log('✅ Jasa Comment stored:', { key, value: comment.trim() });
                        } else {
                            delete jasaComments[sectionId][key];
                        }
                        updateJasaCommentIndicators(sectionId);
                        if (typeof markAsUnsaved === 'function') {
                            markAsUnsaved();
                        }
                    }
                    
                    function deleteJasaComment(sectionId, row, col) {
                        const key = `${row},${col}`;
                        if (jasaComments[sectionId]) {
                            delete jasaComments[sectionId][key];
                        }
                        updateJasaCommentIndicators(sectionId);
                        if (typeof markAsUnsaved === 'function') {
                            markAsUnsaved();
                        }
                    }
                    
                    function updateJasaCommentIndicators(sectionId) {
                        const sectionElement = document.getElementById(sectionId);
                        if (!sectionElement) return;
                        
                        const cells = sectionElement.querySelectorAll('tbody td');
                        cells.forEach(cell => {
                            const existingIndicator = cell.querySelector('.comment-indicator');
                            if (existingIndicator) existingIndicator.remove();
                            
                            const row = cell.dataset.y;
                            const col = cell.dataset.x;
                            if (row === undefined || col === undefined) return;
                            
                            const comment = getJasaComment(sectionId, parseInt(row), parseInt(col));
                            if (comment) {
                                const indicator = document.createElement('div');
                                indicator.className = 'comment-indicator';
                                indicator.style.cssText = `
                                    position: absolute;
                                    top: 0;
                                    right: 0;
                                    width: 0;
                                    height: 0;
                                    border-left: 8px solid transparent;
                                    border-top: 8px solid #dc2626;
                                    pointer-events: none;
                                `;
                                cell.style.position = 'relative';
                                cell.appendChild(indicator);
                            }
                        });
                    }
                    
                    function bindJasaCommentHoverEvents(sectionId) {
                        const sectionElement = document.getElementById(sectionId);
                        if (!sectionElement) return;
                        
                        setTimeout(() => {
                            const cells = sectionElement.querySelectorAll('tbody td');
                            cells.forEach(cell => {
                                cell.addEventListener('mouseenter', function(e) {
                                    const row = this.dataset.y;
                                    const col = this.dataset.x;
                                    if (row === undefined || col === undefined) return;
                                    
                                    const comment = getJasaComment(sectionId, parseInt(row), parseInt(col));
                                    if (comment) {
                                        commentTooltip.textContent = comment;
                                        commentTooltip.style.display = 'block';
                                        commentTooltip.style.left = (e.clientX + 10) + 'px';
                                        commentTooltip.style.top = (e.clientY + 10) + 'px';
                                    }
                                });
                                
                                cell.addEventListener('mousemove', function(e) {
                                    if (commentTooltip.style.display === 'block') {
                                        commentTooltip.style.left = (e.clientX + 10) + 'px';
                                        commentTooltip.style.top = (e.clientY + 10) + 'px';
                                    }
                                });
                                
                                cell.addEventListener('mouseleave', function() {
                                    commentTooltip.style.display = 'none';
                                });
                            });
                            
                            updateJasaCommentIndicators(sectionId);
                        }, 200);
                    }
                    
                    function loadJasaCommentsFromSectionData(sectionId, sectionData) {
                        if (!sectionData || !sectionData.data) return;
                        jasaComments[sectionId] = {};
                        sectionData.data.forEach((row, rowIndex) => {
                            if (row.comments && typeof row.comments === 'object') {
                                Object.keys(row.comments).forEach(col => {
                                    const key = `${rowIndex},${col}`;
                                    jasaComments[sectionId][key] = row.comments[col];
                                });
                            }
                        });
                    }
                    
                    function getJasaCommentsForRow(sectionId, rowIndex) {
                        const comments = {};
                        if (!jasaComments[sectionId]) return null;
                        
                        Object.keys(jasaComments[sectionId]).forEach(key => {
                            const [row, col] = key.split(',').map(Number);
                            if (row === rowIndex) {
                                comments[col] = jasaComments[sectionId][key];
                            }
                        });
                        
                        return Object.keys(comments).length > 0 ? comments : null;
                    }
                    
                    // Flags untuk tracking status validasi tab
                    let penawaranSaved = hasExistingData; // Penawaran sudah valid jika ada existing data
                    let jasaSaved = false;
                    let jasaHasExistingData = false;

                    // =====================================================
                    // FUNGSI UPDATE TAB STATES
                    // =====================================================
                    function updateTabStates() {
                        const tabButtons = document.querySelectorAll('.tab-btn');
                        tabButtons.forEach(btn => {
                            const tab = btn.getAttribute('data-tab');
                            btn.classList.remove('locked');
                            // Lock Jasa only if Penawaran tab is shown and not yet saved
                            if (tab === 'Jasa' && showPenawaran && !penawaranSaved) {
                                btn.classList.add('locked');
                            } else if (tab === 'preview') {
                                // Preview requirements depend on tipe
                                if (!tipe) {
                                    if (!penawaranSaved || !jasaSaved) btn.classList.add('locked');
                                } else if (tipe === 'soc') {
                                    if (!jasaSaved) btn.classList.add('locked');
                                } else if (tipe === 'barang') {
                                    if (!penawaranSaved) btn.classList.add('locked');
                                }
                            }
                        });
                    }

                    // =====================================================
                    // FUNGSI VALIDASI PENAWARAN LENGKAP
                    // =====================================================
                    function isPenawaranComplete() {
                        // Check if all sections have data
                        if (sections.length === 0) return false;
                        
                        for (const section of sections) {
                            const sectionElement = document.getElementById(section.id);
                            if (!sectionElement) continue;
                            
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const rawData = section.spreadsheet.getData();
                            
                            // Check area dan nama section tidak kosong
                            if (!namaSectionInput.value || namaSectionInput.value.trim() === '') return false;
                            
                            // Check minimal ada 1 baris data yang valid
                            let hasValidRow = false;
                            for (const row of rawData) {
                                const hasSignificantData = row.some((cell, idx) => {
                                    if ([0, 1, 2, 4].includes(idx)) return cell && String(cell).trim() !== '';
                                    if ([3, 5, 6, 7, 11].includes(idx)) return parseNumber(cell) > 0;
                                    return false;
                                });
                                
                                if (hasSignificantData) {
                                    const tipe = row[1];
                                    const deskripsi = String(row[2] || '').trim();
                                    const qty = parseNumber(row[3]);
                                    const satuan = String(row[4] || '').trim();
                                    const hpp = parseNumber(row[7]);
                                    const profit = parseNumber(row[9]);
                                    const warna = row[10];
                                    
                                    // All required fields must be filled
                                    if (tipe && deskripsi && qty > 0 && satuan && hpp > 0 && profit >= 0 && warna) {
                                        hasValidRow = true;
                                        break;
                                    }
                                }
                            }
                            
                            if (!hasValidRow) return false;
                        }
                        
                        return true;
                    }

                    // =====================================================
                    // FUNGSI VALIDASI DETAIL (UNTUK TOASTER SPESIFIK)
                    // =====================================================
                    function getPenawaranValidationErrors() {
                        const errors = [];

                        if (!sections || sections.length === 0) {
                            errors.push('Belum ada baris data yang diisi di Penawaran');
                            return errors;
                        }

                        sections.forEach((section, sectionIdx) => {
                            const sectionElement = document.getElementById(section.id);
                            if (!sectionElement || !section.spreadsheet) return;

                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const rawData = section.spreadsheet.getData() || [];

                            const sectionNumber = sectionIdx + 1;

                            // Nama section (kolom Section) wajib
                            if (!namaSectionInput || !namaSectionInput.value || namaSectionInput.value.trim() === '') {
                                errors.push(`Section ${sectionNumber} Kolom Section belum diisi`);
                            }

                            // Kalau semua baris benar-benar kosong (tidak ada satu cell pun yang terisi),
                            // langsung anggap section ini belum punya data sama sekali
                            const allRowsEmpty = rawData.length === 0 || rawData.every(row => {
                                if (!row) return true;
                                return row.every(cell => {
                                    if (cell === null || cell === undefined) return true;
                                    const text = String(cell).trim();
                                    // Anggap kosong jika string kosong / 0 / false dan nilai numeriknya 0
                                    if (text === '' || text === '0' || text.toLowerCase() === 'false') {
                                        return true;
                                    }
                                    return parseNumber(cell) === 0;
                                });
                            });

                            if (allRowsEmpty) {
                                errors.push(`Section ${sectionNumber} belum ada baris data yang diisi`);
                                return; // tidak perlu cek per kolom
                            }

                            // Definisi kolom yang wajib per baris (hanya akan dicek jika ada baris berisi)
                            // Index: 0=No, 1=Tipe, 2=Deskripsi, 3=QTY, 4=Satuan, 5=Harga Satuan, 6=Harga Total, 7=HPP, 8=Mitra, 9=Judul, 10=Profit, 11=Warna, 12=Added Cost, 13=Keterangan
                            const requiredDefs = [
                                { index: 1, name: 'Tipe', type: 'numberOrText' },
                                { index: 2, name: 'Deskripsi', type: 'text' },
                                { index: 3, name: 'Qty', type: 'numberPositive' },
                                { index: 4, name: 'Satuan', type: 'text' },
                                { index: 7, name: 'Hpp', type: 'numberPositive' },
                                { index: 10, name: 'Profit', type: 'numberPositive' },
                            ];

                            const missingColumns = new Set();

                            rawData.forEach(row => {
                                if (!row) return;

                                // Anggap hanya baris yang punya isi (minimal satu kolom ada isi) yang perlu dicek detail
                                const hasData = row.some(cell => {
                                    if (cell === null || cell === undefined) return false;
                                    const text = String(cell).trim();
                                    if (text !== '' && text.toLowerCase() !== 'false') return true;
                                    return parseNumber(cell) > 0;
                                });

                                if (!hasData) return;

                                requiredDefs.forEach(def => {
                                    if (missingColumns.has(def.name)) return;
                                    const cellValue = row[def.index];

                                    if (def.type === 'text') {
                                        if (!cellValue || String(cellValue).trim() === '') {
                                            missingColumns.add(def.name);
                                        }
                                    } else if (def.type === 'numberPositive') {
                                        if (parseNumber(cellValue) <= 0) {
                                            missingColumns.add(def.name);
                                        }
                                    } else if (def.type === 'numberOrText') {
                                        if (!cellValue || String(cellValue).trim() === '') {
                                            missingColumns.add(def.name);
                                        }
                                    }
                                });
                            });

                            missingColumns.forEach(colName => {
                                errors.push(`Section ${sectionNumber} Kolom ${colName} belum diisi`);
                            });
                        });

                        return errors;
                    }

                    function getJasaValidationErrors() {
                        const errors = [];

                        if (!jasaSections || jasaSections.length === 0) {
                            errors.push('Belum ada baris data yang diisi di Rincian Jasa');
                            return errors;
                        }

                        jasaSections.forEach((section, sectionIdx) => {
                            const sectionElement = document.getElementById(section.id);
                            if (!sectionElement || !section.spreadsheet) return;

                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const rawData = section.spreadsheet.getData() || [];

                            const sectionNumber = sectionIdx + 1;

                            // Nama section jasa wajib
                            if (!namaSectionInput || !namaSectionInput.value || namaSectionInput.value.trim() === '') {
                                errors.push(`Section Jasa ${sectionNumber} Kolom Section belum diisi`);
                            }

                            // Jika semua baris kosong total, langsung error dan skip detail
                            const allRowsEmpty = rawData.length === 0 || rawData.every(row => {
                                if (!row) return true;
                                return row.every(cell => {
                                    if (cell === null || cell === undefined) return true;
                                    const text = String(cell).trim();
                                    if (text === '' || text === '0' || text.toLowerCase() === 'false') {
                                        return true;
                                    }
                                    return parseNumber(cell) === 0;
                                });
                            });

                            if (allRowsEmpty) {
                                errors.push(`Section Jasa ${sectionNumber} belum ada baris data yang diisi`);
                                return;
                            }

                            let missingDeskripsi = false;
                            let missingUnit = false;

                            rawData.forEach(row => {
                                if (!row) return;

                                const deskripsi = String(row[1] || '').trim();
                                const unit = parseNumber(row[5]);

                                // Skip baris yang benar-benar kosong
                                const isRowEmpty = (!deskripsi && (!unit || unit === 0));
                                if (isRowEmpty) return;

                                if (!deskripsi) missingDeskripsi = true;
                                if (!unit || isNaN(unit) || unit <= 0) missingUnit = true;
                            });

                            if (missingDeskripsi) {
                                errors.push(`Section Jasa ${sectionNumber} Kolom Deskripsi belum diisi`);
                            }
                            if (missingUnit) {
                                errors.push(`Section Jasa ${sectionNumber} Kolom Unit belum diisi`);
                            }
                        });

                        return errors;
                    }

                    // Expose untuk dipakai di slider verifikasi
                    window.getPenawaranValidationErrors = getPenawaranValidationErrors;
                    window.getJasaValidationErrors = getJasaValidationErrors;

                    // =====================================================
                    // TAB SWITCHING LOGIC
                    // =====================================================

                    const tabButtons = document.querySelectorAll('.tab-btn');
                    const tabPanels = document.querySelectorAll('.tab-panel');

                    tabButtons.forEach(button => {
                        button.addEventListener('click', function () {
                            const targetTab = this.getAttribute('data-tab');

                            // Validasi sebelum switch tab
                            if (targetTab === 'Jasa' && showPenawaran && !penawaranSaved) {
                                if (!isPenawaranComplete()) {
                                    notyf.error('⚠️ Silakan lengkapi dan simpan data Penawaran terlebih dahulu!');
                                    return;
                                }
                            }

                            if (targetTab === 'preview') {
                                let errorMsg = '';
                                if (!tipe) {
                                    if (!penawaranSaved) errorMsg = '⚠️ Silakan lengkapi dan simpan data Penawaran terlebih dahulu!';
                                    else if (!jasaSaved) errorMsg = '⚠️ Silakan lengkapi dan simpan data Rincian Jasa terlebih dahulu!';
                                } else if (tipe === 'soc') {
                                    if (!jasaSaved) errorMsg = '⚠️ Silakan lengkapi dan simpan data Rincian Jasa terlebih dahulu!';
                                } else if (tipe === 'barang') {
                                    if (!penawaranSaved) errorMsg = '⚠️ Silakan lengkapi dan simpan data Penawaran terlebih dahulu!';
                                }
                                if (errorMsg) {
                                    notyf.error(errorMsg);
                                    return;
                                }
                            }

                            if (targetTab === 'rekap') {
                                setTimeout(() => {
                                    if (window.rekapSpreadsheet) {
                                        if (typeof window.rekapSpreadsheet.refresh === 'function') {
                                            window.rekapSpreadsheet.refresh();
                                        } else if (typeof window.rekapSpreadsheet.render === 'function') {
                                            window.rekapSpreadsheet.render();
                                        }
                                    }
                                }, 50);
                            }

                            

                            // Save current tab to localStorage
                            localStorage.setItem(`penawaran_active_tab_${activeVersion}`, targetTab);

                            // Update button styles
                            tabButtons.forEach(btn => {
                                btn.classList.remove('text-green-600', 'border-b-2',
                                    'border-green-600');
                                btn.classList.add('text-gray-600');
                            });
                            this.classList.remove('text-gray-600');
                            this.classList.add('text-green-600', 'border-b-2', 'border-green-600');

                            // Show/hide panels
                            tabPanels.forEach(panel => {
                                if (panel.getAttribute('data-tab') === targetTab) {
                                    panel.classList.remove('hidden');
                                } else {
                                    panel.classList.add('hidden');
                                }
                            });

                            if (targetTab === 'preview') {
                                // Tell the slider logic that preview became visible
                                document.dispatchEvent(new CustomEvent('previewTabShown'));
                            }

                            // Jasa data sudah di-load saat page load, jadi tidak perlu load lagi
                        });
                    });

                    // =====================================================
                    // UTILITY FUNCTIONS
                    // =====================================================

                    function parseNumber(value) {
                        if (typeof value === "string") {
                            value = value.trim();

                            // Handle currency format (Rp 123.456 atau Rp 123,456)
                            if (value.includes('Rp')) {
                                value = value.replace(/Rp\s?/g, ''); // Hapus 'Rp '
                            }

                            // Handle format Indonesia: titik sebagai pemisah ribuan, koma sebagai desimal
                            if (value.indexOf('.') !== -1 && value.indexOf(',') !== -1) {
                                value = value.replace(/\./g, '').replace(/,/g, '.');
                            } else if (value.indexOf(',') !== -1) {
                                // Jika hanya ada koma, anggap sebagai desimal
                                value = value.replace(/,/g, '.');
                            } else {
                                // Hapus titik jika digunakan sebagai pemisah ribuan
                                const dotCount = (value.match(/\./g) || []).length;
                                if (dotCount === 1 && value.indexOf('.') > value.length - 4) {
                                    // Jika ada satu titik di 3 digit terakhir, anggap desimal
                                    // Biarkan apa adanya
                                } else {
                                    // Hapus semua titik (pemisah ribuan)
                                    value = value.replace(/\./g, '');
                                }
                            }
                        }
                        const result = parseFloat(value) || 0;
                        return result;
                    }

                    // =====================================================
                    // FUNGSI JASA
                    // =====================================================

                    // Paste kode ini ke dalam tag <script> di bagian FUNGSI JASA

                    const jasaDetailUrl = "{{ route('jasa.detail') }}";

                    function loadJasaData() {
                        return new Promise((resolve) => {
                            const penawaranId = {{ $penawaran->id_penawaran }};

                            fetch(`${jasaDetailUrl}?id=${penawaranId}&version=${activeVersion}`)
                                .then(res => {
                                    if (!res.ok) throw new Error('Network response was not ok');
                                    return res.json();
                                })
                            .then(data => {
                                jasaInitialSections = data.sections || [];
                                jasaProfit = data.profit || 0;
                                jasaPph = data.pph || 0;
                                jasaHasExistingData = jasaInitialSections.length > 0;
                                
                                // Set jasaSaved flag jika ada data jasa yang sudah ada
                                if (jasaHasExistingData) {
                                    jasaSaved = true;
                                    updateTabStates();
                                }

                                const jasaProfitInputEl = document.getElementById('jasaProfitInput');
                                if (jasaProfitInputEl) jasaProfitInputEl.value = jasaProfit;
                                const jasaPphInputEl = document.getElementById('jasaPphInput');
                                if (jasaPphInputEl) jasaPphInputEl.value = jasaPph;

                                if (jasaHasExistingData) {
                                    // VIEW MODE
                                    jasaInitialSections.forEach(section => {
                                        if (section.data && Array.isArray(section.data)) {
                                            section.data = section.data.map(row => ({
                                                ...row,
                                                id_jasa_detail: row.id_jasa_detail || null
                                            }));
                                        }
                                        createJasaSection(section, false);
                                    });
                                    toggleJasaEditMode(false);
                                } else {
                                    // NEW DATA → langsung EDIT MODE + buat 1 section kosong
                                    createJasaSection(null, true);
                                    toggleJasaEditMode(true);
                                }
                            })
                            .catch(err => {
                                console.error('Load jasa failed:', err);
                                // Anggap tidak ada data → langsung NEW (edit)
                                if (jasaSections.length === 0) {
                                    createJasaSection(null, true);
                                }
                                jasaHasExistingData = false;
                                toggleJasaEditMode(true);
                            })
                            .finally(() => {
                                resolve();
                            });
                        });
                    }

                    const jasaAddSectionBtn = document.getElementById('jasaAddSectionBtn');
                    if (jasaAddSectionBtn) {
                        jasaAddSectionBtn.addEventListener('click', () => {
                            createJasaSection(null, jasaIsEditMode);
                        });
                    }

                    const jasaEditModeBtnMain = document.getElementById('jasaEditModeBtn');
                    if (jasaEditModeBtnMain) {
                        jasaEditModeBtnMain.addEventListener('click', () => {
                            if (jasaSections.length === 0) {
                                // buat satu section kosong ketika belum ada data
                                createJasaSection(null, true);
                            }
                            toggleJasaEditMode(true);
                        });
                    }

                    const jasaCancelEditBtnMain = document.getElementById('jasaCancelEditBtn');
                    if (jasaCancelEditBtnMain) {
                        jasaCancelEditBtnMain.addEventListener('click', () => {
                            if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                                window.location.reload();
                            }
                        });
                    }

                    function toggleJasaEditMode(enable) {
                        jasaIsEditMode = enable;

                        const btnEdit = document.getElementById('jasaEditModeBtn');
                        const btnCancel = document.getElementById('jasaCancelEditBtn');
                        const btnSave = document.getElementById('jasaSaveAllBtn');
                        const btnAdd = document.getElementById('jasaAddSectionBtn');

                        if (jasaHasExistingData) {
                            if (btnEdit) btnEdit.classList.toggle('hidden', enable);
                            if (btnCancel) btnCancel.classList.toggle('hidden', !enable);
                        } else {
                            // Tidak ada data → tidak perlu tombol Edit / Batal
                            if (btnEdit) btnEdit.classList.add('hidden');
                            if (btnCancel) btnCancel.classList.add('hidden');
                        }

                        // Save selalu tampil (sama seperti Penawaran)
                        if (btnSave) btnSave.classList.remove('hidden');
                        // Add Section hanya saat edit
                        if (btnAdd) btnAdd.classList.toggle('hidden', !enable);

                        const profitInput = document.getElementById('jasaProfitInput');
                        if (profitInput) profitInput.disabled = !enable;
                        const pphInput = document.getElementById('jasaPphInput');
                        if (pphInput) pphInput.disabled = !enable;

                        jasaSections.forEach(section => {
                            const sectionElement = document.getElementById(section.id);
                            const spreadsheetWrapper = document.getElementById(section.spreadsheetId);
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const addRowBtn = sectionElement.querySelector('.add-row-btn');
                            const deleteSectionBtn = sectionElement.querySelector('.delete-section-btn');
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');

                            spreadsheetWrapper.classList.toggle('spreadsheet-disabled', !enable);
                            section.spreadsheet.options.editable = enable;

                            namaSectionInput.disabled = !enable;
                            addRowBtn.classList.toggle('hidden', !enable);
                            deleteSectionBtn.classList.toggle('hidden', !enable);

                            if (!pembulatanInput._binded) {
                                pembulatanInput.addEventListener('input', updateJasaOverallSummary);
                                pembulatanInput._binded = true;
                            }
                        });
                    }

                    if (jasaEditModeBtnMain) {
                        jasaEditModeBtnMain.addEventListener('click', () => {
                            toggleJasaEditMode(true);
                        });
                    }

                    if (jasaCancelEditBtnMain) {
                        jasaCancelEditBtnMain.addEventListener('click', () => {
                            if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                                window.location.reload();
                            }
                        });
                    }

                    function recalcJasaRow(spreadsheet, rowIndex) {
                        // Ambil data fresh dari spreadsheet
                        const row = spreadsheet.getRowData(rowIndex);

                        const vol = parseNumber(row[2]);
                        const hari = parseNumber(row[3]);
                        const orang = parseNumber(row[4]);
                        const unit = parseNumber(row[5]);

                        console.log('🧮 recalcJasaRow:', {
                            rowIndex,
                            vol,
                            hari,
                            orang,
                            unit,
                            rawRow: row
                        });

                        // Jika unit kosong atau 0, total = 0
                        if (!unit || unit === 0) {
                            console.log('⚠️ Unit is 0 or empty, setting total to 0');
                            spreadsheet.setValueFromCoords(6, rowIndex, 0, true);
                            const section = jasaSections.find(s => s.spreadsheet === spreadsheet);
                            if (section) updateJasaSubtotal(section);
                            return;
                        }

                        let total = unit; // Mulai dari nilai unit

                        // Kalikan dengan faktor-faktor yang ada (> 0)
                        if (vol > 0) total *= vol;
                        if (hari > 0) total *= hari;
                        if (orang > 0) total *= orang;

                        console.log('💰 Calculated total:', {
                            unit,
                            vol,
                            hari,
                            orang,
                            formula: `${unit}${vol > 0 ? ' × ' + vol : ''}${hari > 0 ? ' × ' + hari : ''}${orang > 0 ? ' × ' + orang : ''}`,
                            result: total
                        });

                        // Set value dengan force render
                        spreadsheet.setValueFromCoords(6, rowIndex, total, true);

                        // Update subtotal untuk section ini
                        const section = jasaSections.find(s => s.spreadsheet === spreadsheet);
                        if (section) {
                            updateJasaSubtotal(section);
                        }
                    }

                    function createJasaSection(sectionData = null, editable = true) {
                        jasaSectionCounter++;
                        const sectionId = 'jasa-section-' + jasaSectionCounter;
                        const spreadsheetId = 'jasa-spreadsheet-' + jasaSectionCounter;

                        const initialData = sectionData ? sectionData.data.map(row => [
                            row.no || '',
                            row.deskripsi || '',
                            row.vol || 0,
                            row.hari || 0,
                            row.orang || 0,
                            row.unit || 0,
                            row.total || 0,
                            row.id_jasa_detail || null
                        ]) : [
                            ['', '', 0, 0, 0, 0, 0, null],
                            ['', '', 0, 0, 0, 0, 0, null],
                        ];

                        const sectionHTML = `
                                    <div class="section-card p-4 mb-6 bg-white" id="${sectionId}">
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-4">
                                                <h3 class="text-lg font-bold text-gray-700">Section Jasa ${jasaSectionCounter}</h3>
                                                <input type="text" class="nama-section-input border rounded px-3 py-1" 
                                                    placeholder="Ex: Pekerjaan Instalasi" 
                                                    value="${sectionData && sectionData.nama_section ? sectionData.nama_section : ''}">
                                            </div>
                                            <div class="flex items-center ml-4">
                                                <label class="block text-sm font-semibold mr-2">Pembulatan:</label>
                                                <input type="number" class="pembulatan-input border rounded px-3 py-1 w-48" 
                                                    min="1" step="1" value="${sectionData && typeof sectionData.pembulatan !== 'undefined' ? sectionData.pembulatan : 1}">
                                            </div>
                                            <div class="flex gap-2">
                                                <button class="flex items-center add-row-btn bg-[#02ADB8] text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Tambah Baris
                                                </button>
                                                <button class="flex items-center delete-row-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Hapus Baris
                                                </button>
                                                <button class="delete-section-btn bg-white text-red-500 px-3 py-1 rounded hover:bg-red-500 hover:text-white transition text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="spreadsheet-scroll-wrapper" style="overflow-x:auto;">
                                            <div id="${spreadsheetId}"></div>
                                        </div>

                                        <div class="mt-3 flex items-start">
                                            <div class="flex-1"></div>
                                            <div class="w-full lg:w-56 flex flex-col items-end text-right space-y-1">
                                                <div class="text-right font-semibold">Subtotal: Rp <span id="${sectionId}-subtotal">0</span></div>
                                                <div class="text-sm"><span class="${sectionId}-profit-label">Profit:</span> Rp <span class="${sectionId}-profit-val">0</span></div>
                                                <div class="text-sm"><span class="${sectionId}-pph-label">PPH:</span> Rp <span class="${sectionId}-pph-val">0</span></div>
                                                <div class="text-sm">Pembulatan: Rp <span class="${sectionId}-pembulatan-val">0</span></div>
                                            </div>
                                        </div>
                                    </div>`;

                        document.getElementById('jasaSectionsContainer').insertAdjacentHTML('beforeend', sectionHTML);

                        const spreadsheet = jspreadsheet(document.getElementById(spreadsheetId), {
                            data: initialData,
                            columns: [{
                                title: 'No',
                                width: 60
                            },
                            {
                                title: 'Deskripsi',
                                width: 250,
                                wordWrap: true
                            },
                            {
                                title: 'Vol',
                                width: 80,
                                type: 'numeric'
                            },
                            {
                                title: 'Hari',
                                width: 80,
                                type: 'numeric'
                            },
                            {
                                title: 'Orang',
                                width: 80,
                                type: 'numeric'
                            },
                            {
                                title: 'Unit',
                                width: 100,
                                type: 'numeric',
                                mask: 'Rp #.##0',
                                decimal: ',',
                            },
                            {
                                title: 'Total',
                                width: 120,
                                type: 'numeric',
                                readOnly: true,
                                mask: 'Rp #.##0',
                                decimal: ',',
                            },
                            {
                                title: 'ID',
                                width: 0,
                                type: 'hidden'
                            },
                            ],
                            tableOverflow: true,
                            tableWidth: '100%',
                            tableHeight: '100%',
                            editable: editable,
                            onchange: function (instance, cell, col, row, value) {
                                if (col >= 2 && col <= 5) {
                                    setTimeout(() => recalcJasaRow(spreadsheet, row), 50);
                                }
                            },
                            onafterchanges: function (instance, records) {
                                const rowsToRecalc = new Set();
                                records.forEach(r => {
                                    if (r.x >= 2 && r.x <= 5) rowsToRecalc.add(r.y);
                                });
                                rowsToRecalc.forEach(r => setTimeout(() => recalcJasaRow(spreadsheet, r), 50));
                            },
                            onselection: function (instance, x1, y1, x2, y2, origin) {
                                scrollToSelectedCell(instance, x2, y2);
                            },
                            contextMenu: function(obj, x, y, e, items) {
                                // Get existing comment for current cell
                                const existingComment = getJasaComment(sectionId, y, x);
                                
                                // Build new items array with comment options first
                                let newItems = [];
                                
                                if (existingComment) {
                                    newItems.push({
                                        title: 'View Comment',
                                        onclick: function() {
                                            viewCommentModal(existingComment);
                                        }
                                    });
                                    newItems.push({
                                        title: 'Edit Comment',
                                        onclick: function() {
                                            openCommentModal('Edit Comment', existingComment, function(newComment) {
                                                if (newComment !== null) {
                                                    setJasaComment(sectionId, y, x, newComment);
                                                }
                                            });
                                        }
                                    });
                                    newItems.push({
                                        title: 'Delete Comment',
                                        onclick: function() {
                                            if (confirm('Hapus komentar ini?')) {
                                                deleteJasaComment(sectionId, y, x);
                                            }
                                        }
                                    });
                                } else {
                                    newItems.push({
                                        title: 'Insert Comment',
                                        onclick: function() {
                                            openCommentModal('Insert Comment', '', function(comment) {
                                                if (comment) {
                                                    setJasaComment(sectionId, y, x, comment);
                                                }
                                            });
                                        }
                                    });
                                }
                                
                                // Add separator
                                newItems.push({ type: 'line' });
                                
                                // Build default jspreadsheet menu items manually
                                newItems.push({
                                    title: 'Insert a new row before',
                                    onclick: function() {
                                        // y is the row index where context menu was clicked
                                        // insertRow(quantity, index, insertBefore=true)
                                        obj.insertRow(1, parseInt(y), true);
                                    }
                                });
                                newItems.push({
                                    title: 'Insert a new row after',
                                    onclick: function() {
                                        // Insert after means insertBefore=false, so insert at y+1
                                        obj.insertRow(1, parseInt(y) + 1, false);
                                    }
                                });
                                newItems.push({
                                    title: 'Delete selected rows',
                                    onclick: function() {
                                        obj.deleteRow(obj.getSelectedRows().length ? undefined : y);
                                    }
                                });
                                newItems.push({ type: 'line' });
                                newItems.push({
                                    title: 'Copy',
                                    shortcut: 'Ctrl + C',
                                    onclick: function() {
                                        obj.copy(true);
                                    }
                                });
                                newItems.push({
                                    title: 'Paste',
                                    shortcut: 'Ctrl + V',
                                    onclick: function() {
                                        if (obj.selectedCell) {
                                            navigator.clipboard.readText().then(function(text) {
                                                obj.paste(obj.selectedCell[0], obj.selectedCell[1], text);
                                            });
                                        }
                                    }
                                });
                                
                                return newItems;
                            }
                        });
                        
                        // Bind comment hover events for jasa
                        bindJasaCommentHoverEvents(sectionId);
                        
                        // Load comments if section has data
                        if (sectionData) {
                            loadJasaCommentsFromSectionData(sectionId, sectionData);
                        }

                        const sectionElement = document.getElementById(sectionId);

                        sectionElement.querySelector('.add-row-btn').addEventListener('click', () => {
                            spreadsheet.insertRow(['', '', 0, 0, 0, 0, 0, null]);
                        });

                        sectionElement.querySelector('.delete-section-btn').addEventListener('click', () => {
                            if (confirm('Yakin ingin menghapus section jasa ini?')) {
                                jasaSections = jasaSections.filter(s => s.id !== sectionId);
                                sectionElement.remove();
                                updateJasaOverallSummary();
                                renumberJasaSections();
                            }
                        });

                        // Event listener pembulatan: update summary setiap kali input berubah
                        sectionElement.querySelector('.pembulatan-input').addEventListener('input',
                            updateJasaOverallSummary);

                        // push ke array
                        const sectionObj = {
                            id: sectionId,
                            spreadsheetId,
                            spreadsheet
                        };
                        jasaSections.push(sectionObj);

                        renumberJasaSections();

                        // Kalkulasi awal
                        setTimeout(() => {
                            const totalRows = spreadsheet.getData().length;
                            for (let i = 0; i < totalRows; i++) recalcJasaRow(spreadsheet, i);
                            computeJasaSectionTotals(sectionObj);
                            updateJasaOverallSummary();
                        }, 100);
                    }

                    function updateJasaSubtotal(section) {
                        const data = section.spreadsheet.getData();
                        let subtotal = 0;

                        data.forEach(row => {
                            const total = parseNumber(row[6]);
                            subtotal += total;
                            console.log('   Row total:', total, 'Running subtotal:', subtotal);
                        });

                        const subtotalEl = document.getElementById(`${section.id}-subtotal`);
                        if (subtotalEl) {
                            subtotalEl.textContent = subtotal.toLocaleString('id-ID');
                            console.log('💰 Subtotal updated:', subtotal);
                        }

                        // TAMBAHAN: Update total keseluruhan setiap kali subtotal berubah
                        updateTotalKeseluruhan();

                        // TAMBAHAN: hitung ulang nilai profit/pph/grand untuk section ini
                        // (pastikan section obj yang dikirim punya struktur {id, spreadsheet, spreadsheetId})
                        try {
                            computeJasaSectionTotals(section);
                        } catch (err) {
                            console.warn('computeJasaSectionTotals failed for', section, err);
                        }
                    }

                    // ...existing code...
                    function computeJasaSectionTotals(section) {
                        const subtotalEl = document.getElementById(`${section.id}-subtotal`);
                        const subtotal = subtotalEl ? parseNumber(subtotalEl.textContent.replace(/\./g, '')) : 0;

                        // gunakan formula pembalikan seperti di Excel:
                        // afterProfit = subtotal / (1 - profit%)
                        // afterPph = afterProfit / (1 - pph%)
                        const profitPercent = parseNumber(jasaProfit) || 0;
                        const pphPercent = parseNumber(jasaPph) || 0;

                        // hindari pembagian dengan 0 atau 1
                        const afterProfit = profitPercent > 0 ? (subtotal / (1 - (profitPercent / 100))) : subtotal;
                        const afterPph = pphPercent > 0 ? (afterProfit / (1 - (pphPercent / 100))) : afterProfit;

                        // profit display: afterProfit (sesuai permintaan)
                        // pph display: afterPph (sesuai contoh Excel Anda)
                        // grand per-section = afterPph
                        const profitDisplay = Math.round(afterProfit);
                        const pphDisplay = Math.round(afterPph);
                        const grand = Math.round(afterPph);

                        // update UI
                        const profitLabel = document.querySelector(`#${section.id} .${section.id}-profit-label`);
                        const profitSpan = document.querySelector(`#${section.id} .${section.id}-profit-val`);
                        const pphLabel = document.querySelector(`#${section.id} .${section.id}-pph-label`);
                        const pphSpan = document.querySelector(`#${section.id} .${section.id}-pph-val`);
                        const grandSpan = document.querySelector(`#${section.id} .${section.id}-grand-val`);

                        // Tampilkan label dengan persentase dan nilai nominal di span
                        if (profitLabel) profitLabel.textContent = `Profit ${profitPercent}%:`;
                        if (profitSpan) profitSpan.textContent = profitDisplay.toLocaleString('id-ID');
                        if (pphLabel) pphLabel.textContent = `PPH ${pphPercent}%:`;
                        if (pphSpan) pphSpan.textContent = pphDisplay.toLocaleString('id-ID');
                        if (grandSpan) grandSpan.textContent = grand.toLocaleString('id-ID');

                        // also update overall
                        updateJasaOverallSummary();
                    }

                    function updateJasaOverallSummary() {
                        let totalGrand = 0;
                        jasaSections.forEach(section => {
                            const sectionElement = document.getElementById(section.id);
                            if (!sectionElement) return;
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');
                            const pembulatan = parseInt(pembulatanInput.value) || 0;
                            totalGrand += pembulatan;

                            // Update pembulatan text per section
                            const pembulatanSpan = sectionElement.querySelector(`.${section.id}-pembulatan-val`);
                            if (pembulatanSpan) pembulatanSpan.textContent = pembulatan.toLocaleString('id-ID');
                        });
                        
                        const overallGrandEl = document.getElementById('jasaOverallGrand');
                        if (overallGrandEl) overallGrandEl.textContent = totalGrand.toLocaleString('id-ID');

                        // Hitung BPJS dan Grand Total
                        const jasaUseBpjsEl = document.getElementById('jasaUseBpjs');
                        const useBpjs = jasaUseBpjsEl ? jasaUseBpjsEl.checked : false;

                        // Ambil nilai BPJS dari database (sudah dihitung dengan benar di backend)
                        const bpjsValueFromDb = {{ $versionRow->jasa_bpjsk_value ?? 0 }};
                        const bpjsPercentFromDb = {{ $versionRow->jasa_bpjsk_percent ?? 0 }};
                        const totalPenawaran = {{ $versionRow->penawaran_total_awal ?? 0 }};

                        let bpjsValue = 0;
                        let grandTotal = totalGrand;

                        if (useBpjs) {
                            // Gunakan nilai dari database jika ada, atau hitung ulang dengan formula yang benar
                            if (bpjsValueFromDb > 0) {
                                bpjsValue = bpjsValueFromDb;
                            } else if (bpjsPercentFromDb > 0) {
                                // Hitung dengan formula yang benar: (totalPenawaran + totalJasa) * percent
                                bpjsValue = ((totalPenawaran + totalGrand) * bpjsPercentFromDb) / 100;
                            }
                            grandTotal = totalGrand + bpjsValue;
                        }

                        // Update UI BPJS
                        const bpjsPercentEl = document.getElementById('jasaBpjsPercent');
                        if (bpjsPercentEl) bpjsPercentEl.textContent = bpjsPercentFromDb;

                        const bpjsValueEl = document.getElementById('jasaBpjsValue');
                        if (bpjsValueEl) bpjsValueEl.textContent = Math.round(bpjsValue).toLocaleString('id-ID');

                        const grandTotalEl = document.getElementById('jasaGrandTotal');
                        if (grandTotalEl) grandTotalEl.textContent = Math.round(grandTotal).toLocaleString('id-ID');

                        // Hitung Pembulatan Final
                        const jasaUsePembulatanEl = document.getElementById('jasaUsePembulatan');
                        const usePembulatan = jasaUsePembulatanEl ? jasaUsePembulatanEl.checked : false;
                        const jasaPembulatanInput = document.getElementById('jasaPembulatanInput');
                        
                        let finalTotal = grandTotal;
                        
                        if (usePembulatan && jasaPembulatanInput) {
                            // Parse input value (remove dot separators)
                            const pembulatanValue = parseNumber(jasaPembulatanInput.value) || 0;
                            if (pembulatanValue > 0) {
                                finalTotal = pembulatanValue;
                            }
                        }

                        // Update UI Final Total
                        const finalTotalEl = document.getElementById('jasaFinalTotal');
                        if (finalTotalEl) finalTotalEl.textContent = Math.round(finalTotal).toLocaleString('id-ID');
                    }

                    function renumberJasaSections() {
                        const cards = document.querySelectorAll('#jasaSectionsContainer .section-card');
                        cards.forEach((card, idx) => {
                            const h3 = card.querySelector('h3');
                            if (h3) h3.textContent = `Section Jasa ${idx + 1}`;
                        });
                    }

                    // Input profit jasa - hanya untuk informasi, tidak mempengaruhi perhitungan
                    const jasaProfitInput = document.getElementById('jasaProfitInput');
                    if (jasaProfitInput) {
                        jasaProfitInput.addEventListener('input', function () {
                            jasaProfit = parseNumber(this.value) || 0;
                            jasaSections.forEach(s => computeJasaSectionTotals(s));
                        });
                    }

                    const jasaPphInput = document.getElementById('jasaPphInput');
                    if (jasaPphInput) {
                        jasaPphInput.addEventListener('input', function () {
                            jasaPph = parseNumber(this.value) || 0;
                            jasaSections.forEach(s => computeJasaSectionTotals(s));
                        });
                    }

                    // Switch untuk BPJS
                    const jasaUseBpjsSwitch = document.getElementById('jasaUseBpjs');
                    if (jasaUseBpjsSwitch) {
                        jasaUseBpjsSwitch.addEventListener('change', function () {
                            updateJasaOverallSummary();
                        });
                    }

                    // Switch untuk Pembulatan Final
                    const jasaUsePembulatanSwitch = document.getElementById('jasaUsePembulatan');
                    const jasaPembulatanInputEl = document.getElementById('jasaPembulatanInput');
                    if (jasaUsePembulatanSwitch) {
                        jasaUsePembulatanSwitch.addEventListener('change', function () {
                            // Enable/disable input berdasarkan checkbox
                            if (jasaPembulatanInputEl) {
                                jasaPembulatanInputEl.disabled = !this.checked;
                                if (!this.checked) {
                                    jasaPembulatanInputEl.value = '0';
                                }
                            }
                            updateJasaOverallSummary();
                        });
                    }
                    if (jasaPembulatanInputEl) {
                        jasaPembulatanInputEl.addEventListener('input', function () {
                            // Format input dengan titik sebagai separator ribuan
                            let val = this.value.replace(/\D/g, '');
                            if (val) {
                                this.value = parseInt(val).toLocaleString('id-ID');
                            }
                            updateJasaOverallSummary();
                        });
                    }

                    function dedupeSectionData(section) {
                        const seen = new Set();
                        const filtered = [];
                        (section.data || []).forEach((r, index) => {
                            // Jangan duplikasi jika row kosong
                            const isEmpty = !r.deskripsi && !r.no && !r.vol && !r.hari && !r.orang && !r.unit;
                            if (isEmpty && !r.id_jasa_detail) {
                                return; // Skip row yang benar-benar kosong
                            }
                            
                            // Untuk data yang ada ID (existing data), gunakan ID sebagai unique key
                            if (r.id_jasa_detail && r.id_jasa_detail !== null) {
                                const idKey = `existing_${r.id_jasa_detail}`;
                                if (!seen.has(idKey)) {
                                    seen.add(idKey);
                                    filtered.push(r);
                                }
                            } else {
                                // Untuk data baru (tanpa ID), gunakan kombinasi field + index untuk uniqueness
                                const newKey = `new_${section.nama_section || ''}_${String(r.no || '')}_${String((r.deskripsi || '').trim())}_${String(r.vol || '')}_${String(r.hari || '')}_${String(r.orang || '')}_${String(r.unit || '')}_${index}`;
                                if (!seen.has(newKey)) {
                                    seen.add(newKey);
                                    filtered.push(r);
                                }
                            }
                        });
                        return filtered;
                    }

                    // Tombol simpan jasa
                    const jasaSaveAllBtn = document.getElementById('jasaSaveAllBtn');
                    if (jasaSaveAllBtn) {
                        jasaSaveAllBtn.addEventListener('click', () => {
                            const btn = document.getElementById('jasaSaveAllBtn');
                        
                        // Validasi sebelum menyimpan
                        let validationErrors = [];
                        
                        jasaSections.forEach((section, index) => {
                            const sectionElement = document.getElementById(section.id);
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');
                            const rawData = section.spreadsheet.getData();
                            
                            const sectionNumber = index + 1;
                            
                            // Validasi nama section
                            if (!namaSectionInput.value.trim()) {
                                validationErrors.push(`Section ${sectionNumber}: Nama section harus diisi`);
                            }
                            
                            // Validasi pembulatan (harus angka dan tidak boleh 0)
                            const pembulatanValue = parseInt(pembulatanInput.value);
                            if (pembulatanInput.value === '' || isNaN(pembulatanValue)) {
                                validationErrors.push(`Section ${sectionNumber}: Pembulatan harus diisi dengan angka`);
                            } else if (pembulatanValue === 0) {
                                validationErrors.push(`Section ${sectionNumber}: Pembulatan tidak boleh 0`);
                            } else if (pembulatanValue < 0) {
                                validationErrors.push(`Section ${sectionNumber}: Pembulatan tidak boleh negatif`);
                            }
                            
                            // Validasi data rows
                            let hasValidRow = false;
                            rawData.forEach((row, rowIndex) => {
                                const deskripsi = String(row[1] || '').trim();
                                const unit = parseNumber(row[5]);
                                
                                // Skip baris kosong
                                if (!deskripsi && unit === 0) return;
                                
                                // Jika ada isi, validasi kelengkapan
                                if (deskripsi || unit > 0) {
                                    hasValidRow = true;
                                    
                                    if (!deskripsi) {
                                        validationErrors.push(`Section ${sectionNumber} Baris ${rowIndex + 1}: Deskripsi harus diisi`);
                                    }
                                    
                                    if (unit === 0 || isNaN(unit)) {
                                        validationErrors.push(`Section ${sectionNumber} Baris ${rowIndex + 1}: Unit harus diisi dengan nilai lebih dari 0`);
                                    }
                                }
                            });
                            
                            if (!hasValidRow) {
                                validationErrors.push(`Section ${sectionNumber}: Harus ada minimal 1 baris data yang diisi`);
                            }
                        });
                        
                        // Jika ada error validasi, tampilkan dan jangan lanjutkan
                        if (validationErrors.length > 0) {
                            validationErrors.forEach(error => {
                                notyf.error(error);
                            });
                            return;
                        }
                        
                        btn.innerHTML = "⏳ Menyimpan...";
                        btn.disabled = true;

                        // Beri delay kecil untuk memastikan spreadsheet data ter-update
                        setTimeout(() => {
                            collectAndSaveJasaData(btn);
                        }, 100);
                        });
                    }

                    function collectAndSaveJasaData(btn) {
                        // Force recalculation of all rows before collecting data
                        jasaSections.forEach(section => {
                            const data = section.spreadsheet.getData();
                            for (let i = 0; i < data.length; i++) {
                                try {
                                    recalcJasaRow(section.spreadsheet, i);
                                } catch(e) {
                                    console.log('Recalc failed for row', i, 'in section', section.id, e);
                                }
                            }
                        });

                        const allSectionsData = jasaSections.map(section => {
                            const sectionElement = document.getElementById(section.id);
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');
                            const rawData = section.spreadsheet.getData();

                            const data = rawData.map((row, rowIndex) => ({
                                no: row[0],
                                deskripsi: row[1],
                                vol: parseNumber(row[2]),
                                hari: parseNumber(row[3]),
                                orang: parseNumber(row[4]),
                                unit: parseNumber(row[5]),
                                total: parseNumber(row[6]),
                                id_jasa_detail: row[7] || null,
                                comments: getJasaCommentsForRow(section.id, rowIndex)
                            }));

                            const processedData = dedupeSectionData({
                                nama_section: namaSectionInput.value,
                                data
                            });

                            return {
                                nama_section: namaSectionInput.value,
                                pembulatan: parseInt(pembulatanInput.value) || 0,
                                data: processedData
                            };
                        });

                        console.log('💾 Saving jasa data:', {
                            penawaran_id: {{ $penawaran->id_penawaran }},
                            profit: parseNumber((document.getElementById('jasaProfitInput') || {}).value),
                            pph: parseNumber((document.getElementById('jasaPphInput') || {}).value),
                            use_bpjs: (document.getElementById('jasaUseBpjs') || {checked:false}).checked,
                            use_pembulatan: (document.getElementById('jasaUsePembulatan') || {checked:false}).checked,
                            pembulatan_final: parseNumber((document.getElementById('jasaPembulatanInput') || {}).value),
                            sections: allSectionsData
                        });

                        fetch("{{ route('jasa.save') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                penawaran_id: {{ $penawaran->id_penawaran }},
                                profit: parseNumber(((document.getElementById('jasaProfitInput') || {})).value) || 0,
                                pph: parseNumber(((document.getElementById('jasaPphInput') || {})).value) || 0,
                                use_bpjs: ((document.getElementById('jasaUseBpjs') || {checked:false}).checked) ? 1 : 0,
                                use_pembulatan: ((document.getElementById('jasaUsePembulatan') || {checked:false}).checked) ? 1 : 0,
                                pembulatan_final: parseNumber(((document.getElementById('jasaPembulatanInput') || {})).value) || 0,
                                sections: allSectionsData,
                                version: {{ $activeVersion ?? 0 }}
                            })
                        })
                            .then(res => res.json())
                            .then(data => {
                                console.log('✅ Jasa data saved successfully:', data);
                                // Set flag bahwa Jasa sudah berhasil disimpan
                                jasaSaved = true;
                                btn.innerHTML = "✅ Tersimpan!";
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            })
                            .catch(error => {
                                console.error('❌ Failed to save jasa data:', error);
                                btn.innerHTML = "❌ Gagal";
                                setTimeout(() => {
                                    btn.innerHTML = "Simpan Data Jasa";
                                    btn.disabled = false;
                                }, 2000);
                            });
                    }

                    // =====================================================
                    // FUNGSI PENAWARAN
                    // =====================================================


                    function recalculateRow(spreadsheet, rowIndex, changedCol = null, newValue = null) {
                        console.log('recalculateRow called', {
                            rowIndex,
                            changedCol,
                            newValue,
                            row: spreadsheet.getRowData(rowIndex)
                        });

                        const row = spreadsheet.getRowData(rowIndex);
                        let hpp = parseNumber(row[7]);
                        let qty = parseNumber(row[3]);
                        let isMitra = row[8] ? true : false;
                        let isJudul = row[9] ? true : false;
                        let profitRaw = parseNumber(row[10]) || 0;
                        let addedCost = parseNumber(row[12]) || 0;

                        let profitDecimal = profitRaw;
                        if (profitRaw > 1) profitDecimal = profitRaw / 100;

                        let hargaSatuan = 0;
                        let total = 0;

                        // Jika is_mitra atau is_judul dichecklist, harga jadi 0
                        if (isMitra || isJudul) {
                            hargaSatuan = 0;
                            total = 0;
                        } else if (profitDecimal > 0 && profitDecimal < 1) {
                            const denominator = 1 - profitDecimal;
                            if (denominator <= 0) {
                                hargaSatuan = Math.ceil(hpp / 1000) * 1000;
                            } else {
                                const rawPrice = Math.round(hpp / denominator);
                                hargaSatuan = Math.ceil(rawPrice / 1000) * 1000;
                            }
                            hargaSatuan += addedCost;
                            total = qty * hargaSatuan;
                        } else {
                            hargaSatuan = Math.ceil(hpp / 1000) * 1000;
                            hargaSatuan += addedCost;
                            total = qty * hargaSatuan;
                        }

                        spreadsheet.setValueFromCoords(5, rowIndex, hargaSatuan, true);
                        spreadsheet.setValueFromCoords(6, rowIndex, total, true);
                        updateSubtotal(sections.find(s => s.spreadsheet === spreadsheet));
                    }

                    function recalculateAll() {
                        sections.forEach((section, sectionIdx) => {
                            const allData = section.spreadsheet.getData();
                            allData.forEach((row, i) => {
                                const hpp = parseNumber(row[7]);
                                const qty = parseNumber(row[3]);
                                const isMitra = row[8] ? true : false;
                                const isJudul = row[9] ? true : false;
                                const profitRaw = parseNumber(row[10]) || 0;
                                const addedCost = parseNumber(row[12]) || 0;

                                let profitDecimal = profitRaw;
                                if (profitRaw > 1) profitDecimal = profitRaw / 100;

                                let hargaSatuan = 0;
                                let total = 0;

                                // Jika is_mitra atau is_judul dichecklist, harga jadi 0
                                if (isMitra || isJudul) {
                                    hargaSatuan = 0;
                                    total = 0;
                                } else if (profitDecimal > 0 && profitDecimal < 1) {
                                    const denominator = 1 - profitDecimal;
                                    if (denominator <= 0) {
                                        hargaSatuan = Math.ceil(hpp / 1000) * 1000;
                                    } else {
                                        const rawPrice = Math.round(hpp / denominator);
                                        hargaSatuan = Math.ceil(rawPrice / 1000) * 1000;
                                    }
                                    hargaSatuan += addedCost;
                                    total = qty * hargaSatuan;
                                } else {
                                    hargaSatuan = Math.ceil(hpp / 1000) * 1000;
                                    hargaSatuan += addedCost;
                                    total = qty * hargaSatuan;
                                }

                                section.spreadsheet.setValueFromCoords(5, i, hargaSatuan, true);
                                section.spreadsheet.setValueFromCoords(6, i, total, true);
                            });

                            updateSubtotal(section);
                        });

                        updateTotalKeseluruhan();
                    }

                    function toggleEditMode(enable) {
                        isEditMode = enable;

                        const editBtn = document.getElementById('editModeBtn');
                        const cancelBtn = document.getElementById('cancelEditBtn');
                        const saveBtn = document.getElementById('saveAllBtn');
                        const addBtn = document.getElementById('addSectionBtn');

                        if (hasExistingData) {
                            if (editBtn) editBtn.classList.toggle('hidden', enable);
                            if (cancelBtn) cancelBtn.classList.toggle('hidden', !enable);
                        } else {
                            if (editBtn) editBtn.classList.add('hidden');
                            if (cancelBtn) cancelBtn.classList.add('hidden');
                        }

                        if (saveBtn) saveBtn.classList.remove('hidden');
                        if (addBtn) addBtn.classList.toggle('hidden', !enable);

                        sections.forEach(section => {
                            const sectionElement = document.getElementById(section.id);
                            const spreadsheetWrapper = document.getElementById(section.spreadsheetId);
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const addRowBtn = sectionElement.querySelector('.add-row-btn');
                            const deleteRowBtn = sectionElement.querySelector('.delete-row-btn');
                            const deleteSectionBtn = sectionElement.querySelector('.delete-section-btn');

                            if (enable) {
                                spreadsheetWrapper.classList.remove('spreadsheet-disabled');
                                section.spreadsheet.options.editable = true;
                            } else {
                                spreadsheetWrapper.classList.add('spreadsheet-disabled');
                                section.spreadsheet.options.editable = false;
                            }

                            areaSelect.disabled = !enable;
                            addRowBtn.classList.toggle('hidden', !enable);
                            deleteRowBtn.classList.toggle('hidden', !enable);
                            deleteSectionBtn.classList.toggle('hidden', !enable);
                        });

                        // Start/Stop auto-save berdasarkan mode edit
                        if (enable) {
                            if (typeof startAutoSave === 'function') {
                                startAutoSave();
                            }
                        } else {
                            if (typeof stopAutoSave === 'function') {
                                stopAutoSave();
                            }
                        }
                    }

                    function createSection(sectionData = null) {
                        sectionCounter++;
                        const sectionId = 'section-' + sectionCounter;
                        const spreadsheetId = 'spreadsheet-' + sectionCounter;

                        console.log(`🏗️ Creating section: ${sectionId}`, {
                            hasData: !!sectionData
                        });

                        const initialData = sectionData ? sectionData.data.map(row => [
                            row.no || '',
                            row.tipe || '',
                            row.deskripsi || '',
                            row.qty || 0,
                            row.satuan || '',
                            row.harga_satuan || 0,
                            row.harga_total || 0,
                            row.hpp || 0,
                            row.is_mitra ? true : false,
                            row.is_judul ? true : false,
                            row.profit || 0,
                            row.color_code || 1,
                            row.added_cost || 0,
                            row.delivery_time || ''
                        ]) : [
                            ['', '', '', 0, '', 0, 0, 0, false, false, 0, 1, 0, ''],
                            ['', '', '', 0, '', 0, 0, 0, false, false, 0, 1, 0, ''],
                        ];

                        const sectionHTML = `
                                <div class="section-card p-4 mb-6 bg-white" id="${sectionId}">
                                    <div class="flex justify-between items-center mb-4">
                                        <div class="flex items-center gap-4">
                                            <h3 class="text-lg font-bold text-gray-700">Section ${sectionCounter}</h3>
                                            <input type="text" class="nama-section-input border rounded px-3 py-1 ml-2" placeholder="Ex: Main Unit" value="${sectionData && sectionData.nama_section ? sectionData.nama_section : ''}">
                                            <div class="flex items-center">
                                                <label class="block text-sm font-semibold mr-2">Area Pemasangan:</label>
                                                <input type="text" class="area-select border rounded px-3 py-1 ml-2" placeholder="Ex: Kantor" value="${sectionData && sectionData.area ? sectionData.area : ''}">
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                        <button class="flex items-center add-row-btn bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Tambah Baris
                                        </button>
                                        <button class="flex items-center delete-row-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Hapus Baris
                                        </button>
                                        <button class="delete-section-btn bg-white text-red-500 px-3 py-1 rounded hover:bg-red-500 hover:text-white transition text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                        </button>
                                    </div>
                                    </div>
                                    <div class="spreadsheet-scroll-wrapper" style="overflow-x:auto;">
                                        <div id="${spreadsheetId}"></div>
                                    </div>
                                    <div class="text-right mt-3 font-semibold text-gray-700">
                                        Subtotal: Rp <span id="${sectionId}-subtotal">0</span>
                                    </div>
                                </div>`;

                        document.getElementById('sectionsContainer').insertAdjacentHTML('beforeend', sectionHTML);

                        const spreadsheet = jspreadsheet(document.getElementById(spreadsheetId), {
                            data: initialData,
                            columns: [
                                { title: 'No', width: 60 },
                                { title: 'Tipe', width: 150, wordWrap: true },
                                { title: 'Deskripsi', width: 300, wordWrap: true },
                                { title: 'QTY', width: 100, type: 'numeric' },
                                {
                                    title: 'Satuan',
                                    width: 100,
                                    type: 'dropdown',
                                    source: satuanOptions,
                                },
                                {
                                    title: 'Harga Satuan',
                                    width: 150,
                                    type: 'numeric',
                                    readOnly: true,
                                    mask: 'Rp #.##0',
                                    decimal: ','
                                },
                                {
                                    title: 'Harga Total',
                                    width: 150,
                                    type: 'numeric',
                                    readOnly: true,
                                    mask: 'Rp #.##0',
                                    decimal: ','
                                },
                                {
                                    title: 'HPP',
                                    width: 100,
                                    type: 'numeric',
                                    mask: 'Rp #.##0',
                                    decimal: ','
                                },
                                { title: 'Mitra', width: 80, type: 'checkbox' },
                                { title: 'Judul', width: 80, type: 'checkbox' },
                                {
                                    title: 'Profit (%)',
                                    width: 100,
                                    type: 'numeric',
                                    decimal: ','
                                },
                                {
                                    title: 'Warna',
                                    width: 160,
                                    type: 'dropdown',
                                    source: [
                                        { id: 1, name: 'Hitam - BOQ / Klien' },
                                        { id: 2, name: 'Ungu - Detail / Breakdown' },
                                        { id: 3, name: 'Biru - Rekomendasi Puterako' },
                                    ],
                                    default: 1
                                },
                                {
                                    title: 'Added Cost',
                                    width: 120,
                                    type: 'numeric',
                                    mask: 'Rp #.##0',
                                    decimal: ','
                                },
                                {
                                    title: 'Keterangan',
                                    width: 120,
                                    type: 'text'
                                }
                            ],
                            tableOverflow: true,
                            tableWidth: '100%',
                            tableHeight: '100%',
                            editable: isEditMode,
                            onchange: function (instance, cell, colIndex, rowIndex, value) {
                                // Mark data sebagai unsaved untuk auto-save
                                if (typeof markAsUnsaved === 'function') {
                                    markAsUnsaved();
                                }
                                
                                console.log('📝 Spreadsheet onChange:', {
                                    spreadsheetId,
                                    colIndex,
                                    rowIndex,
                                    value,
                                    columnName: ['No', 'Tipe', 'Deskripsi', 'QTY', 'Satuan',
                                        'Harga Satuan', 'Harga Total', 'HPP', 'Mitra', 'Judul', 'Profit (%)', 'Warna', 'Added Cost', 'Keterangan'
                                    ][colIndex]
                                });

                                // colIndex: 3=QTY, 7=HPP, 8=Mitra, 9=Judul, 10=Profit, 12=Added Cost
                                if (colIndex == 3 || colIndex == 7 || colIndex == 8 || colIndex == 9 || colIndex == 10 || colIndex == 12) {
                                    recalculateRow(spreadsheet, rowIndex, colIndex, value);
                                } else {
                                    console.log('⏭️ Skip calculation (column not QTY/HPP/Mitra/Judul/Profit/Added Cost)');
                                }
                            },
                            onselection: function (instance, x1, y1, x2, y2, origin) {
                                scrollToSelectedCell(instance, x2, y2);
                            },
                            contextMenu: function(obj, x, y, e, items) {
                                // Get existing comment for current cell
                                const existingComment = getComment(sectionId, y, x);
                                
                                // Build new items array with comment options first
                                let newItems = [];
                                
                                if (existingComment) {
                                    newItems.push({
                                        title: 'View Comment',
                                        onclick: function() {
                                            viewCommentModal(existingComment);
                                        }
                                    });
                                    newItems.push({
                                        title: 'Edit Comment',
                                        onclick: function() {
                                            openCommentModal('Edit Comment', existingComment, function(newComment) {
                                                if (newComment !== null) {
                                                    setComment(sectionId, y, x, newComment);
                                                }
                                            });
                                        }
                                    });
                                    newItems.push({
                                        title: 'Delete Comment',
                                        onclick: function() {
                                            if (confirm('Hapus komentar ini?')) {
                                                deleteComment(sectionId, y, x);
                                            }
                                        }
                                    });
                                } else {
                                    newItems.push({
                                        title: 'Insert Comment',
                                        onclick: function() {
                                            openCommentModal('Insert Comment', '', function(comment) {
                                                if (comment) {
                                                    setComment(sectionId, y, x, comment);
                                                }
                                            });
                                        }
                                    });
                                }
                                
                                // Add separator
                                newItems.push({ type: 'line' });
                                
                                // Build default jspreadsheet menu items manually
                                newItems.push({
                                    title: 'Insert a new row before',
                                    onclick: function() {
                                        // y is the row index where context menu was clicked
                                        // insertRow(quantity, index, insertBefore=true)
                                        obj.insertRow(1, parseInt(y), true);
                                    }
                                });
                                newItems.push({
                                    title: 'Insert a new row after',
                                    onclick: function() {
                                        // Insert after means insertBefore=false, so insert at y+1
                                        obj.insertRow(1, parseInt(y) + 1, false);
                                    }
                                });
                                newItems.push({
                                    title: 'Delete selected rows',
                                    onclick: function() {
                                        obj.deleteRow(obj.getSelectedRows().length ? undefined : y);
                                    }
                                });
                                newItems.push({ type: 'line' });
                                newItems.push({
                                    title: 'Copy',
                                    shortcut: 'Ctrl + C',
                                    onclick: function() {
                                        obj.copy(true);
                                    }
                                });
                                newItems.push({
                                    title: 'Paste',
                                    shortcut: 'Ctrl + V',
                                    onclick: function() {
                                        if (obj.selectedCell) {
                                            navigator.clipboard.readText().then(function(text) {
                                                obj.paste(obj.selectedCell[0], obj.selectedCell[1], text);
                                            });
                                        }
                                    }
                                });
                                
                                return newItems;
                            }
                        });
                        
                        // Bind comment hover events after spreadsheet is created
                        bindCommentHoverEvents(sectionId);
                        
                        // Load comments if section has data
                        if (sectionData) {
                            loadCommentsFromSectionData(sectionId, sectionData);
                        }

                        const sectionElement = document.getElementById(sectionId);

                        if (sectionData && sectionData.area) {
                            sectionElement.querySelector('.area-select').value = sectionData.area;
                        }

                        sectionElement.querySelector('.add-row-btn').addEventListener('click', () => {
                            spreadsheet.insertRow();
                        });

                        sectionElement.querySelector('.delete-row-btn').addEventListener('click', () => {
                            const totalRows = spreadsheet.getData().length;
                            const input = prompt(
                                `Masukkan nomor baris yang ingin dihapus (1-${totalRows}):\n\nContoh:\n- Satu baris: 3\n- Beberapa baris: 2,5,7\n- Range: 3-6\n- Kombinasi: 2,5-8,10`
                            );

                            if (input) {
                                try {
                                    const rowsToDelete = [];
                                    const parts = input.split(',');

                                    parts.forEach(part => {
                                        part = part.trim();
                                        if (part.includes('-')) {
                                            const [start, end] = part.split('-').map(n => parseInt(n
                                                .trim()));
                                            for (let i = start; i <= end; i++) {
                                                rowsToDelete.push(i);
                                            }
                                        } else {
                                            rowsToDelete.push(parseInt(part));
                                        }
                                    });

                                    const validRows = [...new Set(rowsToDelete)]
                                        .filter(row => row >= 1 && row <= totalRows)
                                        .sort((a, b) => b - a);

                                    if (validRows.length === 0) {
                                        alert('Tidak ada baris yang valid untuk dihapus!');
                                        return;
                                    }

                                    if (confirm(
                                        `Hapus ${validRows.length} baris: ${validRows.sort((a, b) => a - b).join(', ')}?`
                                    )) {
                                        validRows.forEach(rowNum => {
                                            spreadsheet.deleteRow(rowNum - 1, 1);
                                        });
                                    }
                                } catch (error) {
                                    alert('Format input tidak valid! Gunakan format: 2,5,7 atau 3-6');
                                }
                            }
                        });

                        sectionElement.querySelector('.delete-section-btn').addEventListener('click', () => {
                            if (confirm('Yakin ingin menghapus section ini?')) {
                                sections = window.penawaranSections = sections.filter(s => s.id !== sectionId);
                                sectionElement.remove();
                            }
                        });

                        sections.push({
                            id: sectionId,
                            spreadsheetId,
                            spreadsheet
                        });
                        
                        // Update global reference
                        window.penawaranSections = sections;

                        // applyTemplateStyle(spreadsheetId);
                        updateSubtotal({
                            id: sectionId,
                            spreadsheet
                        });
                    }

                    // Initialize from database value if available
                    const dbGrandTotal = {{ $versionRow->grand_total ?? 0 }};
                    if (dbGrandTotal > 0) {
                        currentGrandTotal = dbGrandTotal;
                        console.log('📊 Initialized currentGrandTotal from DB:', currentGrandTotal);
                    }

                    function updateTotalKeseluruhan() {
                        let totalKeseluruhan = 0;

                        sections.forEach(section => {
                            const subtotalEl = document.getElementById(`${section.id}-subtotal`);
                            if (subtotalEl) {
                                const subtotal = parseNumber(subtotalEl.textContent.replace(/\./g, ''));
                                totalKeseluruhan += subtotal;
                            }
                        });

                        // Update Total (sum of section subtotals)
                        const totalKesEl = document.getElementById('totalKeseluruhan');
                        if (totalKesEl) totalKesEl.textContent = totalKeseluruhan.toLocaleString('id-ID');

                        // read PPN
                        const ppnInputEl = document.getElementById('ppnInput');
                        const ppnPersen = ppnInputEl ? (parseNumber(ppnInputEl.value) || 0) : 0;

                        // read Best Price toggle and value
                        const isBestEl = document.getElementById('isBestPrice');
                        const useBest = isBestEl ? !!isBestEl.checked : false;
                        const bestInputEl = document.getElementById('bestPriceInput');
                        const bestPriceRaw = bestInputEl ? (bestInputEl.value || '0') : '0';
                        const bestPrice = parseNumber(bestPriceRaw);

                        // base amount for PPN and grand total
                        const baseAmount = useBest ? bestPrice : totalKeseluruhan;

                        const ppnNominal = (baseAmount * ppnPersen) / 100;
                        const grandTotal = baseAmount + ppnNominal;

                        // Store raw value in global variable for direct access
                        currentGrandTotal = Math.round(grandTotal);

                        // update PPN display
                        const ppnPersenDisplayEl = document.getElementById('ppnPersenDisplay');
                        if (ppnPersenDisplayEl) ppnPersenDisplayEl.textContent = ppnPersen;
                        const ppnNominalEl = document.getElementById('ppnNominal');
                        if (ppnNominalEl) ppnNominalEl.textContent = ppnNominal.toLocaleString('id-ID');

                        // show/hide best price display row
                        const bestRow = document.getElementById('bestPriceDisplayRow');
                        if (bestRow) {
                            if (useBest) {
                                bestRow.style.display = 'flex';
                                const bestDispEl = document.getElementById('bestPriceDisplay');
                                if (bestDispEl) bestDispEl.textContent = bestPrice.toLocaleString('id-ID');
                            } else {
                                bestRow.style.display = 'none';
                            }
                        }

                        // update grand total (based on baseAmount)
                        const grandTotalEl = document.getElementById('grandTotal');
                        if (grandTotalEl) grandTotalEl.textContent = grandTotal.toLocaleString('id-ID');

                        console.log('💰 Total Summary:', {
                            totalKeseluruhan,
                            useBest,
                            bestPrice,
                            ppnPersen,
                            ppnNominal,
                            grandTotal,
                            currentGrandTotalRaw: currentGrandTotal
                        });
                    }

                    function updateSubtotal(section) {
                        const data = section.spreadsheet.getData();
                        let subtotal = 0;

                        data.forEach(row => {
                            subtotal += parseNumber(row[6]); // kolom Harga Total
                        });

                        const subtotalEl = document.getElementById(`${section.id}-subtotal`);
                        if (subtotalEl) {
                            subtotalEl.textContent = subtotal.toLocaleString('id-ID');
                        }

                        // TAMBAHAN: Update total keseluruhan setiap kali subtotal berubah
                        updateTotalKeseluruhan();
                    }

                    // Event listener untuk perubahan PPN (guard when Penawaran UI exists)
                    const ppnInput = document.getElementById('ppnInput');
                    if (ppnInput) ppnInput.addEventListener('input', updateTotalKeseluruhan);
                    const isBestChk = document.getElementById('isBestPrice');
                    if (isBestChk) isBestChk.addEventListener('change', updateTotalKeseluruhan);
                    const bestPriceInput = document.getElementById('bestPriceInput');
                    if (bestPriceInput) bestPriceInput.addEventListener('input', updateTotalKeseluruhan);

                    function setBestPriceInputState() {
                        const chk = document.getElementById('isBestPrice');
                        const input = document.getElementById('bestPriceInput');
                        const bestRow = document.getElementById('bestPriceDisplayRow');

                        if (!chk || !input) return;

                        if (chk.checked) {
                            input.disabled = false;
                            // kalau sebelumnya 0, user boleh ubah — jangan otomatis isi
                        } else {
                            // reset dan disable ketika unchecked
                            input.value = '0';
                            input.disabled = true;
                            // sembunyikan tampilan best price di ringkasan juga
                            if (bestRow) bestRow.style.display = 'none';
                        }
                    }

                    // panggil saat load untuk set state awal
                    setBestPriceInputState();

                    // ganti listener existing supaya juga set state + update totals
                    if (isBestChk) {
                        isBestChk.addEventListener('change', function () {
                            setBestPriceInputState();
                            updateTotalKeseluruhan();
                        });
                    }

                    if (bestPriceInput) {
                        bestPriceInput.addEventListener('input', updateTotalKeseluruhan);
                    }

                    // =====================================================
                    // EVENT LISTENERS PENAWARAN
                    // =====================================================

                    const addSectionBtn = document.getElementById('addSectionBtn');
                    if (addSectionBtn) addSectionBtn.addEventListener('click', () => createSection());

                    const editModeBtn = document.getElementById('editModeBtn');
                    if (editModeBtn) editModeBtn.addEventListener('click', () => {
                        toggleEditMode(true);
                    });

                    const cancelEditBtn = document.getElementById('cancelEditBtn');
                    if (cancelEditBtn) cancelEditBtn.addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

                    const saveAllBtn = document.getElementById('saveAllBtn');
                    if (saveAllBtn) saveAllBtn.addEventListener('click', function () {
                        const btn = this;
                        btn.innerHTML = "⏳ Menyimpan...";
                        btn.disabled = true;

                        // ==================== VALIDASI AWAL ====================
                        const validationErrors = [];

                        // Validasi section names dan areas tidak kosong
                        for (let sectionIdx = 0; sectionIdx < sections.length; sectionIdx++) {
                            const section = sections[sectionIdx];
                            const sectionElement = document.getElementById(section.id);
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            
                            if (!namaSectionInput.value || namaSectionInput.value.trim() === '') {
                                validationErrors.push(`Section ${sectionIdx + 1}: Nama Section tidak boleh kosong`);
                            }
                        }

                        // Validasi baris data di setiap section
                        // Index: 0=No, 1=Tipe, 2=Deskripsi, 3=QTY, 4=Satuan, 5=Harga Satuan, 6=Harga Total, 7=HPP, 8=Mitra, 9=Judul, 10=Profit, 11=Warna, 12=Added Cost, 13=Keterangan
                        const requiredColumns = [1, 2, 3, 4, 7, 10, 11]; // Tipe, Deskripsi, QTY, Satuan, HPP, Profit, Warna
                        const columnNames = ['Tipe', 'Deskripsi', 'QTY', 'Satuan', 'HPP', 'Profit (%)', 'Warna'];
                        
                        for (let sectionIdx = 0; sectionIdx < sections.length; sectionIdx++) {
                            const section = sections[sectionIdx];
                            const rawData = section.spreadsheet.getData();
                            let sectionHasData = false;
                            
                            for (let rowIdx = 0; rowIdx < rawData.length; rowIdx++) {
                                const row = rawData[rowIdx];
                                const missingColumns = [];
                                
                                // Check if row has any significant data
                                const hasSignificantData = row.some((cell, idx) => {
                                    // Check if has text content in key fields
                                    if ([0, 1, 2, 4].includes(idx)) return cell && String(cell).trim() !== ''; // No, Tipe, Deskripsi, Satuan
                                    // Check if has numeric values
                                    if ([3, 5, 6, 7, 12].includes(idx)) return parseNumber(cell) > 0; // QTY, HargaSatuan, HargaTotal, HPP, AddedCost
                                    return false;
                                });
                                
                                if (!hasSignificantData) continue; // Skip completely empty rows
                                sectionHasData = true;
                                
                                // Check required columns only for rows with data
                                requiredColumns.forEach((colIdx, posIdx) => {
                                    const cellValue = String(row[colIdx] || '').trim();
                                    // For numeric fields (QTY, HPP, Profit), check if > 0
                                    if ([3, 7, 10].includes(colIdx)) {
                                        if (parseNumber(cellValue) <= 0) {
                                            missingColumns.push(columnNames[posIdx]);
                                        }
                                    } else {
                                        // For text fields, check if not empty
                                        if (cellValue === '' || cellValue === '0' || cellValue === 'false') {
                                            missingColumns.push(columnNames[posIdx]);
                                        }
                                    }
                                });
                                
                                if (missingColumns.length > 0) {
                                    validationErrors.push(
                                        `Section ${sectionIdx + 1}, Baris ${rowIdx + 1}: Kolom ${missingColumns.join(', ')} tidak boleh kosong`
                                    );
                                }
                            }

                            // Jika satu section sama sekali tidak punya baris berisi,
                            // anggap tidak valid (harus ada minimal 1 baris data)
                            if (!sectionHasData) {
                                validationErrors.push(`Section ${sectionIdx + 1}: Minimal 1 baris data harus diisi`);
                            }
                        }

                        // Jika ada error, tampilkan toaster dan berhenti
                        if (validationErrors.length > 0) {
                            // Format errors untuk notyf
                            const errorList = validationErrors.map((err, idx) => `${idx + 1}. ${err}`).join('<br>');
                            const errorMessage = `<strong>Validasi Gagal:</strong><br>${errorList}`;
                            
                            notyf.error({
                                message: errorMessage,
                                duration: 6000
                            });
                            btn.innerHTML = "💾 Simpan";
                            btn.disabled = false;
                            console.error('Validation errors:', validationErrors);
                            return;
                        }

                        // Validasi unique area + nama_section combination
                        const sectionKeys = new Set();
                        let hasDuplicate = false;
                        
                        for (const section of sections) {
                            const sectionElement = document.getElementById(section.id);
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const key = `${areaSelect.value}|${namaSectionInput.value}`;
                            
                            if (sectionKeys.has(key)) {
                                hasDuplicate = true;
                                break;
                            }
                            sectionKeys.add(key);
                        }
                        
                        if (hasDuplicate) {
                            notyf.error('Setiap section harus memiliki kombinasi Area dan Nama Section yang unik!');
                            btn.innerHTML = "💾 Simpan";
                            btn.disabled = false;
                            return;
                        }

                        const allSectionsData = sections.map(section => {
                            const sectionElement = document.getElementById(section.id);
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const rawData = section.spreadsheet.getData();

                            return {
                                area: areaSelect.value,
                                nama_section: namaSectionInput.value,
                                data: rawData.map((row, rowIndex) => ({
                                    no: row[0],
                                    tipe: row[1],
                                    deskripsi: row[2],
                                    qty: parseNumber(row[3]),
                                    satuan: row[4],
                                    harga_satuan: parseNumber(row[5]),
                                    harga_total: parseNumber(row[6]),
                                    hpp: parseNumber(row[7]),
                                    is_mitra: row[8] ? 1 : 0,
                                    is_judul: row[9] ? 1 : 0,
                                    profit: parseNumber(row[10]) || 0,
                                    color_code: row[11] || 1,
                                    added_cost: parseNumber(row[12]) || 0,
                                    delivery_time: row[13] || '',
                                    comments: getCommentsForRow(section.id, rowIndex)
                                })).filter(row => 
                                    // Only keep rows that have actual data (not completely empty)
                                    row.no || row.tipe || row.deskripsi || row.satuan || row.delivery_time || row.comments ||
                                    row.harga_satuan > 0 || row.harga_total > 0 || row.hpp > 0 || row.added_cost > 0
                                )
                            };
                        });

                        // Debug: log all sections data with comments
                        console.log('📝 Saving sections with comments:', JSON.stringify(allSectionsData, null, 2));
                        console.log('📝 sectionComments state:', JSON.stringify(sectionComments, null, 2));

                        fetch("{{ route('penawaran.save') }}", {
                            credentials: 'same-origin',
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                penawaran_id: {{ $penawaran->id_penawaran }},
                                ppn_persen: parseNumber(((document.getElementById('ppnInput') || {})).value) || 11,
                                is_best_price: ((document.getElementById('isBestPrice') || {checked:false}).checked) ? 1 : 0,
                                best_price: parseNumber(((document.getElementById('bestPriceInput') || {})).value || 0) || 0,
                                sections: allSectionsData,
                                version: {{ $activeVersion ? $activeVersion : 0 }}
                                            })
                        })
                            .then(async res => {
                                const text = await res.text();
                                try {
                                    const json = JSON.parse(text);
                                    console.log('Penawaran save response raw:', json);
                                    return json;
                                } catch (e) {
                                    console.error('Non-JSON response:', text);
                                    throw new Error('Invalid JSON response from server');
                                }
                            })
                            .then(data => {
                                console.log('✅ Data saved with totals:', data);
                                // Set flag bahwa Penawaran sudah berhasil disimpan
                                penawaranSaved = true;
                                
                                // Stop autosave interval setelah berhasil save ke database
                                if (typeof stopAutoSave === 'function') {
                                    stopAutoSave();
                                }
                                
                                // Clear draft dari localStorage setelah berhasil save ke database
                                if (typeof clearAutoSaveData === 'function') {
                                    clearAutoSaveData();
                                }
                                
                                // Reset unsaved changes flag
                                hasUnsavedChanges = false;
                                
                                notyf.success(data.message || 'Penawaran berhasil disimpan');
                                btn.innerHTML = "✅ Tersimpan!";
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            })
                            .catch(error => {
                                console.error('❌ Save failed:', error);
                                notyf.error(data.message || 'Penawaran gagal disimpan');
                                btn.innerHTML = "❌ Gagal";
                                setTimeout(() => {
                                    btn.innerHTML = "Simpan Semua Data";
                                    btn.disabled = false;
                                }, 2000);
                            });
                    });

                    // =====================================================
                    // AUTO-SAVE TO LOCALSTORAGE (Setiap 1 menit)
                    // =====================================================
                    let hasUnsavedChanges = false;
                    let autoSaveInterval = null;
                    const LOCAL_STORAGE_KEY = `penawaran_autosave_{{ $penawaran->id_penawaran }}_v{{ $activeVersion ? $activeVersion : 0 }}`;

                    // Fungsi untuk menandai ada perubahan
                    function markAsUnsaved() {
                        hasUnsavedChanges = true;
                    }

                    // Fungsi auto-save ke localStorage (bisa dipanggil berulang kali)
                    function autoSavePenawaran() {
                        // Skip jika tidak dalam edit mode atau tidak ada section
                        if (!isEditMode || sections.length === 0) {
                            console.log('⏭️ Auto-save skipped: not in edit mode or no sections');
                            return false;
                        }

                        console.log('💾 Auto-saving penawaran data to localStorage...');

                        // Kumpulkan semua data termasuk yang belum lengkap
                        const allSectionsData = sections.map(section => {
                            const sectionElement = document.getElementById(section.id);
                            const areaSelect = sectionElement.querySelector('.area-select');
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const rawData = section.spreadsheet.getData();

                            return {
                                area: areaSelect.value || '',
                                nama_section: namaSectionInput.value || '',
                                data: rawData.map((row, rowIndex) => ({
                                    no: row[0] || '',
                                    tipe: row[1] || '',
                                    deskripsi: row[2] || '',
                                    qty: parseNumber(row[3]) || 0,
                                    satuan: row[4] || '',
                                    harga_satuan: parseNumber(row[5]) || 0,
                                    harga_total: parseNumber(row[6]) || 0,
                                    hpp: parseNumber(row[7]) || 0,
                                    is_mitra: row[8] ? 1 : 0,
                                    is_judul: row[9] ? 1 : 0,
                                    profit: parseNumber(row[10]) || 0,
                                    color_code: row[11] || 1,
                                    added_cost: parseNumber(row[12]) || 0,
                                    delivery_time: row[13] || '',
                                    comments: getCommentsForRow(section.id, rowIndex)
                                }))
                            };
                        });

                        const autoSaveData = {
                            penawaran_id: {{ $penawaran->id_penawaran }},
                            version: {{ $activeVersion ? $activeVersion : 0 }},
                            ppn_persen: parseNumber(document.getElementById('ppnInput').value) || 11,
                            is_best_price: document.getElementById('isBestPrice').checked ? 1 : 0,
                            best_price: parseNumber(document.getElementById('bestPriceInput').value) || 0,
                            sections: allSectionsData,
                            saved_at: new Date().toISOString()
                        };

                        try {
                            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(autoSaveData));
                            hasUnsavedChanges = false;
                            const now = new Date();
                            console.log('✅ Auto-save to localStorage successful at', now.toLocaleTimeString());
                            
                            notyf.success({
                                message: 'Autosaving...',
                                duration: 2000,
                                dismissible: true
                            });
                            return true;
                        } catch (error) {
                            console.error('❌ Auto-save to localStorage failed:', error);
                            return false;
                        }
                    }

                    // Fungsi untuk load data dari localStorage
                    function loadAutoSaveData() {
                        try {
                            const savedData = localStorage.getItem(LOCAL_STORAGE_KEY);
                            if (savedData) {
                                return JSON.parse(savedData);
                            }
                        } catch (error) {
                            console.error('❌ Failed to load auto-save data:', error);
                        }
                        return null;
                    }

                    // Fungsi untuk clear auto-save data dari localStorage
                    function clearAutoSaveData() {
                        try {
                            localStorage.removeItem(LOCAL_STORAGE_KEY);
                            console.log('🗑️ Auto-save data cleared from localStorage');
                        } catch (error) {
                            console.error('❌ Failed to clear auto-save data:', error);
                        }
                    }

                    // Fungsi untuk restore data dari localStorage ke spreadsheet
                    function restoreAutoSaveData(savedData) {
                        if (!savedData || !savedData.sections) return false;

                        console.log('🔄 Restoring data from localStorage...', savedData);

                        // Clear existing sections first
                        sections.forEach(section => {
                            const sectionElement = document.getElementById(section.id);
                            if (sectionElement) sectionElement.remove();
                        });
                        sections.length = 0;
                        sectionCounter = 0;

                        // Restore PPN dan Best Price
                        document.getElementById('ppnInput').value = savedData.ppn_persen || 11;
                        document.getElementById('isBestPrice').checked = savedData.is_best_price == 1;
                        document.getElementById('bestPriceInput').value = savedData.best_price || 0;

                        // Recreate sections dengan data dari localStorage
                        savedData.sections.forEach(sectionData => {
                            createSection(sectionData);
                        });

                        // Masuk edit mode
                        toggleEditMode(true);

                        // Recalculate
                        setTimeout(() => {
                            recalculateAll();
                            updateTotalKeseluruhan();
                        }, 200);

                        console.log('✅ Data restored from localStorage');
                        notyf.success({
                            message: '📂 Draft sebelumnya berhasil dipulihkan',
                            duration: 3000
                        });

                        return true;
                    }

                    // Start auto-save interval (setiap 1 menit)
                    function startAutoSave() {
                        if (autoSaveInterval) {
                            clearInterval(autoSaveInterval);
                        }
                        autoSaveInterval = setInterval(autoSavePenawaran, 60000); // 1 menit
                        console.log('🔄 Auto-save started (setiap 1 menit)');
                    }

                    // Stop auto-save
                    function stopAutoSave() {
                        if (autoSaveInterval) {
                            clearInterval(autoSaveInterval);
                            autoSaveInterval = null;
                            console.log('⏹️ Auto-save stopped');
                        }
                    }

                    // Warning sebelum meninggalkan halaman jika ada perubahan
                    window.addEventListener('beforeunload', function(e) {
                        if (isEditMode && hasUnsavedChanges) {
                            e.preventDefault();
                            e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                            return e.returnValue;
                        }
                    });

                    // =====================================================
                    // INISIALISASI PENAWARAN
                    // =====================================================

                    if (showPenawaran) {
                        // Cek apakah ada draft tersimpan di localStorage
                        const savedDraft = loadAutoSaveData();
                        if (savedDraft && savedDraft.sections && savedDraft.sections.length > 0) {
                            const savedTime = new Date(savedDraft.saved_at);
                            const formattedTime = savedTime.toLocaleString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            // Tampilkan modal konfirmasi restore
                            if (confirm(`📂 Ditemukan draft tersimpan pada ${formattedTime}.\n\nApakah Anda ingin memulihkan draft tersebut?\n\nKlik OK untuk memulihkan, atau Cancel untuk mengabaikan dan menggunakan data dari database.`)) {
                                restoreAutoSaveData(savedDraft);
                            } else {
                                // User memilih tidak restore, clear localStorage
                                clearAutoSaveData();
                                
                                // Load dari database seperti biasa
                                if (initialSections.length > 0) {
                                    console.log('🗄️ Loading existing data...', {
                                        totalSections: initialSections.length
                                    });

                                    initialSections.forEach((section, idx) => {
                                        console.log(`Creating section ${idx + 1}:`, section);
                                        createSection(section);
                                    });

                                    toggleEditMode(false);
                                    console.log('🔒 Mode: VIEW (data exists)');

                                    console.log('🚀 Initial calculation with per-row profit values');
                                    recalculateAll();
                                    
                                    if (jasaInitialSections.length > 0) {
                                        jasaSaved = true;
                                    }
                                } else {
                                    console.log('🆕 Creating new empty section...');
                                    createSection();
                                    toggleEditMode(true);
                                    console.log('✏️ Mode: EDIT (new data)');
                                }
                            }
                        } else {
                            // Tidak ada draft, load seperti biasa
                            if (initialSections.length > 0) {
                                console.log('🗄️ Loading existing data...', {
                                    totalSections: initialSections.length
                                });

                                initialSections.forEach((section, idx) => {
                                    console.log(`Creating section ${idx + 1}:`, section);
                                    createSection(section);
                                });

                                toggleEditMode(false);
                                console.log('🔒 Mode: VIEW (data exists)');

                                // Trigger kalkulasi awal setelah semua section dibuat
                                console.log('🚀 Initial calculation with per-row profit values');
                                recalculateAll();
                                
                                // Jika ada data jasa, tandai sebagai saved
                                if (jasaInitialSections.length > 0) {
                                    jasaSaved = true;
                                }
                            } else {
                                console.log('🆕 Creating new empty section...');
                                createSection();
                                toggleEditMode(true);
                                console.log('✏️ Mode: EDIT (new data)');
                            }
                        }
                    }
                    
                    // Update tab states awal
                    updateTabStates();
                    
                    // Load jasa data jika tab Jasa tersedia, lalu restore tab
                    const shouldLoadJasa = (typeof showJasa !== 'undefined') ? showJasa : true;
                    const jasaLoadPromise = shouldLoadJasa ? loadJasaData() : Promise.resolve();
                    jasaLoadPromise.then(() => {
                        // Update tab states setelah (opsional) jasa data loaded
                        updateTabStates();
                        
                        // Restore active tab from localStorage
                        const savedTab = localStorage.getItem(`penawaran_active_tab_${activeVersion}`);
                        if (savedTab) {
                            const savedButton = Array.from(tabButtons).find(btn => btn.getAttribute('data-tab') === savedTab);
                            if (savedButton && !savedButton.classList.contains('locked')) {
                                // Trigger click event untuk restore tab (only if not locked)
                                setTimeout(() => {
                                    savedButton.click();
                                }, 100);
                            }
                        }
                    });
                });

                // =======================
                // TAB REKAP 
                // =======================
                const importBtn = document.getElementById('importRekapBtn');
                const modal = document.getElementById('importRekapModal');
                const closeBtn = document.getElementById('closeRekapModal');
                const loadBtn = document.getElementById('loadRekapBtn');
                const dropdown = document.getElementById('rekapDropdown');

                function getCsrfToken() {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta && meta.content) return meta.content;
                    const input = document.querySelector('input[name="_token"]');
                    if (input && input.value) return input.value;
                    return '{{ csrf_token() }}';
                }

                // populate dropdown when Import modal opens
                if (importBtn) {
                    importBtn.onclick = function() {
                        if (modal) modal.classList.remove('hidden');

                        fetch('{{ route("rekap.all") }}?penawaran_id={{ $penawaran->id_penawaran }}')
                            .then(res => res.json())
                            .then(data => {
                                if (!dropdown) return;
                                
                                // Get already loaded rekap IDs from the display
                                const loadedRekapIds = new Set();
                                const rekapHeaders = document.querySelectorAll('[data-rekap-id]');
                                rekapHeaders.forEach(header => {
                                    const rekapId = header.getAttribute('data-rekap-id');
                                    if (rekapId) loadedRekapIds.add(parseInt(rekapId));
                                });
                                
                                dropdown.innerHTML = '<option value="">-- Pilih Rekap --</option>';
                                data.forEach(r => {
                                    // Skip already loaded rekaps
                                    if (loadedRekapIds.has(r.id)) return;
                                    
                                    const displayText = `${r.nama} - ${r.user_name}`;
                                    dropdown.innerHTML += `<option value="${r.id}">${displayText}</option>`;
                                });
                            })
                            .catch(err => {
                                console.error('Gagal memuat daftar rekap:', err);
                                if (window.notyf) notyf.error('Gagal memuat daftar rekap.');
                            });
                    };
                }

                // close modal
                if (closeBtn) {
                    closeBtn.onclick = function() {
                        if (modal) modal.classList.add('hidden');
                    };
                }

                /**
                 * Render survey data from rekaps for Rincian Rekap tab
                 * @param {Object} response - Response from surveysForPenawaran endpoint
                 */
                function renderRekapSurveys(response) {
                    const container = document.getElementById('rekapSpreadsheet');
                    const accumulationBody = document.getElementById('rekapAccumulationBody');
                    if (!container || !accumulationBody) return;

                    container.innerHTML = '';
                    accumulationBody.innerHTML = '';

                    if (!response || !response.success || !response.rekaps || response.rekaps.length === 0) {
                        container.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                        accumulationBody.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                        return;
                    }

                    const allNumericTotals = {}; // For accumulation across all areas
                    const allSatuans = {}; // For accumulation - satuan per column
                    const allAreaBreakdowns = {}; // For per-area breakdown: { key: { areaName: qty, ... } }

                    // Render each rekap
                    response.rekaps.forEach((rekapData, rekapIdx) => {
                        // Rekap header
                        const rekapHeader = document.createElement('div');
                        rekapHeader.className = 'mb-4 p-4 rounded bg-blue-50 border-l-4 border-blue-500';
                        rekapHeader.setAttribute('data-rekap-id', rekapData.rekap_id);
                        
                        const hasDocuments = rekapData.supporting_documents && rekapData.supporting_documents.length > 0;
                        const docButton = hasDocuments ? `
                            <button class="bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 text-sm dokumen-btn" 
                                    data-docs='${JSON.stringify(rekapData.supporting_documents)}'>
                                📁 Dokumen Pendukung (${rekapData.supporting_documents.length})
                            </button>
                        ` : '';
                        
                        rekapHeader.innerHTML = `
                            <div class="flex justify-between items-center mb-2">
                                <h2 class="text-lg font-bold text-blue-800">${escapeHtml(rekapData.rekap_nama)}</h2>
                                <div class="flex items-center gap-2">
                                    ${docButton}
                                    <div class="text-sm text-blue-600">
                                        ${rekapData.version !== null ? `<span class="bg-blue-100 px-2 py-1 rounded">Rev ${rekapData.version}</span>` : ''}
                                        <span class="ml-2 ${rekapData.rekap_status === 'approved' ? 'text-green-600' : 'text-orange-600'}">${rekapData.rekap_status}</span>
                                    </div>
                                </div>
                            </div>
                            ${rekapData.version_notes ? `<p class="text-sm text-gray-600 mt-1">${escapeHtml(rekapData.version_notes)}</p>` : ''}
                        `;
                        container.appendChild(rekapHeader);

                        // Render each survey (area) in this rekap
                        rekapData.surveys.forEach((survey, surveyIdx) => {
                            const areaName = survey.area_name || 'Default Area';
                            const headers = survey.headers || [];
                            const data = survey.data || [];
                            const totals = survey.totals || {};
                            const satuans = survey.satuans || {};

                            // Area header
                            const areaHeader = document.createElement('div');
                            areaHeader.className = 'mb-2 p-3 rounded bg-green-50';
                            areaHeader.innerHTML = `<h3 class="text-md font-bold text-green-700">${escapeHtml(areaName)}</h3>`;
                            container.appendChild(areaHeader);

                            // Table wrapper
                            const tableWrapper = document.createElement('div');
                            tableWrapper.className = 'mb-6 bg-white rounded shadow overflow-x-auto';

                            const table = document.createElement('table');
                            table.className = 'w-full text-sm border-collapse min-w-max';

                            // Build table header from survey headers
                            const thead = document.createElement('thead');
                            
                            // Group header row
                            const groupRow = document.createElement('tr');
                            headers.forEach(group => {
                                const th = document.createElement('th');
                                th.className = 'text-center bg-green-600 text-white font-semibold px-2 py-2 border';
                                th.colSpan = (group.columns || []).length;
                                th.textContent = group.group || '';
                                groupRow.appendChild(th);
                            });
                            thead.appendChild(groupRow);

                            // Column header row
                            const colRow = document.createElement('tr');
                            headers.forEach(group => {
                                (group.columns || []).forEach(col => {
                                    const th = document.createElement('th');
                                    th.className = 'text-center bg-green-100 font-semibold px-2 py-2 border text-xs';
                                    th.textContent = col.title || col.key || '';
                                    th.style.minWidth = (col.width || 80) + 'px';
                                    colRow.appendChild(th);
                                });
                            });
                            thead.appendChild(colRow);
                            table.appendChild(thead);

                            // Build column keys array for data mapping
                            const columnKeys = [];
                            const numericKeys = [];
                            headers.forEach(group => {
                                (group.columns || []).forEach(col => {
                                    columnKeys.push(col.key);
                                    if (col.type === 'numeric') {
                                        numericKeys.push(col.key);
                                    }
                                });
                            });

                            // Table body
                            const tbody = document.createElement('tbody');
                            data.forEach((row, rowIdx) => {
                                const tr = document.createElement('tr');
                                tr.className = 'hover:bg-gray-50';
                                tr.dataset.rowIndex = rowIdx;

                                columnKeys.forEach((key, colIdx) => {
                                    const td = document.createElement('td');
                                    td.className = 'py-1 px-2 border text-center relative';
                                    td.style.position = 'relative';
                                    const value = row[key];
                                    
                                    // Add cell value normally
                                    const valueSpan = document.createElement('span');
                                    if (value !== null && value !== undefined && value !== '') {
                                        if (numericKeys.includes(key) && !isNaN(parseFloat(value))) {
                                            valueSpan.textContent = parseFloat(value).toLocaleString('id-ID');
                                        } else {
                                            valueSpan.textContent = value;
                                        }
                                    } else {
                                        valueSpan.textContent = '';
                                    }
                                    td.appendChild(valueSpan);
                                    
                                    // Check if there's a comment for this specific cell
                                    const cellKey = rowIdx + ',' + colIdx;
                                    if (survey.comments && survey.comments[cellKey] && survey.comments[cellKey].trim() !== '') {
                                        // Create red triangle indicator at top-right corner
                                        const triangle = document.createElement('div');
                                        triangle.style.position = 'absolute';
                                        triangle.style.top = '0';
                                        triangle.style.right = '0';
                                        triangle.style.width = '0';
                                        triangle.style.height = '0';
                                        triangle.style.borderLeft = '12px solid transparent';
                                        triangle.style.borderTop = '12px solid #ef4444';
                                        triangle.style.cursor = 'pointer';
                                        triangle.style.zIndex = '10';
                                        triangle.title = 'Klik untuk melihat komentar';
                                        triangle.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            window.showCommentModal(survey.comments[cellKey]);
                                        });
                                        td.appendChild(triangle);
                                    }
                                    
                                    tr.appendChild(td);
                                });

                                tbody.appendChild(tr);
                            });

                            // Totals row
                            if (Object.keys(totals).length > 0 && numericKeys.length > 0) {
                                const totalsRow = document.createElement('tr');
                                totalsRow.className = 'bg-yellow-50 font-bold';
                                
                                let isFirstTotal = true;
                                columnKeys.forEach(key => {
                                    const td = document.createElement('td');
                                    td.className = 'py-2 px-2 border text-center';
                                    
                                    if (numericKeys.includes(key) && totals[key] !== undefined) {
                                        td.textContent = parseFloat(totals[key]).toLocaleString('id-ID');
                                        
                                        // Accumulate for grand total
                                        if (!allNumericTotals[key]) allNumericTotals[key] = { total: 0, title: '' };
                                        allNumericTotals[key].total += parseFloat(totals[key]) || 0;
                                        // Find title from headers
                                        headers.forEach(group => {
                                            (group.columns || []).forEach(col => {
                                                if (col.key === key) allNumericTotals[key].title = col.title || key;
                                            });
                                        });
                                        
                                        // Track per-area breakdown
                                        if (!allAreaBreakdowns[key]) allAreaBreakdowns[key] = {};
                                        if (!allAreaBreakdowns[key][areaName]) allAreaBreakdowns[key][areaName] = 0;
                                        allAreaBreakdowns[key][areaName] += parseFloat(totals[key]) || 0;
                                    } else if (isFirstTotal) {
                                        td.textContent = 'TOTAL';
                                        isFirstTotal = false;
                                    } else {
                                        td.textContent = '';
                                    }
                                    totalsRow.appendChild(td);
                                });
                                tbody.appendChild(totalsRow);
                            }

                            // Satuan row - display satuan for each numeric column
                            if (Object.keys(satuans).length > 0) {
                                const satuanRow = document.createElement('tr');
                                satuanRow.className = 'bg-blue-50';
                                
                                let isFirstSatuan = true;
                                columnKeys.forEach(key => {
                                    const td = document.createElement('td');
                                    td.className = 'py-2 px-2 border text-center text-sm';
                                    
                                    if (satuans[key]) {
                                        // Lookup satuan name from ID
                                        const satuanId = satuans[key];
                                        const satuanName = satuanMap[satuanId] || '';
                                        td.textContent = satuanName;
                                        td.style.fontWeight = 'bold';
                                        td.style.color = '#1e40af';
                                        
                                        // Store satuan for accumulation
                                        if (!allSatuans[key]) allSatuans[key] = satuanName;
                                    } else if (isFirstSatuan) {
                                        td.textContent = 'Satuan';
                                        td.style.fontWeight = 'bold';
                                        isFirstSatuan = false;
                                    } else {
                                        td.textContent = '';
                                    }
                                    satuanRow.appendChild(td);
                                });
                                tbody.appendChild(satuanRow);
                            }

                            table.appendChild(tbody);
                            tableWrapper.appendChild(table);
                            container.appendChild(tableWrapper);
                        });
                    });

                    // Render accumulation (grand totals across all areas) with Satuan column
                    if (Object.keys(allNumericTotals).length > 0) {
                        const accTable = document.createElement('table');
                        accTable.className = 'w-full text-sm border-collapse';

                        const accThead = document.createElement('thead');
                        const accHeadRow = document.createElement('tr');
                        
                        const th1 = document.createElement('th');
                        th1.className = 'text-left font-semibold pb-2 bg-blue-100 px-3 py-2';
                        th1.textContent = 'Item';
                        accHeadRow.appendChild(th1);

                        const th2 = document.createElement('th');
                        th2.className = 'text-center font-semibold pb-2 bg-blue-100 px-3 py-2';
                        th2.textContent = 'Quantity';
                        accHeadRow.appendChild(th2);

                        const th3 = document.createElement('th');
                        th3.className = 'text-center font-semibold pb-2 bg-blue-100 px-3 py-2';
                        th3.textContent = 'Satuan';
                        accHeadRow.appendChild(th3);

                        const th4 = document.createElement('th');
                        th4.className = 'text-center font-semibold pb-2 bg-blue-100 px-3 py-2';
                        th4.textContent = 'Aksi';
                        accHeadRow.appendChild(th4);

                        accThead.appendChild(accHeadRow);
                        accTable.appendChild(accThead);

                        const accTbody = document.createElement('tbody');
                        Object.entries(allNumericTotals).forEach(([key, data]) => {
                            const tr = document.createElement('tr');

                            const tdName = document.createElement('td');
                            tdName.className = 'py-2 border-t px-3';
                            tdName.textContent = data.title;
                            tr.appendChild(tdName);

                            const tdTotal = document.createElement('td');
                            tdTotal.className = 'py-2 border-t text-center px-3 font-bold';
                            tdTotal.textContent = data.total.toLocaleString('id-ID');
                            tr.appendChild(tdTotal);

                            const tdSatuan = document.createElement('td');
                            tdSatuan.className = 'py-2 border-t text-center px-3';
                            tdSatuan.textContent = allSatuans[key] || '-';
                            tr.appendChild(tdSatuan);

                            // Action button cell
                            const tdAction = document.createElement('td');
                            tdAction.className = 'py-2 border-t text-center px-3';
                            
                            const insertBtn = document.createElement('button');
                            insertBtn.className = 'bg-blue-600 text-white px-2 py-2 rounded hover:bg-blue-700 text-sm transition inline-flex items-center justify-center';
                            insertBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M13 3l8 8-8 8v-5c-7 0-11 4-11 9 0-9 4-13 11-13V3z"/></svg>';
                            insertBtn.title = 'Masukkan ke penawaran';
                            insertBtn.addEventListener('click', function() {
                                insertItemToPenawaran(data.title, allAreaBreakdowns[key] || {}, allSatuans[key] || '');
                            });
                            tdAction.appendChild(insertBtn);
                            tr.appendChild(tdAction);

                            accTbody.appendChild(tr);
                        });
                        accTable.appendChild(accTbody);
                        accumulationBody.appendChild(accTable);
                    } else {
                        accumulationBody.innerHTML = '<div class="text-gray-500">Tidak ada data numerik untuk diakumulasi.</div>';
                    }

                    // Reattach event listeners to dokumen buttons after rendering
                    setTimeout(attachDokumenButtonListeners, 100);
                }

                /**
                 * Insert item from akumulasi into penawaran sections by area
                 * @param {string} itemName - The item/tipe name
                 * @param {Object} areaBreakdown - Object with area names as keys and quantities as values
                 * @param {string} satuan - The unit of measurement
                 */
                function insertItemToPenawaran(itemName, areaBreakdown, satuan) {
                    const sectionsContainer = document.getElementById('sectionsContainer');
                    if (!sectionsContainer) {
                        if (window.notyf) notyf.error('Penawaran sections container not found.');
                        return;
                    }

                    // If no area breakdown, use a default "Umum" area
                    if (!areaBreakdown || Object.keys(areaBreakdown).length === 0) {
                        areaBreakdown = { 'Umum': 0 };
                    }

                    let insertedCount = 0;

                    // For each area in the breakdown
                    Object.entries(areaBreakdown).forEach(([areaName, qty]) => {
                        if (qty <= 0) return;

                        // Find existing section with matching area
                        let targetSection = null;
                        const sections = window.penawaranSections || [];
                        
                        for (const section of sections) {
                            const sectionElement = document.getElementById(section.id);
                            if (sectionElement) {
                                const areaInput = sectionElement.querySelector('.area-select');
                                if (areaInput && areaInput.value.toLowerCase() === areaName.toLowerCase()) {
                                    targetSection = section;
                                    break;
                                }
                            }
                        }

                        // If no matching section found, create a new one
                        if (!targetSection) {
                            const sectionData = {
                                nama_section: itemName,
                                area: areaName,
                                data: []
                            };
                            
                            // Trigger add section button click to create new section
                            const addSectionBtn = document.getElementById('addSectionBtn');
                            if (addSectionBtn) {
                                addSectionBtn.click();
                                // Get the newly created section (last one in the array)
                                const updatedSections = window.penawaranSections || [];
                                targetSection = updatedSections[updatedSections.length - 1];
                                
                                // Set the area value for the new section
                                if (targetSection) {
                                    const sectionElement = document.getElementById(targetSection.id);
                                    if (sectionElement) {
                                        const areaInput = sectionElement.querySelector('.area-select');
                                        if (areaInput) areaInput.value = areaName;
                                    }
                                }
                            }
                        }

                        // Now add the item to the spreadsheet
                        if (targetSection && targetSection.spreadsheet) {
                            const spreadsheet = targetSection.spreadsheet;
                            const currentData = spreadsheet.getData();
                            
                            // Find empty row or add new row
                            let emptyRowIdx = -1;
                            for (let i = 0; i < currentData.length; i++) {
                                const row = currentData[i];
                                // Check if row is empty (no tipe, no qty)
                                if (!row[1] && (!row[3] || row[3] === 0 || row[3] === '0')) {
                                    emptyRowIdx = i;
                                    break;
                                }
                            }

                            if (emptyRowIdx === -1) {
                                // Add new row
                                spreadsheet.insertRow();
                                emptyRowIdx = currentData.length;
                            }

                            // Set the values
                            // Column indices: 0=No, 1=Tipe, 2=Deskripsi, 3=QTY, 4=Satuan, ...
                            spreadsheet.setValueFromCoords(1, emptyRowIdx, itemName, true);  // Tipe
                            spreadsheet.setValueFromCoords(3, emptyRowIdx, qty, true);       // QTY
                            spreadsheet.setValueFromCoords(4, emptyRowIdx, satuan, true);    // Satuan

                            insertedCount++;
                        }
                    });

                    if (insertedCount > 0) {
                        if (window.notyf) notyf.success(`Berhasil memasukkan ${insertedCount} item ke penawaran.`);
                    } else {
                        if (window.notyf) notyf.error('Tidak ada item yang dapat dimasukkan.');
                    }
                }

                // Legacy function for backward compatibility (items-based rendering)
                function renderRekapTables(payload) {
                    const container = document.getElementById('rekapSpreadsheet');
                    const accumulationBody = document.getElementById('rekapAccumulationBody');
                    if (!container || !accumulationBody) return;

                    container.innerHTML = '';
                    accumulationBody.innerHTML = '';

                    if (!Array.isArray(payload) || payload.length === 0) {
                        container.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                        accumulationBody.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                        return;
                    }

                    // Sort payload by item id to preserve insertion order (fallbacks handled)
                    const sorted = payload.slice().sort((a, b) => {
                        const ia = (a && typeof a.id !== 'undefined') ? Number(a.id) : 0;
                        const ib = (b && typeof b.id !== 'undefined') ? Number(b.id) : 0;
                        return ia - ib;
                    });

                    // Group by area while preserving the order of first occurrence (based on sorted array)
                    const groups = {};
                    const areaOrder = [];
                    sorted.forEach(it => {
                        const area = (it.nama_area || 'Umum').toString();
                        if (!groups[area]) groups[area] = [];
                        groups[area].push(it);
                        if (!areaOrder.includes(area)) areaOrder.push(area);
                    });

                    // Render each area section in the preserved order
                    areaOrder.forEach(areaName => {
                        const items = groups[areaName] || [];

                        const areaHeader = document.createElement('div');
                        areaHeader.className = 'mb-4 p-4 rounded bg-green-50';
                        areaHeader.innerHTML = `<h3 class="text-lg font-bold text-green-700">${escapeHtml(areaName)}</h3>`;
                        container.appendChild(areaHeader);

                        const tableWrapper = document.createElement('div');
                        tableWrapper.className = 'mb-8 bg-white rounded shadow overflow-auto';

                        const table = document.createElement('table');
                        table.className = 'w-full text-sm border-collapse';

                        const thead = document.createElement('thead');
                        const thRow = document.createElement('tr');
                        ['Kategori','Nama Item','Jumlah','Satuan'].forEach(h => {
                            const th = document.createElement('th');
                            th.className = 'text-left bg-green-100 font-semibold px-3 py-2';
                            th.textContent = h;
                            thRow.appendChild(th);
                        });
                        thead.appendChild(thRow);
                        table.appendChild(thead);

                        const tbody = document.createElement('tbody');
                        items.forEach(it => {
                            const tr = document.createElement('tr');
                            tr.className = 'rekap-item-row hover:bg-gray-50 cursor-pointer';
                            // Store item data for future feature (fetch per item to penawaran)
                            tr.dataset.itemId = it.id;
                            tr.dataset.namaItem = it.nama_item || '';
                            tr.dataset.jumlah = it.jumlah || 0;
                            tr.dataset.satuan = it.satuan || '';
                            tr.dataset.kategoriId = it.kategori && it.kategori.id ? it.kategori.id : '';
                            tr.dataset.kategoriNama = it.kategori && it.kategori.nama ? it.kategori.nama : '';
                            tr.dataset.namaArea = it.nama_area || '';

                            const tdKategori = document.createElement('td');
                            tdKategori.className = 'py-2 border-t px-3';
                            tdKategori.textContent = it.kategori && it.kategori.nama ? it.kategori.nama : (it.kategori || '');
                            tr.appendChild(tdKategori);

                            const tdNama = document.createElement('td');
                            tdNama.className = 'py-2 border-t px-3';
                            tdNama.textContent = it.nama_item || '';
                            tr.appendChild(tdNama);

                            const tdJumlah = document.createElement('td');
                            tdJumlah.className = 'py-2 border-t text-center px-3';
                            tdJumlah.innerHTML = `<strong>${(Number.isInteger(it.jumlah) ? it.jumlah : Number(it.jumlah).toLocaleString('id-ID'))}</strong>`;
                            tr.appendChild(tdJumlah);

                            const tdSatuan = document.createElement('td');
                            tdSatuan.className = 'py-2 border-t text-right px-3';
                            tdSatuan.textContent = it.satuan || '-';
                            tr.appendChild(tdSatuan);

                            tbody.appendChild(tr);
                        });

                        table.appendChild(tbody);
                        tableWrapper.appendChild(table);
                        container.appendChild(tableWrapper);
                    });

                    // Akumulasi (subtotal semua area) 
                    const map = {};
                    payload.forEach(it => {
                        const nama = (it.nama_item || '').toString().trim();
                        const satuan = (it.satuan || '').toString().trim();
                        const jumlah = parseFloat(it.jumlah) || 0;
                        if (!nama) return;
                        const key = nama + '||' + satuan;
                        if (!map[key]) map[key] = { nama, satuan, jumlah: 0 };
                        map[key].jumlah += jumlah;
                    });

                    const items = Object.values(map).sort((a,b) => a.nama.localeCompare(b.nama));

                    if (items.length === 0) {
                        accumulationBody.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                        return;
                    }

                    const accTable = document.createElement('table');
                    accTable.className = 'w-full text-sm border-collapse';

                    const accThead = document.createElement('thead');
                    const accHeadRow = document.createElement('tr');
                    const th1 = document.createElement('th'); th1.className = 'text-left font-semibold pb-2 bg-blue-100 px-3 py-2'; th1.textContent = 'Nama Item'; accHeadRow.appendChild(th1);
                    const th2 = document.createElement('th'); th2.className = 'text-center font-semibold pb-2 bg-blue-100 px-3 py-2'; th2.textContent = 'Total Jumlah'; accHeadRow.appendChild(th2);
                    const th3 = document.createElement('th'); th3.className = 'text-right font-semibold pb-2 bg-blue-100 px-3 py-2'; th3.textContent = 'Satuan'; accHeadRow.appendChild(th3);
                    accThead.appendChild(accHeadRow);
                    accTable.appendChild(accThead);

                    const accTbody = document.createElement('tbody');
                    items.forEach(it => {
                        const tr = document.createElement('tr');

                        const tdName = document.createElement('td'); tdName.className = 'py-2 border-t px-3'; tdName.textContent = it.nama; tr.appendChild(tdName);

                        const tdJumlah = document.createElement('td'); tdJumlah.className = 'py-2 border-t text-center px-3';
                        const jumlahFormatted = Number.isInteger(it.jumlah) ? it.jumlah.toLocaleString('id-ID') : it.jumlah.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                        tdJumlah.innerHTML = `<strong>${jumlahFormatted}</strong>`;
                        tr.appendChild(tdJumlah);

                        const tdSatuan = document.createElement('td'); tdSatuan.className = 'py-2 border-t text-right px-3'; tdSatuan.textContent = it.satuan || '-'; tr.appendChild(tdSatuan);

                        accTbody.appendChild(tr);
                    });
                    accTable.appendChild(accTbody);
                    accumulationBody.appendChild(accTable);
                }

                // reuse escapeHtml from file if present, otherwise provide:
                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                }

                // Hook up Import modal load action: when payload is returned, render tables
                if (loadBtn) {
                    loadBtn.onclick = function() {
                        const rekapId = dropdown.value;
                        if (!rekapId) {
                            if (window.notyf) notyf.error('Silakan pilih rekap terlebih dahulu');
                            return;
                        }

                        const csrf = getCsrfToken();
                        fetch(`/rekap/${rekapId}/import`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ penawaran_id: {{ $penawaran->id_penawaran }} })
                        })
                        .then(async response => {
                            if (!response.ok) {
                                if (response.status === 403) {
                                    if (window.notyf) notyf.error('Rekap ini sudah diimport oleh user lain.');
                                    throw new Error('Forbidden');
                                }
                                const err = await response.json().catch(() => ({}));
                                throw err;
                            }
                            return response.json();
                        })
                        .then(payload => {
                            if (window.notyf) notyf.success('Rekap berhasil dimuat. Halaman akan di-refresh...');
                            modal.classList.add("hidden");
                            
                            // Refresh the page to show only the selected rekap
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        })
                        .catch(err => {
                            console.error('Error loading rekap import:', err);
                            if (window.notyf) notyf.error('Gagal memuat rekap.');
                        });
                    };
                }

                // Initial load for this penawaran: render surveys if exists
                fetch(`/rekap/surveys-for-penawaran/{{ $penawaran->id_penawaran }}`)
                .then(res => res.json())
                .then(response => {
                    if (response && response.success && response.rekaps && response.rekaps.length > 0) {
                        renderRekapSurveys(response);
                    } else {
                        // Fallback to legacy items-based endpoint
                        fetch(`/rekap/for-penawaran/{{ $penawaran->id_penawaran }}`)
                        .then(res2 => res2.json())
                        .then(payload => {
                            if (Array.isArray(payload) && payload.length > 0) {
                                renderRekapTables(payload);
                            } else {
                                // show empty state
                                const container = document.getElementById('rekapSpreadsheet');
                                const accBody = document.getElementById('rekapAccumulationBody');
                                if (container) container.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                                if (accBody) accBody.innerHTML = '<div class="text-gray-500">Belum ada data rekap.</div>';
                            }
                        })
                        .catch(e2 => {
                            console.error('Failed to load legacy rekap items:', e2);
                        });
                    }
                })
                .catch(e => {
                    console.error('Failed to load rekap surveys for this penawaran:', e);
                    // show empty state
                    const container = document.getElementById('rekapSpreadsheet');
                    if (container) container.innerHTML = '<div class="text-gray-500">Gagal memuat data rekap.</div>';
                });

                // =====================================================
                // SUPPORTING DOCUMENTS MODAL FUNCTIONS
                // =====================================================
                
                // Function to render modal content with supporting documents
                window.renderSupportDocuments = function(documents) {
                    const modal = document.getElementById('rekapSupportDocModal');
                    const contentDiv = modal.querySelector('#rekapSupportDocContent');
                    
                    if (!documents || documents.length === 0) {
                        contentDiv.innerHTML = '<div class="text-center text-gray-500 py-8">Tidak ada dokumen pendukung.</div>';
                        return;
                    }

                    let html = '<div class="space-y-4">';
                    documents.forEach((doc, index) => {
                        const fileExt = doc.filename.split('.').pop().toLowerCase();
                        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExt);
                        const isPdf = fileExt === 'pdf';
                        const isDoc = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileExt);
                        
                        let icon = '📄'; // default document icon
                        if (isImage) icon = '🖼️';
                        else if (isPdf) icon = '📕';
                        else if (isDoc) icon = '📑';

                        const createdDate = new Date(doc.created_at).toLocaleDateString('id-ID', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        html += `
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl">${icon}</span>
                                            <p class="font-medium text-gray-900 break-words">${doc.filename}</p>
                                        </div>
                                        ${doc.notes ? `<p class="text-sm text-gray-600 mb-2"><strong>Catatan:</strong> ${doc.notes}</p>` : ''}
                                        <p class="text-xs text-gray-500">Ditambahkan: ${createdDate}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <a href="{{ route('upload.download') }}?path=${encodeURIComponent(doc.file_path)}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                        Download
                                    </a>
                                    ${(isImage || isPdf) ? `
                                        <button class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 transition preview-btn"
                                                data-path="${doc.file_path}"
                                                data-type="${isImage ? 'image' : 'pdf'}"
                                                data-name="${doc.filename}">
                                            Pratinjau
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';

                    contentDiv.innerHTML = html;

                    // Attach preview button listeners
                    contentDiv.querySelectorAll('.preview-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const path = this.dataset.path;
                            const type = this.dataset.type;
                            const name = this.dataset.name;
                            window.openFilePreview(path, type, name);
                        });
                    });
                };

                // Function to open the support documents modal
                window.openSupportDocModal = function(documents) {
                    const modal = document.getElementById('rekapSupportDocModal');
                    if (modal) {
                        window.renderSupportDocuments(documents);
                        modal.classList.remove('hidden');
                    }
                };

                // Function to close support documents modal
                window.closeSupportDocModal = function() {
                    const modal = document.getElementById('rekapSupportDocModal');
                    if (modal) {
                        modal.classList.add('hidden');
                    }
                };

                // Function to preview file
                window.openFilePreview = function(path, type, name) {
                    if (type === 'image') {
                        const previewHtml = `
                            <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" id="imagePreviewModal">
                                <div class="bg-white rounded-lg p-4 max-w-2xl max-h-[90vh] overflow-auto relative">
                                    <button class="absolute top-2 right-2 bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
                                            onclick="document.getElementById('imagePreviewModal').remove()">✕</button>
                                    <h3 class="text-lg font-bold mb-4">${name}</h3>
                                    <img src="{{ route('upload.download') }}?path=${encodeURIComponent(path)}" 
                                         alt="${name}"
                                         class="max-w-full h-auto">
                                </div>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', previewHtml);
                    } else if (type === 'pdf') {
                        // Fetch PDF as blob and create object URL for preview
                        fetch("{{ route('upload.download') }}?path=" + encodeURIComponent(path))
                            .then(response => response.blob())
                            .then(blob => {
                                const pdfUrl = URL.createObjectURL(blob);
                                const previewHtml = `
                                    <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" id="pdfPreviewModal">
                                        <div class="bg-white rounded-lg p-4 max-w-4xl max-h-[90vh] overflow-auto relative w-11/12">
                                            <button class="absolute top-2 right-2 bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 z-10"
                                                    onclick="URL.revokeObjectURL('${pdfUrl}'); document.getElementById('pdfPreviewModal').remove()">✕</button>
                                            <h3 class="text-lg font-bold mb-4 pr-8">${name}</h3>
                                            <iframe src="${pdfUrl}" 
                                                    class="w-full h-[80vh] border rounded"
                                                    frameborder="0"></iframe>
                                        </div>
                                    </div>
                                `;
                                document.body.insertAdjacentHTML('beforeend', previewHtml);
                            })
                            .catch(err => {
                                console.error('Error loading PDF:', err);
                                if (window.notyf) notyf.error('Gagal memuat PDF.');
                            });
                    }
                };

                // Function to show comment modal
                window.showCommentModal = function(comment) {
                    const modalHtml = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="commentDisplayModal">
                            <div class="bg-white rounded-lg p-6 max-w-lg mx-4 max-h-[80vh] overflow-y-auto">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-900">Komentar</h3>
                                    <button class="text-gray-500 hover:text-gray-700 text-2xl"
                                            onclick="document.getElementById('commentDisplayModal').remove()">×</button>
                                </div>
                                <div class="text-sm text-gray-700 bg-blue-50 p-3 rounded border border-blue-200 break-words">
                                    ${escapeHtml(comment)}
                                </div>
                                <div class="flex justify-end gap-2 mt-6">
                                    <button class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                                            onclick="document.getElementById('commentDisplayModal').remove()">Tutup</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                };

                // Attach event listeners to dokumen-btn elements
                function attachDokumenButtonListeners() {
                    document.querySelectorAll('.dokumen-btn').forEach(btn => {
                        btn.removeEventListener('click', handleDokumenClick);
                        btn.addEventListener('click', handleDokumenClick);
                    });
                }

                function handleDokumenClick(e) {
                    e.preventDefault();
                    const docs = JSON.parse(this.dataset.docs || '[]');
                    window.openSupportDocModal(docs);
                }

                // Attach listeners after initial render
                attachDokumenButtonListeners();

                // Attach close button listeners for support doc modal
                const rekapSupportDocClose = document.getElementById('rekapSupportDocClose');
                const rekapSupportDocCloseBtn = document.getElementById('rekapSupportDocCloseBtn');
                if (rekapSupportDocClose) {
                    rekapSupportDocClose.addEventListener('click', window.closeSupportDocModal);
                }
                if (rekapSupportDocCloseBtn) {
                    rekapSupportDocCloseBtn.addEventListener('click', window.closeSupportDocModal);
                }

                // Close modal on background click
                const rekapSupportDocModal = document.getElementById('rekapSupportDocModal');
                if (rekapSupportDocModal) {
                    rekapSupportDocModal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            window.closeSupportDocModal();
                        }
                    });
                }
                    

                // =====================================================
                // SLIDER VERIFICATION LOGIC
                // =====================================================
                document.addEventListener('DOMContentLoaded', function() {
                    const slider = document.getElementById('verificationSlider');
                    const sliderThumb = document.getElementById('sliderThumb');
                    const sliderTrack = document.getElementById('sliderTrack');
                    const sliderText = document.getElementById('sliderText');
                    const sliderHeader = document.getElementById('verificationHeaderText');
                    const ringkasanInput = document.getElementById('ringkasan');
                    const noteInput = document.getElementById('note');
                    const initialRequestSent = @json((bool) $approval);
                    const initialApprovalStatus = @json($approval->status ?? null);

                    if (!slider) return; // Skip if not on staff role

                    let isDragging = false;
                    let hasRequestedVerification = initialRequestSent;
                    let currentX = 0; // Track current position

                    function lockSlider(text = '✅ Permintaan verifikasi telah dikirim') {
                        const rect = slider.getBoundingClientRect();
                        if (!rect.width) {
                            // Retry after layout (e.g. when tab becomes visible)
                            setTimeout(() => lockSlider(text), 120);
                            return;
                        }

                        const maxLeft = rect.width - sliderThumb.offsetWidth - 4;
                        sliderThumb.style.transition = 'left 0.25s ease';
                        sliderTrack.style.transition = 'width 0.25s ease';
                        sliderThumb.style.left = `${maxLeft}px`;
                        sliderTrack.style.width = '100%';
                        slider.style.pointerEvents = 'none';
                        slider.style.opacity = '0.7';
                        sliderText.textContent = text;
                        sliderText.style.opacity = '1';
                        if (sliderHeader) {
                            sliderHeader.textContent = text.replace('✅ ', '');
                        }
                        currentX = maxLeft;
                        hasRequestedVerification = true;
                        setTimeout(() => {
                            sliderThumb.style.transition = 'none';
                            sliderTrack.style.transition = 'none';
                        }, 250);
                    }
                    
                    // Calculate slider width properly
                    function getSliderWidth() {
                        return slider.offsetWidth - sliderThumb.offsetWidth - 8;
                    }

                    // Get CSRF token from Laravel
                    function getCsrfToken() {
                        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                               document.querySelector('input[name="_token"]')?.value || 
                               '{{ csrf_token() }}';
                    }

                    // Validasi data Penawaran/Jasa sebelum slider bisa dipakai
                    function validateBeforeSlider() {
                        let errors = [];

                        // Tipe kosong (default) -> semua aktif validasi
                        if (!tipe) {
                            if (typeof window.getPenawaranValidationErrors === 'function') {
                                errors = errors.concat(window.getPenawaranValidationErrors());
                            }
                            if (typeof window.getJasaValidationErrors === 'function') {
                                errors = errors.concat(window.getJasaValidationErrors());
                            }
                        } else if (tipe === 'barang') {
                            // Tipe Barang -> hanya validasi Tab Penawaran
                            if (typeof window.getPenawaranValidationErrors === 'function') {
                                errors = errors.concat(window.getPenawaranValidationErrors());
                            }
                        } else if (tipe === 'soc') {
                            // Tipe SOC -> hanya validasi Rincian Jasa
                            if (typeof window.getJasaValidationErrors === 'function') {
                                errors = errors.concat(window.getJasaValidationErrors());
                            }
                        }

                        if (errors.length > 0) {
                            const messageHtml = errors
                                .map((e, idx) => `${idx + 1}. ${e}`)
                                .join('<br>');

                            notyf.error({
                                message: `<strong>Validasi Data:</strong><br>${messageHtml}`,
                                duration: 7000
                            });
                            return false;
                        }

                        return true;
                    }

                    // Function to check validation
                    function checkPreviewValidation() {
                        const requireRingkasan = typeof showJasa !== 'undefined' ? showJasa : true;
                        const ringkasanFilled = !requireRingkasan || (ringkasanInput && ringkasanInput.value.trim().length > 0);
                        const notesFilled = noteInput && noteInput.value.trim().length > 0;

                        // Update validation UI
                        const checkRingkasan = document.getElementById('checkRingkasan');
                        const checkNotes = document.getElementById('checkNotes');

                        if (checkRingkasan) {
                            if (ringkasanFilled) {
                                checkRingkasan.innerHTML = '<span class="mr-2">✅</span> Ringkasan Jasa sudah diisi';
                                checkRingkasan.classList.remove('text-yellow-700');
                                checkRingkasan.classList.add('text-green-700');
                            } else {
                                checkRingkasan.innerHTML = '<span class="mr-2">❌</span> Ringkasan Jasa sudah diisi';
                                checkRingkasan.classList.remove('text-green-700');
                                checkRingkasan.classList.add('text-yellow-700');
                            }
                        }

                        if (checkNotes) {
                            if (notesFilled) {
                                checkNotes.innerHTML = '<span class="mr-2">✅</span> Notes sudah diisi';
                                checkNotes.classList.remove('text-yellow-700');
                                checkNotes.classList.add('text-green-700');
                            } else {
                                checkNotes.innerHTML = '<span class="mr-2">❌</span> Notes sudah diisi';
                                checkNotes.classList.remove('text-green-700');
                                checkNotes.classList.add('text-yellow-700');
                            }
                        }

                        return notesFilled && ringkasanFilled;
                    }

                    // Spring back animation
                    function springBack() {
                        if (hasRequestedVerification) return;
                        sliderThumb.style.transition = 'left 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
                        sliderTrack.style.transition = 'width 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
                        sliderThumb.style.left = '4px';
                        sliderTrack.style.width = '0%';
                        currentX = 0;
                        setTimeout(() => {
                            sliderThumb.style.transition = 'none';
                            sliderTrack.style.transition = 'none';
                        }, 300);
                    }

                    // Initial validation check
                    checkPreviewValidation();

                    // Lock slider if request already exists
                    if (initialRequestSent) {
                        requestAnimationFrame(() => {
                            const lockedMessage = initialApprovalStatus === 'fully_approved'
                                ? '✅ Permintaan verifikasi telah disetujui'
                                : '✅ Permintaan verifikasi telah dikirim!';
                            lockSlider(lockedMessage);
                        });
                    }

                    // Re-lock when preview tab becomes visible (width becomes non-zero)
                    document.addEventListener('previewTabShown', () => {
                        if (hasRequestedVerification || initialRequestSent) {
                            lockSlider(sliderText.textContent || '✅ Permintaan verifikasi telah dikirim!');
                        }
                    });

                    // Update validation on input change
                    if (ringkasanInput) ringkasanInput.addEventListener('input', checkPreviewValidation);
                    if (noteInput) noteInput.addEventListener('input', checkPreviewValidation);

                    // Submit verification request to backend
                    function submitVerificationRequest() {
    if (hasRequestedVerification) return;
    hasRequestedVerification = true;

    const penawaranIdInput = document.getElementById('penawaranId');
    const versionIdInput   = document.getElementById('versionId');

    let penawaranId = penawaranIdInput && penawaranIdInput.value
        ? parseInt(penawaranIdInput.value)
        : {{ $penawaran->id_penawaran }};

    let versionId = versionIdInput && versionIdInput.value
        ? parseInt(versionIdInput.value)
        : {{ $activeVersionId }};

    const csrfToken = getCsrfToken();

    console.log('Submitting verification request...', {
        penawaranId,
        versionId
    });

    fetch('{{ route("export-approval.submit") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            penawaran_id: penawaranId,
            version_id: versionId
        })
    })
    .then(async response => {
    console.log('Response status:', response.status);

    const data = await response.json();

    if (!response.ok) {
        throw data;
    }

    return data;
})
    .then(data => {
        if (data.success) {
            notyf.success('✅ ' + data.message);
            lockSlider('✅ Permintaan verifikasi telah dikirim!');
        } else {
            throw new Error(data.message || 'Request gagal');
        }
    })
    .catch(error => {
    console.error('Full error:', error);

    if (error.notify && error.notify.message) {
        notyf.error('❌ ' + error.notify.message);
    } else if (error.message) {
        notyf.error('❌ ' + error.message);
    } else {
        notyf.error('❌ Terjadi kesalahan');
    }

    hasRequestedVerification = false;
    springBack();
});
}

                    // Mouse events - DRAG ONLY WHEN MOVING MOUSE
                    slider.addEventListener('mousedown', (e) => {
                        if (hasRequestedVerification) return;
                        // Validasi detail Penawaran/Jasa dulu
                        if (!validateBeforeSlider()) return;
                        if (!checkPreviewValidation()) {
                            const msg = (typeof showJasa !== 'undefined' && showJasa)
                                ? '⚠️ Silakan isi Ringkasan Jasa dan Notes terlebih dahulu!'
                                : '⚠️ Silakan isi Notes terlebih dahulu!';
                            notyf.error(msg);
                            return;
                        }
                        isDragging = true;
                        updateSlider(e);
                    });

                    document.addEventListener('mousemove', (e) => {
                        if (!isDragging) return;
                        updateSlider(e);
                    });

                    document.addEventListener('mouseup', () => {
                        if (!isDragging) return;
                        isDragging = false;
                        
                        // Check if slider reached >90% completion
                        const sliderWidth = getSliderWidth();
                        if (currentX / sliderWidth < 0.90) {
                            // Spring back if not fully dragged
                            springBack();
                        }
                    });

                    function updateSlider(e) {
                        if (hasRequestedVerification) return; // Don't allow dragging after request sent
                        
                        const sliderWidth = getSliderWidth();
                        const rect = slider.getBoundingClientRect();
                        let x = e.clientX - rect.left;

                        // Constrain x within bounds (0 to sliderWidth)
                        x = Math.max(0, Math.min(x, sliderWidth + 4));
                        currentX = x;

                        const progress = x / sliderWidth;
                        const percentage = Math.min(100, Math.max(0, Math.round(progress * 100)));

                        // Position thumb - smooth without transition
                        sliderThumb.style.left = x + 'px';
                        // Fill track proportionally
                        sliderTrack.style.width = (x / rect.width * 100) + '%';

                        if (progress >= 0.90) {
                            sliderText.textContent = '✅ Verified!';
                            sliderText.style.opacity = '0';
                            // Submit verification on completion
                            submitVerificationRequest();
                        } else {
                            sliderText.textContent = percentage + '%';
                            sliderText.style.opacity = '1';
                        }
                    }

                    // Touch events for mobile - DRAG ONLY WHEN TOUCHING
                    slider.addEventListener('touchstart', (e) => {
                        if (hasRequestedVerification) return;
                        // Validasi detail Penawaran/Jasa dulu
                        if (!validateBeforeSlider()) return;
                        if (!checkPreviewValidation()) {
                            const msg = (typeof showJasa !== 'undefined' && showJasa)
                                ? '⚠️ Silakan isi Ringkasan Jasa dan Notes terlebih dahulu!'
                                : '⚠️ Silakan isi Notes terlebih dahulu!';
                            notyf.error(msg);
                            return;
                        }
                        isDragging = true;
                        updateSlider(e.touches[0]);
                    });

                    document.addEventListener('touchmove', (e) => {
                        if (!isDragging) return;
                        updateSlider(e.touches[0]);
                    });

                    document.addEventListener('touchend', () => {
                        if (!isDragging) return;
                        isDragging = false;
                        
                        // Check if slider reached >90% completion
                        const sliderWidth = getSliderWidth();
                        if (currentX / sliderWidth < 0.90) {
                            // Spring back if not fully dragged
                            springBack();
                        }
                    });

                    // =======================
                    // TAB DISABLE LOGIC FOR TEMPLATE BoQ
                    // =======================
                    const templateType = '{{ $penawaran->template_type }}';
                    if (templateType === 'template_boq') {
                        const tabButtons = document.querySelectorAll('.tab-btn');
                        const disabledTabs = ['penawaran', 'Jasa', 'preview', 'rekap'];
                        
                        tabButtons.forEach(btn => {
                            const tabName = btn.getAttribute('data-tab');
                            if (disabledTabs.includes(tabName)) {
                                btn.classList.add('locked');
                                btn.disabled = true;
                                btn.style.opacity = '0.5';
                                btn.style.cursor = 'not-allowed';
                                btn.title = 'Tab ini dinonaktifkan untuk Template BoQ';
                            }
                        });

                        // Auto-navigate to dokumen tab if template_boq
                        const dokumenBtn = Array.from(tabButtons).find(btn => btn.getAttribute('data-tab') === 'dokumen');
                        if (dokumenBtn) {
                            setTimeout(() => {
                                dokumenBtn.click();
                            }, 100);
                        }
                    }

                    // =======================
                    // SUPPORTING DOCUMENTS FUNCTIONALITY
                    // =======================
                    const supportDocForm = document.getElementById('supportDocForm');
                    const uploadBtn = document.getElementById('uploadSupportDocBtn');
                    const supportDocFile = document.getElementById('supportDocFile');

                    if (supportDocForm) {
                        supportDocForm.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            const file = supportDocFile.files[0];
                            const notes = document.getElementById('supportDocNotes').value;
                            
                            if (!file) {
                                alert('Pilih file terlebih dahulu');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('notes', notes);
                            formData.append('_token', document.querySelector('input[name="_token"]').value);
                            
                            uploadBtn.disabled = true;
                            uploadBtn.textContent = 'Uploading...';

                            try {
                                const response = await fetch('{{ route("penawaran.uploadSupportDoc", $penawaran->id_penawaran) }}', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                const data = await response.json();

                                if (data.success) {
                                    supportDocFile.value = '';
                                    document.getElementById('supportDocNotes').value = '';
                                    loadSupportingDocuments();
                                    notyf.success('Dokumen berhasil diupload');
                                } else {
                                    notyf.error(data.message || 'Gagal upload dokumen');
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                notyf.error('Error uploading dokumen');
                            } finally {
                                uploadBtn.disabled = false;
                                uploadBtn.textContent = 'Upload Dokumen Pendukung';
                            }
                        });
                    }

                    // Load supporting documents function
                    function loadSupportingDocuments() {
                        const penId = {{ $penawaran->id_penawaran }};
                        fetch(`/penawaran/${penId}/supporting-documents`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            const container = document.getElementById('supportDocsList');
                            if (data.documents && data.documents.length > 0) {
                                container.innerHTML = data.documents.map(doc => `
                                    <div class="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded mb-3" data-doc-id="${doc.id}">
                                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900">${doc.original_filename}</p>
                                            ${doc.notes ? `<p class="text-sm text-gray-600 mt-1">${doc.notes}</p>` : ''}
                                            <p class="text-xs text-gray-500 mt-1">Diupload: ${new Date(doc.created_at).toLocaleDateString('id-ID', {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'})}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="/penawaran/${penId}/download-support-doc?doc_id=${doc.id}"
                                               class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-sm">
                                                Download
                                            </a>
                                            <button type="button" onclick="deleteSupportDoc(${doc.id}, ${penId})"
                                                class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700 text-sm">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                `).join('');
                            } else {
                                container.innerHTML = '<div class="text-center text-gray-500 py-8"><p>Belum ada dokumen pendukung.</p></div>';
                            }
                        });
                    }

                    // Delete supporting document function
                    window.deleteSupportDoc = function(docId, penId) {
                        if (!confirm('Yakin ingin menghapus dokumen ini?')) return;
                        
                        fetch(`/penawaran/${penId}/delete-support-doc/${docId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                loadSupportingDocuments();
                                notyf.success('Dokumen berhasil dihapus');
                            } else {
                                notyf.error(data.message || 'Gagal menghapus dokumen');
                            }
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            notyf.error('Error menghapus dokumen');
                        });
                    };
                });
            </script>
        @endpush