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
        <a href="{{ route('penawaran.list') }}" class="flex items-center hover:text-green-600">
            <x-lucide-arrow-left class="w-5 h-5 mr-2" />
            List Penawaran
        </a>
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
            <button type="button" onclick="openStatusModal('draft')"
                class="bg-[#FFA500] text-white px-2 py-2 rounded hover:shadow-lg font-semibold">
                <x-lucide-file-edit class="w-6 h-6 inline-block" />
            </button>
            <button type="button" onclick="openStatusModal('lost')"
                class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-600 font-semibold">
                <x-lucide-badge-x class="w-6 h-6 inline-block" />
            </button>
            <button type="button" onclick="openStatusModal('success')"
                class="bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 font-semibold">
                <x-lucide-badge-check class="w-6 h-6 inline-block" />
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
                                {{ ucfirst($penawaran->status) }}
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
            <div class="flex border-b mb-4">
                <button
                    class="tab-btn px-4 py-2 font-semibold text-green-600 border-b-2 border-green-600 focus:outline-none"
                    data-tab="penawaran">Penawaran</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="Jasa">Rincian Jasa</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="preview">Preview</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="rekap">Rincian Rekap</button>
            </div>

            <div id="tabContent">
                <!-- Panel Penawaran -->
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
                <!-- Panel Jasa -->
                <div class="tab-panel hidden" data-tab="Jasa">
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
                <!-- Panel Preview -->
                <div class="tab-panel hidden" data-tab="preview">
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
                                        <li id="checkRingkasan" class="flex items-center"><span class="mr-2">❌</span> Ringkasan Jasa sudah diisi</li>
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
                                        
                                        if ($approvedBy) {
                                            $approver = \App\Models\User::find($approvedBy);
                                            $approverName = $approver ? $approver->name : 'Unknown';
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
                                Bersama ini kami PT. Puterako Inti Buana memberitahukan Penawaran Harga
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
                                                <th class="border border-gray-300 px-3 py-2 text-center w-32" style="color: #ef4444; font-weight: bold;">Keterangan</th>
                                                <th class="border border-gray-300 px-3 py-2 text-right w-32">Harga
                                                    Total
                                                </th>
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
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['tipe'] }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['deskripsi'] }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ number_format($row['qty'], 0) }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['satuan'] }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-right">
    @if ((int) $row['is_mitra'] === 1)
        <span style="color:#3498db;font-weight:bold;font-style:italic;">
            by User
        </span>
    @else
        {{ $row['harga_satuan'] > 0 ? 'Rp ' . number_format($row['harga_satuan'], 0, ',', '.') : '' }}
    @endif
</td>
<td class="border border-gray-300 px-3 py-2 text-center" style="color: #ef4444; font-weight: bold;">{{ $row['delivery_time'] ?? '-' }}</td>
<td class="border border-gray-300 px-3 py-2 text-right">
    @if ((int) $row['is_mitra'] === 1)
        <span style="color:#3498db;font-weight:bold;font-style:italic;">
            by User
        </span>
    @else
        {{ $row['harga_total'] > 0 ? 'Rp ' . number_format($row['harga_total'], 0, ',', '.') : '' }}
    @endif
</td>
                                                            
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7" class="text-center font-bold bg-gray-50">Subtotal
                                                </td>
                                                <td class="border border-gray-300 px-3 py-2 text-right font-bold">
                                                    Rp {{ number_format($subtotal, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endforeach

                        <!-- Tabel Jasa Detail (hanya sekali di bawah semua section) -->
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
                                            Rp {{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</td>
                                        <td class="border border-gray-300 px-3 py-2 text-right">
                                            Rp {{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-center font-bold bg-gray-50">Subtotal</td>
                                        <td class="border border-gray-300 px-3 py-2 text-right font-bold">
                                            Rp {{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

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

                                                // Total keseluruhan = total penawaran + jasa grand total
                                                $totalKeseluruhan = $totalPenawaran + ($versionRow->jasa_grand_total ?? 0);
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

                                    @if ($isBest)
                                        <tr>
                                            <td class="py-2 font-semibold">Best Price</td>
                                            <td class="py-2 text-right">Rp
                                                {{ number_format($bestPrice, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="py-2 font-semibold">PPN {{ number_format($ppnPersen, 0, ',', '.') }}%
                                        </td>
                                        <td class="py-2 text-right">Rp
                                            @php
                                                $baseAmountForPPN = $isBest && $bestPrice > 0 ? $bestPrice : $totalKeseluruhan;
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

                        <!-- Notes -->
                        <div class="mt-8 mb-6">
                            <form method="POST" action="{{ route('penawaran.saveNotes', $penawaran->id_penawaran) }}" id="notesForm">
                                @csrf
                                <input type="hidden" name="version" value="{{ $activeVersion }}">
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
@endsection

        <script>
            const activeVersion = {{ $activeVersion ?? 0 }};
        </script>
        <script>
            const satuanOptions = @json($satuans->pluck('nama'));
        </script>

        @push('scripts')
            <script>
                // Data awal dari backend
                const initialSections = @json($sections);
                const hasExistingData = initialSections.length > 0;
            </script>
            <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
            <link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />
            <script src="https://jsuites.net/v4/jsuites.js"></script>
            <script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>

            <script>
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
                        modalTitle.textContent = 'Tandai Penawaran Selesai';
                        submitBtn.textContent = 'Tandai Selesai';
                        submitBtn.className = 'px-4 py-2 text-white bg-green-500 rounded hover:bg-green-600';
                        noteInput.placeholder = 'Masukkan catatan penyelesaian penawaran...';
                    } else if (status === 'lost') {
                        modalTitle.textContent = 'Tandai Penawaran Gagal';
                        submitBtn.textContent = 'Tandai Gagal';
                        submitBtn.className = 'px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600';
                        noteInput.placeholder = 'Masukkan alasan penawaran gagal...';
                    }

                    modal.classList.remove('hidden');
                }

                function closeStatusModal() {
                    const modal = document.getElementById('statusModal');
                    modal.classList.add('hidden');
                }

                // Close modal when clicking outside
                document.getElementById('statusModal').addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeStatusModal();
                    }
                });
            </script>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // =====================================================
                    // DEKLARASI VARIABEL
                    // =====================================================

                    // Variabel Penawaran
                    let sections = [];
                    let sectionCounter = 0;
                    let isEditMode = !hasExistingData;

                    // Variabel Jasa
                    let jasaSections = [];
                    let jasaSectionCounter = 0;
                    let jasaInitialSections = [];
                    let jasaProfit = 0;
                    let jasaPph = 0;
                    let jasaIsEditMode = false;
                    
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
                            
                            if (tab === 'Jasa' && !penawaranSaved) {
                                btn.classList.add('locked');
                            } else if (tab === 'preview' && (!penawaranSaved || !jasaSaved)) {
                                btn.classList.add('locked');
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
                    // TAB SWITCHING LOGIC
                    // =====================================================

                    const tabButtons = document.querySelectorAll('.tab-btn');
                    const tabPanels = document.querySelectorAll('.tab-panel');

                    tabButtons.forEach(button => {
                        button.addEventListener('click', function () {
                            const targetTab = this.getAttribute('data-tab');

                            // Validasi sebelum switch tab
                            if (targetTab === 'Jasa' && !penawaranSaved) {
                                if (!isPenawaranComplete()) {
                                    notyf.error('⚠️ Silakan lengkapi dan simpan data Penawaran terlebih dahulu!');
                                    return;
                                }
                            }
                            
                            if (targetTab === 'preview' && (!penawaranSaved || !jasaSaved)) {
                                let errorMsg = '';
                                if (!penawaranSaved) {
                                    errorMsg = '⚠️ Silakan lengkapi dan simpan data Penawaran terlebih dahulu!';
                                } else if (!jasaSaved) {
                                    errorMsg = '⚠️ Silakan lengkapi dan simpan data Rincian Jasa terlebih dahulu!';
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

                                document.getElementById('jasaProfitInput').value = jasaProfit;
                                document.getElementById('jasaPphInput').value = jasaPph;

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

                    document.getElementById('jasaAddSectionBtn').addEventListener('click', () => {
                        createJasaSection(null, jasaIsEditMode);
                    });

                    document.getElementById('jasaEditModeBtn').addEventListener('click', () => {
                        if (jasaSections.length === 0) {
                            // buat satu section kosong ketika belum ada data
                            createJasaSection(null, true);
                        }
                        toggleJasaEditMode(true);
                    });

                    document.getElementById('jasaCancelEditBtn').addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

                    function toggleJasaEditMode(enable) {
                        jasaIsEditMode = enable;

                        const btnEdit = document.getElementById('jasaEditModeBtn');
                        const btnCancel = document.getElementById('jasaCancelEditBtn');
                        const btnSave = document.getElementById('jasaSaveAllBtn');
                        const btnAdd = document.getElementById('jasaAddSectionBtn');

                        if (jasaHasExistingData) {
                            btnEdit.classList.toggle('hidden', enable);
                            btnCancel.classList.toggle('hidden', !enable);
                        } else {
                            // Tidak ada data → tidak perlu tombol Edit / Batal
                            btnEdit.classList.add('hidden');
                            btnCancel.classList.add('hidden');
                        }

                        // Save selalu tampil (sama seperti Penawaran)
                        btnSave.classList.remove('hidden');
                        // Add Section hanya saat edit
                        btnAdd.classList.toggle('hidden', !enable);

                        document.getElementById('jasaProfitInput').disabled = !enable;
                        document.getElementById('jasaPphInput').disabled = !enable;

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

                    document.getElementById('jasaEditModeBtn').addEventListener('click', () => {
                        toggleJasaEditMode(true);
                    });

                    document.getElementById('jasaCancelEditBtn').addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

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
                        ]) : [
                            ['', '', 0, 0, 0, 0, 0],
                            ['', '', 0, 0, 0, 0, 0],
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
                                                    min="0" step="1" value="${sectionData && typeof sectionData.pembulatan !== 'undefined' ? sectionData.pembulatan : 0}">
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
                            }
                        });

                        const sectionElement = document.getElementById(sectionId);

                        sectionElement.querySelector('.add-row-btn').addEventListener('click', () => {
                            spreadsheet.insertRow();
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
                        const useBpjs = document.getElementById('jasaUseBpjs').checked;
                        const bpjsPercent = {{ $versionRow->jasa_bpjsk_percent ?? 0 }};
                        
                        let bpjsValue = 0;
                        let grandTotal = totalGrand;
                        
                        if (useBpjs && bpjsPercent > 0) {
                            bpjsValue = (totalGrand * bpjsPercent) / 100;
                            grandTotal = totalGrand + bpjsValue;
                        }
                        
                        // Update UI
                        const bpjsValueEl = document.getElementById('jasaBpjsValue');
                        if (bpjsValueEl) bpjsValueEl.textContent = Math.round(bpjsValue).toLocaleString('id-ID');
                        
                        const grandTotalEl = document.getElementById('jasaGrandTotal');
                        if (grandTotalEl) grandTotalEl.textContent = Math.round(grandTotal).toLocaleString('id-ID');
                    }

                    function renumberJasaSections() {
                        const cards = document.querySelectorAll('#jasaSectionsContainer .section-card');
                        cards.forEach((card, idx) => {
                            const h3 = card.querySelector('h3');
                            if (h3) h3.textContent = `Section Jasa ${idx + 1}`;
                        });
                    }

                    // Input profit jasa - hanya untuk informasi, tidak mempengaruhi perhitungan
                    document.getElementById('jasaProfitInput').addEventListener('input', function () {
                        jasaProfit = parseNumber(this.value) || 0;
                        jasaSections.forEach(s => computeJasaSectionTotals(s));
                    });

                    document.getElementById('jasaPphInput').addEventListener('input', function () {
                        jasaPph = parseNumber(this.value) || 0;
                        jasaSections.forEach(s => computeJasaSectionTotals(s));
                    });

                    // Switch untuk BPJS
                    document.getElementById('jasaUseBpjs').addEventListener('change', function () {
                        updateJasaOverallSummary();
                    });

                    function dedupeSectionData(section) {
                        const seen = new Set();
                        const filtered = [];
                        (section.data || []).forEach(r => {
                            const key =
                                `${section.nama_section || ''}||${String(r.no || '')}||${String((r.deskripsi || '').trim())}||${String(r.total || '')}`;
                            if (!seen.has(key)) {
                                seen.add(key);
                                filtered.push(r);
                            }
                        });
                        return filtered;
                    }

                    // Tombol simpan jasa
                    document.getElementById('jasaSaveAllBtn').addEventListener('click', () => {
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
                            
                            // Validasi pembulatan (harus angka)
                            if (pembulatanInput.value === '' || isNaN(parseInt(pembulatanInput.value))) {
                                validationErrors.push(`Section ${sectionNumber}: Pembulatan harus diisi dengan angka`);
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

                        const allSectionsData = jasaSections.map(section => {
                            const sectionElement = document.getElementById(section.id);
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');
                            const rawData = section.spreadsheet.getData();

                            const data = rawData.map(row => ({
                                no: row[0],
                                deskripsi: row[1],
                                vol: parseNumber(row[2]),
                                hari: parseNumber(row[3]),
                                orang: parseNumber(row[4]),
                                unit: parseNumber(row[5]),
                                total: parseNumber(row[6]),
                                id_jasa_detail: row[7] || null
                            }));

                            return {
                                nama_section: namaSectionInput.value,
                                pembulatan: parseInt(pembulatanInput.value) || 0,
                                data: dedupeSectionData({
                                    nama_section: namaSectionInput.value,
                                    data
                                })
                            };
                        });

                        console.log('💾 Saving jasa data:', {
                            penawaran_id: {{ $penawaran->id_penawaran }},
                            profit: parseNumber(document.getElementById('jasaProfitInput').value),
                            pph: parseNumber(document.getElementById('jasaPphInput').value),
                            use_bpjs: document.getElementById('jasaUseBpjs').checked,
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
                                profit: parseNumber(document.getElementById('jasaProfitInput')
                                    .value) || 0,
                                pph: parseNumber(document.getElementById('jasaPphInput').value) ||
                                    0,
                                use_bpjs: document.getElementById('jasaUseBpjs').checked ? 1 : 0,
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
                    });

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
                        let profitRaw = parseNumber(row[9]) || 0;
                        let addedCost = parseNumber(row[11]) || 0;

                        let profitDecimal = profitRaw;
                        if (profitRaw > 1) profitDecimal = profitRaw / 100;

                        let hargaSatuan = 0;
                        let total = 0;

                        if (isMitra) {
                            hargaSatuan = 0;
                            total = 0;
                        } else if (profitDecimal > 0) {
                            hargaSatuan = Math.ceil((hpp / profitDecimal) / 1000) * 1000;
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
                                const profitRaw = parseNumber(row[9]) || 0;
                                const addedCost = parseNumber(row[11]) || 0;

                                let profitDecimal = profitRaw;
                                if (profitRaw > 1) profitDecimal = profitRaw / 100;

                                let hargaSatuan = 0;
                                let total = 0;

                                if (isMitra) {
                                    hargaSatuan = 0;
                                    total = 0;
                                } else if (profitDecimal > 0) {
                                    hargaSatuan = Math.ceil((hpp / profitDecimal) / 1000) * 1000;
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

                        if (hasExistingData) {
                            document.getElementById('editModeBtn').classList.toggle('hidden', enable);
                            document.getElementById('cancelEditBtn').classList.toggle('hidden', !enable);
                        } else {
                            document.getElementById('editModeBtn').classList.add('hidden');
                            document.getElementById('cancelEditBtn').classList.add('hidden');
                        }

                        document.getElementById('saveAllBtn').classList.remove('hidden');
                        document.getElementById('addSectionBtn').classList.toggle('hidden', !enable);

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
                            row.profit || 0,
                            row.color_code || 1,
                            row.added_cost || 0,
                            row.delivery_time || ''
                        ]) : [
                            ['', '', '', 0, '', 0, 0, 0, false, 0, 1, 0, ''],
                            ['', '', '', 0, '', 0, 0, 0, false, 0, 1, 0, ''],
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
                                console.log('📝 Spreadsheet onChange:', {
                                    spreadsheetId,
                                    colIndex,
                                    rowIndex,
                                    value,
                                    columnName: ['No', 'Tipe', 'Deskripsi', 'QTY', 'Satuan',
                                        'Harga Satuan', 'Harga Total', 'HPP', 'Mitra', 'Profit (%)', 'Warna', 'Added Cost', 'Keterangan'
                                    ][colIndex]
                                });

                                if (colIndex == 3 || colIndex == 7 || colIndex == 8 || colIndex == 9 || colIndex == 11) {
                                    console.log('✨ Triggering recalculateRow with new value:', value);
                                    recalculateRow(spreadsheet, rowIndex, colIndex, value);
                                } else {
                                    console.log('⏭️ Skip calculation (column not QTY/HPP/Mitra/Profit/Added Cost)');
                                }
                            }
                        });

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
                                sections = sections.filter(s => s.id !== sectionId);
                                sectionElement.remove();
                            }
                        });

                        sections.push({
                            id: sectionId,
                            spreadsheetId,
                            spreadsheet
                        });

                        // applyTemplateStyle(spreadsheetId);
                        updateSubtotal({
                            id: sectionId,
                            spreadsheet
                        });
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
                        document.getElementById('totalKeseluruhan').textContent = totalKeseluruhan.toLocaleString('id-ID');

                        // read PPN
                        const ppnPersen = parseNumber(document.getElementById('ppnInput').value) || 0;

                        // read Best Price toggle and value
                        const useBest = document.getElementById('isBestPrice').checked;
                        const bestPriceRaw = document.getElementById('bestPriceInput').value || '0';
                        const bestPrice = parseNumber(bestPriceRaw);

                        // base amount for PPN and grand total
                        const baseAmount = useBest ? bestPrice : totalKeseluruhan;

                        const ppnNominal = (baseAmount * ppnPersen) / 100;
                        const grandTotal = baseAmount + ppnNominal;

                        // update PPN display
                        document.getElementById('ppnPersenDisplay').textContent = ppnPersen;
                        document.getElementById('ppnNominal').textContent = ppnNominal.toLocaleString('id-ID');

                        // show/hide best price display row
                        const bestRow = document.getElementById('bestPriceDisplayRow');
                        if (useBest) {
                            bestRow.style.display = 'flex';
                            document.getElementById('bestPriceDisplay').textContent = bestPrice.toLocaleString('id-ID');
                        } else {
                            bestRow.style.display = 'none';
                        }

                        // update grand total (based on baseAmount)
                        document.getElementById('grandTotal').textContent = grandTotal.toLocaleString('id-ID');

                        console.log('💰 Total Summary:', {
                            totalKeseluruhan,
                            useBest,
                            bestPrice,
                            ppnPersen,
                            ppnNominal,
                            grandTotal
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

                    // Event listener untuk perubahan PPN
                    document.getElementById('ppnInput').addEventListener('input', updateTotalKeseluruhan);
                    document
                        .getElementById('isBestPrice').addEventListener('change', updateTotalKeseluruhan);
                    document.getElementById(
                        'bestPriceInput').addEventListener('input', updateTotalKeseluruhan);

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
                    document.getElementById('isBestPrice').addEventListener('change', function () {
                        setBestPriceInputState();
                        updateTotalKeseluruhan();
                    });

                    document.getElementById('bestPriceInput').addEventListener('input', updateTotalKeseluruhan);

                    // =====================================================
                    // EVENT LISTENERS PENAWARAN
                    // =====================================================

                    document.getElementById('addSectionBtn').addEventListener('click', () => createSection());

                    document.getElementById('editModeBtn').addEventListener('click', () => {
                        toggleEditMode(true);
                    });

                    document.getElementById('cancelEditBtn').addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

                    document.getElementById('saveAllBtn').addEventListener('click', function () {
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
                        const requiredColumns = [1, 2, 3, 4, 7, 9, 10]; // Tipe, Deskripsi, QTY, Satuan, HPP, Profit, Warna
                        const columnNames = ['Tipe', 'Deskripsi', 'QTY', 'Satuan', 'HPP', 'Profit (%)', 'Warna'];
                        
                        for (let sectionIdx = 0; sectionIdx < sections.length; sectionIdx++) {
                            const section = sections[sectionIdx];
                            const rawData = section.spreadsheet.getData();
                            
                            for (let rowIdx = 0; rowIdx < rawData.length; rowIdx++) {
                                const row = rawData[rowIdx];
                                const missingColumns = [];
                                
                                // Check if row has any significant data
                                const hasSignificantData = row.some((cell, idx) => {
                                    // Check if has text content in key fields
                                    if ([0, 1, 2, 4].includes(idx)) return cell && String(cell).trim() !== ''; // No, Tipe, Deskripsi, Satuan
                                    // Check if has numeric values
                                    if ([3, 5, 6, 7, 11].includes(idx)) return parseNumber(cell) > 0; // QTY, HargaSatuan, HargaTotal, HPP, AddedCost
                                    return false;
                                });
                                
                                if (!hasSignificantData) continue; // Skip completely empty rows
                                
                                // Check required columns only for rows with data
                                requiredColumns.forEach((colIdx, posIdx) => {
                                    const cellValue = String(row[colIdx] || '').trim();
                                    // For numeric fields (QTY, HPP, Profit), check if > 0
                                    if ([3, 7, 9].includes(colIdx)) {
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
                                data: rawData.map(row => ({
                                    no: row[0],
                                    tipe: row[1],
                                    deskripsi: row[2],
                                    qty: parseNumber(row[3]),
                                    satuan: row[4],
                                    harga_satuan: parseNumber(row[5]),
                                    harga_total: parseNumber(row[6]),
                                    hpp: parseNumber(row[7]),
                                    is_mitra: row[8] ? 1 : 0,
                                    profit: parseNumber(row[9]) || 0,
                                    color_code: row[10] || 1,
                                    added_cost: parseNumber(row[11]) || 0,
                                    delivery_time: row[12] || ''
                                })).filter(row => 
                                    // Only keep rows that have actual data (not completely empty)
                                    row.no || row.tipe || row.deskripsi || row.satuan || row.delivery_time || 
                                    row.harga_satuan > 0 || row.harga_total > 0 || row.hpp > 0 || row.added_cost > 0
                                )
                            };
                        });

                        fetch("{{ route('penawaran.save') }}", {
                            credentials: 'same-origin',
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                penawaran_id: {{ $penawaran->id_penawaran }},
                                ppn_persen: parseNumber(document.getElementById('ppnInput')
                                    .value) || 11,
                                is_best_price: document.getElementById('isBestPrice').checked ? 1 :
                                    0,
                                best_price: parseNumber(document.getElementById('bestPriceInput')
                                    .value) || 0,
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
                    // INISIALISASI PENAWARAN
                    // =====================================================

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
                    
                    // Update tab states awal
                    updateTabStates();
                    
                    // Load jasa data dan tunggu sampai selesai sebelum restore tab
                    loadJasaData().then(() => {
                        // Update tab states setelah jasa data loaded
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

                // =====================================================
                    // TAB REKAP
                    // =====================================================
                    function getCsrfToken() {
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta && meta.content) return meta.content;
                        const input = document.querySelector('input[name="_token"]');
                        if (input) return input.value;
                        return '';
                    }
                    const importBtn = document.getElementById('importRekapBtn');
                    const modal = document.getElementById('importRekapModal');
                    const closeBtn = document.getElementById('closeRekapModal');
                    const loadBtn = document.getElementById('loadRekapBtn');
                    const dropdown = document.getElementById('rekapDropdown');

                    const rekapColumns = [
                        { type: "text", title: "Area", width: 160, readOnly: true },
                        { type: "text", title: "Kategori", width: 160, readOnly: true },
                        { type: "text", title: "Nama Item", width: 220, readOnly: true },
                        { type: "number", title: "Jumlah", width: 100, readOnly: true },
                        { type: "text", title: "Satuan", width: 100, readOnly: true }
                    ];

                    function escapeHtml(str) {
                        if (!str) return '';
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function computeRekapAccumulation() {
                        const body = document.getElementById('rekapAccumulationBody');
                        if (!body) return;
                        body.innerHTML = '';

                        if (!window.rekapSpreadsheet || typeof window.rekapSpreadsheet.getData !== 'function') {
                            const p = document.createElement('div');
                            p.className = 'text-gray-500';
                            p.textContent = 'Belum ada data rekap.';
                            body.appendChild(p);
                            return;
                        }

                        const rows = window.rekapSpreadsheet.getData() || [];
                        const map = {};

                        rows.forEach(r => {
                            const nama = (r[2] || '').toString().trim();     // Nama Item (kolom ke-3)
                            const jumlah = parseFloat(r[3]) || 0;           // Jumlah (kolom ke-4)
                            const satuan = (r[4] || '').toString().trim();  // Satuan (kolom ke-5)

                            if (!nama) return;
                            const key = nama + '||' + satuan;
                            if (!map[key]) map[key] = { nama, satuan, jumlah: 0 };
                            map[key].jumlah += jumlah;
                        });

                        const items = Object.values(map).sort((a, b) => a.nama.localeCompare(b.nama));

                        if (items.length === 0) {
                            const p = document.createElement('div');
                            p.className = 'text-gray-500';
                            p.textContent = 'Belum ada data rekap.';
                            body.appendChild(p);
                            return;
                        }

                        const table = document.createElement('table');
                        table.className = 'w-full text-sm border-collapse';

                        const thead = document.createElement('thead');
                        const headRow = document.createElement('tr');

                        const th1 = document.createElement('th'); th1.className = 'text-left font-semibold pb-2 bg-blue-100 px-3 py-2'; th1.textContent = 'Nama Item'; headRow.appendChild(th1);
                        const th2 = document.createElement('th'); th2.className = 'text-center font-semibold pb-2 bg-blue-100 px-3 py-2'; th2.textContent = 'Total Jumlah'; headRow.appendChild(th2);
                        const th3 = document.createElement('th'); th3.className = 'text-right font-semibold pb-2 bg-blue-100 px-3 py-2'; th3.textContent = 'Satuan'; headRow.appendChild(th3);

                        thead.appendChild(headRow);
                        table.appendChild(thead);

                        const tbody = document.createElement('tbody');
                        items.forEach(it => {
                            const tr = document.createElement('tr');

                            const tdName = document.createElement('td'); tdName.className = 'py-2 border-t px-3'; tdName.textContent = it.nama; tr.appendChild(tdName);

                            const tdJumlah = document.createElement('td'); tdJumlah.className = 'py-2 border-t text-center px-3';
                            const jumlahFormatted = Number.isInteger(it.jumlah) ? it.jumlah.toLocaleString('id-ID') : it.jumlah.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                            tdJumlah.innerHTML = `<strong>${jumlahFormatted}</strong>`;
                            tr.appendChild(tdJumlah);

                            const tdSatuan = document.createElement('td'); tdSatuan.className = 'py-2 border-t text-right px-3'; tdSatuan.textContent = it.satuan || '-'; tr.appendChild(tdSatuan);

                            tbody.appendChild(tr);
                        });

                        table.appendChild(tbody);
                        body.appendChild(table);
                    }

                    // Event listener untuk Import button
                    if (importBtn) {
                        importBtn.onclick = function() {
                            modal.classList.remove("hidden");
                            
                            fetch('{{ route("rekap.all") }}?penawaran_id={{ $penawaran->id_penawaran }}')
                                .then(res => {
                                    return res.json();
                                })
                                .then(data => {
                                    dropdown.innerHTML = "<option value=\"\">-- Pilih Rekap --</option>";
                                    data.forEach(r => {
                                        dropdown.innerHTML += `<option value="${r.id}">${r.nama} (ID: ${r.id})</option>`;
                                    });
                                })
                                .catch(err => {
                                    console.error('❌ Error fetching rekap list:', err);
                                    if (window.notyf) {
                                        notyf.error('Gagal memuat daftar rekap: ' + err.message);
                                    }
                                });
                        };
                    } else {
                        console.warn('⚠️ Import button NOT found!');
                    }

                    // Event listener untuk Close button
                    if (closeBtn) {
                        closeBtn.onclick = function() {
                            modal.classList.add("hidden");
                        };
                    } else {
                        console.warn('⚠️ Close button NOT found!');
                    }

                    // Event listener untuk Load button
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
                                const dataRows = payload.map(it => [
                                    it.nama_area ?? '',                               
                                    (it.kategori && it.kategori.nama) ? it.kategori.nama : (it.kategori ?? ''), // Kategori (jika ada)
                                    it.nama_item ?? '',                               
                                    it.jumlah ?? 0,                                   
                                    it.satuan ?? ''                                   
                                ]);
                            computeRekapAccumulation();

                                if (window.rekapSpreadsheet && typeof window.rekapSpreadsheet.setData === 'function') {
                                    window.rekapSpreadsheet.setData(dataRows);
                                } else {
                                    window.rekapSpreadsheet = jspreadsheet(document.getElementById('rekapSpreadsheet'), {
                                        data: dataRows,
                                        columns: rekapColumns,
                                        minDimensions: [4, 5],
                                        tableOverflow: true,
                                        tableHeight: "300px"
                                    });
                                }
                                computeRekapAccumulation();

                                if (window.notyf) notyf.success('Rekap berhasil dimuat.');
                                modal.classList.add("hidden");
                                // switch to Rekap tab if needed
                                document.querySelectorAll('.tab-btn').forEach(btn => {
                                    if (btn.dataset.tab === 'rekap') btn.click();
                                });
                            })
                            .catch(err => {
                                console.error('Error loading rekap import:', err);
                            });
                        };
                    } else {
                        console.warn('⚠️ Load button NOT found!');
                    }

                    // Initialize spreadsheet dengan data kosong
                    try {
                        const rekapContainer = document.getElementById("rekapSpreadsheet");
                        if (!rekapContainer) {
                            console.error('❌ Rekap spreadsheet container NOT FOUND!');
                        } else {
                            let needCreate = false;

                            if (!window.rekapSpreadsheet) {
                                needCreate = true;
                            } else if (typeof window.rekapSpreadsheet.setData !== 'function') {
                                console.warn('⚠️ Existing window.rekapSpreadsheet present but not a valid jspreadsheet instance. Will recreate.');
                                // try to destroy if possible
                                try {
                                    if (typeof window.rekapSpreadsheet.destroy === 'function') {
                                        window.rekapSpreadsheet.destroy();
                                    }
                                } catch (err) {
                                    console.warn('⚠️ Error while destroying invalid instance:', err);
                                }
                                needCreate = true;
                            } else {
                                // Looks like a valid instance — try to refresh/render
                                try {
                                    if (typeof window.rekapSpreadsheet.refresh === 'function') {
                                        window.rekapSpreadsheet.refresh();
                                    } else if (typeof window.rekapSpreadsheet.render === 'function') {
                                        window.rekapSpreadsheet.render();
                                    } else {
                                        console.log('ℹ️ Existing rekapSpreadsheet appears valid (no refresh/render method).');
                                    }
                                } catch (err) {
                                    console.warn('⚠️ Refresh failed, will recreate spreadsheet:', err);
                                    try {
                                        if (typeof window.rekapSpreadsheet.destroy === 'function') {
                                            window.rekapSpreadsheet.destroy();
                                        }
                                    } catch (e) {
                                        console.warn('⚠️ Destroy also failed:', e);
                                    }
                                    needCreate = true;
                                }
                            }

                            if (needCreate) {
                                window.rekapSpreadsheet = jspreadsheet(rekapContainer, {
                                data: [
                                    ['', '', '', 0, ''],
                                ],
                                minDimensions: [5, 1],
                                minSpareRows: 1,
                                columns: rekapColumns,
                                tableOverflow: true,
                                tableWidth: '100%'
                            });
                                console.log('✅ Empty spreadsheet initialized successfully (created).', window.rekapSpreadsheet);
                                // Update accumulation panel after creating empty spreadsheet
                                try { computeRekapAccumulation(); } catch (err) { console.warn('computeRekapAccumulation error:', err); }
                            }
                        }
                    } catch (err) {
                        console.error('❌ Error initializing spreadsheet (defensive):', err);
                        console.error('Error stack:', err && err.stack);
                    }

                    fetch(`/rekap/for-penawaran/{{ $penawaran->id_penawaran }}`)
                    .then(res => res.json())
                    .then(payload => {
                        if (Array.isArray(payload) && payload.length > 0) {
                        const dataRows = payload.map(it => [
                            it.nama_area ?? '',                               
                            (it.kategori && it.kategori.nama) ? it.kategori.nama : (it.kategori ?? ''), 
                            it.nama_item ?? '',                               
                            it.jumlah ?? 0,                                   
                            it.satuan ?? ''                                   
                        ]);

                        if (window.rekapSpreadsheet && typeof window.rekapSpreadsheet.setData === 'function') {
                            window.rekapSpreadsheet.setData(dataRows);
                        } else {
                            window.rekapSpreadsheet = jspreadsheet(document.getElementById('rekapSpreadsheet'), {
                            data: dataRows,
                            columns: rekapColumns,
                            minDimensions: [4, 5],
                            tableOverflow: true,
                            });
                        }
                        }
                        // Update accumulation panel after loading imported rows (rehydrate)
                        try { computeRekapAccumulation(); } catch (err) { console.warn('computeRekapAccumulation error:', err); }
                    })
                    .catch(e => console.error('Failed to load imported rekap for this penawaran:', e));
                    console.log('🏁 REKAP TAB INIT - Completed (defensive)');
                    

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

                    // Function to check validation
                    function checkPreviewValidation() {
                        const ringkasanFilled = ringkasanInput && ringkasanInput.value.trim().length > 0;
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

                        return ringkasanFilled && notesFilled;
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
                        if (!checkPreviewValidation()) {
                            notyf.error('⚠️ Silakan isi Ringkasan Jasa dan Notes terlebih dahulu!');
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
                        if (!checkPreviewValidation()) {
                            notyf.error('⚠️ Silakan isi Ringkasan Jasa dan Notes terlebih dahulu!');
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
                });
            </script>
        @endpush