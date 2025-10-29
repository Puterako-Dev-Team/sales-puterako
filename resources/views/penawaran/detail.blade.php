@extends('layouts.app')

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
            pointer-events: none;
            opacity: 0.7;
        }

        .spreadsheet-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
        }
    </style>

    <div class="flex items-center p-8 text-gray-600 -mb-8">
        <a href="{{ route('penawaran.list') }}" class="flex items-center hover:text-blue-600">
            <x-lucide-arrow-left class="w-5 h-5 mr-2" />
            List Penawaran
        </a>
        <span class="mx-2">/</span>
        <span class="font-semibold">Detail Penawaran</span>
    </div>
    <?php
    $versions = \App\Models\PenawaranVersion::where('penawaran_id', $penawaran->id_penawaran)->orderBy('version')->get();
    $activeVersion = request('version') ?? ($versions->max('version') ?? 1); ?>

    <div class="mb-4 flex items-center gap-2">
        <label class="font-semibold">Lihat Versi:</label>
        <form method="GET" action="{{ route('penawaran.show') }}">
            <input type="hidden" name="id" value="{{ $penawaran->id_penawaran }}">
            <select name="version" onchange="this.form.submit()" class="border rounded px-3 py-2">
                @foreach ($versions as $v)
                    <option value="{{ $v->version }}" {{ $v->version == $activeVersion ? 'selected' : '' }}>
                        Rev {{ $v->version }} {{ $v->notes ? '- ' . $v->notes : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    <form method="POST" action="{{ route('penawaran.createRevision', ['id' => $penawaran->id_penawaran]) }}">
        @csrf
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold ml-2">
            + Buat Revisi Baru
        </button>
    </form>

    <div class="container mx-auto p-8">
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Detail Penawaran</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="font-semibold">No Penawaran</div>
                    <div>{{ $penawaran->no_penawaran }}</div>
                </div>
                <div>
                    <div class="font-semibold">Perihal</div>
                    <div>{{ $penawaran->perihal }}</div>
                </div>
                <div>
                    <div class="font-semibold">Nama Perusahaan</div>
                    <div>{{ $penawaran->nama_perusahaan }}</div>
                </div>
                <div>
                    <div class="font-semibold">PIC Perusahaan</div>
                    <div>{{ $penawaran->pic_perusahaan }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex border-b mb-4">
                <button
                    class="tab-btn px-4 py-2 font-semibold text-green-600 border-b-2 border-green-600 focus:outline-none"
                    data-tab="penawaran">Penawaran & Jasa</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="Jasa">Rincian Jasa</button>
                <button class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:text-green-600 focus:outline-none"
                    data-tab="preview">Preview</button>
            </div>

            <div id="tabContent">
                <!-- Panel Penawaran -->
                <div class="tab-panel" data-tab="penawaran">
                    <!-- Template Selection (Global) -->
                    <div class="p-2 rounded-lg mb-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-4">

                                <div class="flex items-center">
                                    <label class="block text-sm font-semibold mr-2">Profit (%)</label>
                                    <input type="number" id="profitInput" class="border rounded px-3 py-2 bg-white w-24"
                                        min="0" step="0.1" placeholder="30" value="{{ $profit ?? '' }}">
                                    <span class="ml-1 text-sm text-gray-600">%</span>
                                </div>
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
                                class="bg-[#02ADB8] text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm font-semibold shadow-md">
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
                                            class="border rounded px-3 py-2 bg-white w-24 text-right" min="0"
                                            step="0.01" value="{{ $ppn_persen ?? 11 }}">
                                        <span class="text-sm text-gray-600">%</span>
                                    </div>
                                </div>

                                {{-- <!-- Best Price toggle + input -->
                                <div class="flex items-center gap-4 mt-3">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" id="isBestPrice"
                                            {{ $penawaran->is_best_price ?? false ? 'checked' : '' }} />
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
                                <div class="flex justify-between items-center text-lg font-semibold">
                                    <span>Total:</span>
                                    <span>Rp <span id="totalKeseluruhan">0</span></span>
                                </div>

                                <!-- Best Price display (hidden by default; JS toggles) -->
                                <div id="bestPriceDisplayRow"
                                    class="flex justify-between items-center text-lg font-semibold" style="display:none;">
                                    <span>Best Price:</span>
                                    <span>Rp <span id="bestPriceDisplay">0</span></span>
                                </div>

                                <!-- PPN Nominal -->
                                <div class="flex justify-between items-center text-lg font-semibold">
                                    <span>PPN (<span id="ppnPersenDisplay">11</span>%):</span>
                                    <span>Rp <span id="ppnNominal">0</span></span>
                                </div>

                                <!-- Grand Total -->
                                <div
                                    class="flex justify-between items-center text-xl font-bold pt-3 border-t-2 border-gray-400">
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
                            <input type="number" id="jasaProfitInput" class="border rounded px-3 py-2 bg-white w-24"
                                min="0" step="0.1" value="{{ $versionRow->jasa_profit_percent ?? 0 }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">PPH (%)</label>
                            <input type="number" id="jasaPphInput" class="border rounded px-3 py-2 bg-white w-24"
                                min="0" step="0.1" value="{{ $versionRow->jasa_pph_percent ?? 0 }}">
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

                            <div class="flex justify-between ">
                                <div class="text-gray-700 font-bold">Total Jasa Awal</div>
                                <div class="font-bold text-green-600">Rp <span id="jasaOverallGrand">0</span></div>
                            </div>
                            <!-- Tambahkan di bawah sini -->
                            <div class="flex justify-between mt-2">
                                <div class="text-gray-700 font-semibold">BPJS Konstruksi
                                    {{ number_format($versionRow->jasa_bpjsk_percent ?? 0, 2, ',', '.') }}
                                    <div class="font-semibold text-blue-700">Rp
                                        {{ number_format($versionRow->jasa_bpjsk_value ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2">
                                    <div class="text-gray-700 font-semibold">Total Jasa Setelah BPJS</div>
                                    <div class="font-bold text-green-600">Rp <span>
                                            {{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</div>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const input = document.getElementById('ringkasanJasa');
                            const previewSpan = document.getElementById('ringkasanJasaPreview');
                            if (input && previewSpan) {
                                input.addEventListener('input', function() {
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
                    </style>

                    <!-- Action Buttons -->
                    <div class="mb-4 text-right no-print">
                        <a href="{{ route('penawaran.exportPdf', ['id' => $penawaran->id_penawaran]) }}" target="_blank"
                            class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition font-semibold shadow-md">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                </path>
                            </svg>
                            Export PDF
                        </a>
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
                                {{ \Carbon\Carbon::parse($penawaran->created_at ?? now())->locale('id')->translatedFormat('F Y') }}
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
                            <p class="mb-4"><span class="font-semibold">No:</span> {{ $penawaran->no_penawaran }}
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
                            @php $sectionNumber++; @endphp
                            <div class="mb-8 break-inside-avoid">
                                <h3 class="font-bold text-lg mb-3">
                                    {{ convertToRoman($sectionNumber) }}.
                                    {{ $namaSection ?: 'Section ' . $sectionNumber }}
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse border border-gray-300 text-sm">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-12">No</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center">Tipe</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center">Deskripsi</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-16">Qty</th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-20">Satuan
                                                </th>
                                                <th class="border border-gray-300 px-3 py-2 text-center w-32">Harga
                                                    Satuan
                                                </th>
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
                                                        <td colspan="7"
                                                            style="background:#67BC4B;font-weight:bold; color: white; text-align: center; padding: 8px;">
                                                            {{ $area }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @foreach ($areaRows as $section)
                                                    @foreach ($section['data'] as $row)
                                                        @php $subtotal += $row['harga_total']; @endphp
                                                        <tr>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ $row['no'] }}</td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['tipe'] }}</td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['deskripsi'] }}</td>
                                                            <td class="border border-gray-300 px-3 py-2 text-center">
                                                                {{ number_format($row['qty'], 0) }}</td>
                                                            <td class="border border-gray-300 px-3 py-2">
                                                                {{ $row['satuan'] }}</td>
                                                            <td class="border border-gray-300 px-3 py-2 text-right">
                                                                Rp
                                                                {{ number_format($row['harga_satuan'], 0, ',', '.') }}
                                                            </td>
                                                            <td class="border border-gray-300 px-3 py-2 text-right">
                                                                Rp
                                                                {{ number_format($row['harga_total'], 0, ',', '.') }}
                                                            </td>
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
                                            {{ $versionRow->jasa_ringkasan ?? '' }}</td>
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
                                            {{ number_format($grandTotal, 0, ',', '.') }} </td>
                                    </tr>
                                    <!-- Best Price toggle + input -->
                                    <div class="flex items-center gap-4 mt-3">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" id="isBestPrice"
                                                {{ $isBest ?? false ? 'checked' : '' }} />
                                            <span class="text-sm font-medium">Best Price</span>
                                        </label>

                                        <form method="POST"
                                            action="{{ route('penawaran.saveBestPrice', ['id' => $penawaran->id_penawaran, 'version' => $activeVersion]) }}"
                                            class="flex items-center ml-4">
                                            @csrf
                                            <span class="ml-2 text-sm text-gray-600">Rp</span>
                                            <input type="text" name="best_price" id="bestPriceInput"
                                                class="border rounded px-3 py-2 bg-white w-40 text-right" placeholder="0"
                                                value="{{ $isBest ? $bestPrice : 0 }}">
                                            <button type="submit"
                                                class="ml-4 bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition font-semibold shadow-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
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
                                        <td class="py-2 font-semibold">PPN
                                            {{ number_format($ppnPersen, 0, ',', '.') }}%
                                        </td>
                                        <td class="py-2 text-right">Rp {{ number_format($ppnNominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr class="border-t-2 border-gray-400">
                                        <td class="py-2 font-bold text-lg">Grand Total</td>
                                        <td class="py-2 text-right font-bold text-lg">
                                            Rp {{ number_format($grandTotalWithPpn, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Tabel Jasa di Preview -->
                        <form method="POST"
                            action="{{ route('jasa.saveRingkasan', ['id_penawaran' => $penawaran->id_penawaran]) }}">
                            @csrf
                            <label for="ringkasan" class="font-bold mb-2 block">Ringkasan Jasa:</label>
                            <textarea class="border rounded w-full p-3 text-sm mb-2" name="ringkasan" id="ringkasan"
                                placeholder="Masukkan Ringkasan Jasa">{{ old('ringkasan', $versionRow->jasa_ringkasan ?? '') }}</textarea>
                            <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition font-semibold shadow-md">
                                Simpan Ringkasan Jasa
                            </button>
                        </form>

                        <!-- Notes -->
                        <div class="mt-8 mb-6">
                            <form id="notesForm" method="POST"
                                action="{{ route('penawaran.saveNotes', ['id' => $penawaran->id_penawaran]) }}">
                                @csrf
                                <label for="note" class="font-bold mb-2 block">Catatan Penawaran (Notes):</label>
                                <textarea name="note" id="note" rows="7" class="border rounded w-full p-3 text-sm mb-2"
                                    placeholder="Masukkan catatan penawaran...">{{ old('note', $penawaran->note) }}</textarea>
                                <button type="submit"
                                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition font-semibold shadow-md">
                                    Simpan Notes
                                </button>
                            </form>
                        </div>
                        <div class="mt-8 border-t pt-6">
                            <h4 class="font-bold mb-3">NOTE:</h4>
                            @if (!empty($penawaran->note))
                                <ol class="list-decimal list-inside space-y-1 text-sm">
                                    @foreach (explode("\n", $penawaran->note) as $note)
                                        @if (trim($note) !== '')
                                            <li>{{ $note }}</li>
                                        @endif
                                    @endforeach
                                </ol>
                            @else
                                <p class="text-gray-500 text-sm">Belum ada catatan penawaran.</p>
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
            </div>
        @endsection

        <script>
            const activeVersion = {{ $activeVersion }};
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
                document.addEventListener("DOMContentLoaded", function() {
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
                    let jasaIsEditMode = true;
                    let jasaHasExistingData = false;

                    // =====================================================
                    // TAB SWITCHING LOGIC
                    // =====================================================

                    const tabButtons = document.querySelectorAll('.tab-btn');
                    const tabPanels = document.querySelectorAll('.tab-panel');

                    tabButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const targetTab = this.getAttribute('data-tab');

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

                            // Load jasa data jika tab Jasa diklik (hanya sekali)
                            if (targetTab === 'Jasa' && jasaSections.length === 0) {
                                console.log('🔄 Loading Jasa tab for first time...');
                                loadJasaData();
                            }
                        });
                    });

                    // =====================================================
                    // UTILITY FUNCTIONS
                    // =====================================================

                    function parseNumber(value) {
                        if (typeof value === "string") {
                            value = value.trim();
                            if (value.indexOf('.') !== -1 && value.indexOf(',') !== -1) {
                                value = value.replace(/\./g, '').replace(/,/g, '.');
                            } else if (value.indexOf(',') !== -1) {
                                value = value.replace(/,/g, '.');
                            } else {
                                value = value.replace(/,/g, '');
                            }
                        }
                        const result = parseFloat(value) || 0;
                        return result;
                    }

                    // =====================================================
                    // FUNGSI JASA
                    // =====================================================

                    // Paste kode ini ke dalam tag <script> di bagian FUNGSI JASA

                    function loadJasaData() {
                        const penawaranId = {{ $penawaran->id_penawaran }};

                        fetch(`/jasa/detail?id=${penawaranId}&version=${activeVersion}`)
                            .then(res => {
                                if (!res.ok) throw new Error('Network response was not ok');
                                return res.json();
                            })
                            .then(data => {
                                console.log('🟢 Response dari /jasa/detail:', data);
                                jasaInitialSections = data.sections || [];
                                jasaProfit = data.profit || 0;
                                jasaPph = data.pph || 0;
                                jasaHasExistingData = jasaInitialSections.length > 0;

                                document.getElementById('jasaProfitInput').value = jasaProfit;
                                document.getElementById('jasaPphInput').value = jasaPph;

                                if (jasaHasExistingData) {
                                    // Tambahkan ID detail ke setiap row
                                    jasaInitialSections.forEach(section => {
                                        if (section.data && Array.isArray(section.data)) {
                                            section.data = section.data.map(row => ({
                                                ...row,
                                                id_jasa_detail: row.id_jasa_detail || null
                                            }));
                                        }
                                        createJasaSection(section, false);
                                    });
                                    jasaIsEditMode = false;
                                    toggleJasaEditMode(false);
                                    document.getElementById('jasaEditModeBtn').classList.remove('hidden');
                                    document.getElementById('jasaCancelEditBtn').classList.add('hidden');

                                    console.log('🔒 Mode: VIEW (jasa data exists)');
                                } else {
                                    createJasaSection(null, true);
                                    jasaIsEditMode = true;
                                    toggleJasaEditMode(true);
                                    document.getElementById('jasaEditModeBtn').classList.add('hidden');
                                    document.getElementById('jasaCancelEditBtn').classList.remove('hidden');

                                    console.log('✏️ Mode: EDIT (new jasa data)');
                                }
                            })
                            .catch(error => {
                                if (jasaSections.length === 0) {
                                    createJasaSection(null, true);
                                    jasaIsEditMode = true;
                                    toggleJasaEditMode(true);
                                    document.getElementById('jasaEditModeBtn').classList.add('hidden');
                                    document.getElementById('jasaCancelEditBtn').classList.remove('hidden');
                                    document.getElementById('jasaSaveAllBtn').classList.remove('hidden');
                                    console.log('✏️ Mode: EDIT (first create jasa data)');
                                }
                            });
                    }

                    document.getElementById('jasaAddSectionBtn').addEventListener('click', () => {
                        createJasaSection(null, jasaIsEditMode);
                    });

                    document.getElementById('jasaEditModeBtn').addEventListener('click', () => {
                        toggleJasaEditMode(true);
                        jasaIsEditMode = true;
                        document.getElementById('jasaEditModeBtn').classList.add('hidden');
                        document.getElementById('jasaCancelEditBtn').classList.remove('hidden');
                        document.getElementById('jasaSaveAllBtn').classList.remove('hidden');
                    });

                    document.getElementById('jasaCancelEditBtn').addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

                    function toggleJasaEditMode(enable) {
                        jasaIsEditMode = enable;

                        document.getElementById('jasaProfitInput').disabled = !enable;
                        document.getElementById('jasaPphInput').disabled = !enable;

                        // Tampilkan tombol tambah section jasa hanya saat edit
                        document.getElementById('jasaAddSectionBtn').classList.toggle('hidden', !enable);

                        jasaSections.forEach(section => {
                            const sectionElement = document.getElementById(section.id);
                            const spreadsheetWrapper = document.getElementById(section.spreadsheetId);
                            const namaSectionInput = sectionElement.querySelector('.nama-section-input');
                            const addRowBtn = sectionElement.querySelector('.add-row-btn');
                            const deleteSectionBtn = sectionElement.querySelector('.delete-section-btn');
                            const pembulatanInput = sectionElement.querySelector('.pembulatan-input');
                            pembulatanInput.addEventListener('input', updateJasaOverallSummary);

                            if (enable) {
                                spreadsheetWrapper.classList.remove('spreadsheet-disabled');
                                section.spreadsheet.options.editable = true;
                            } else {
                                spreadsheetWrapper.classList.add('spreadsheet-disabled');
                                section.spreadsheet.options.editable = false;
                            }

                            namaSectionInput.disabled = !enable;
                            addRowBtn.classList.toggle('hidden', !enable);
                            deleteSectionBtn.classList.toggle('hidden', !enable);
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
                <input type="number" class="pembulatan-input border rounded px-3 py-1 w-24" 
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
                <div class="text-sm">Profit: Rp <span class="${sectionId}-profit-val">0</span></div>
                <div class="text-sm">PPH: Rp <span class="${sectionId}-pph-val">0</span></div>
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
                                    type: 'numeric'
                                },
                                {
                                    title: 'Total',
                                    width: 120,
                                    type: 'numeric',
                                    readOnly: true
                                },
                            ],
                            tableOverflow: true,
                            tableWidth: '100%',
                            tableHeight: '350px',
                            editable: editable,
                            onchange: function(instance, cell, col, row, value) {
                                if (col >= 2 && col <= 5) {
                                    setTimeout(() => recalcJasaRow(spreadsheet, row), 50);
                                }
                            },
                            onafterchanges: function(instance, records) {
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
                        const profitSpan = document.querySelector(`#${section.id} .${section.id}-profit-val`);
                        const pphSpan = document.querySelector(`#${section.id} .${section.id}-pph-val`);
                        const grandSpan = document.querySelector(`#${section.id} .${section.id}-grand-val`);

                        if (profitSpan) profitSpan.textContent = profitDisplay.toLocaleString('id-ID');
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
                    }

                    function renumberJasaSections() {
                        const cards = document.querySelectorAll('#jasaSectionsContainer .section-card');
                        cards.forEach((card, idx) => {
                            const h3 = card.querySelector('h3');
                            if (h3) h3.textContent = `Section Jasa ${idx + 1}`;
                        });
                    }

                    // Input profit jasa - hanya untuk informasi, tidak mempengaruhi perhitungan
                    document.getElementById('jasaProfitInput').addEventListener('input', function() {
                        jasaProfit = parseNumber(this.value) || 0;
                        jasaSections.forEach(s => computeJasaSectionTotals(s));
                    });

                    document.getElementById('jasaPphInput').addEventListener('input', function() {
                        jasaPph = parseNumber(this.value) || 0;
                        jasaSections.forEach(s => computeJasaSectionTotals(s));
                    });

                    function dedupeSectionData(section) {
                        const seen = new Set();
                        const filtered = [];
                        (section.data || []).forEach(r => {
                            const key =
                                `${section.nama_section||''}||${String(r.no||'')}||${String((r.deskripsi||'').trim())}||${String(r.total||'')}`;
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
                                    sections: allSectionsData,
                                    version: {{ $activeVersion ? $activeVersion : 1 }}
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                console.log('✅ Jasa data saved successfully:', data);
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

                        const profitRaw = parseNumber(document.getElementById('profitInput').value) || 0;
                        let profitDecimal = profitRaw;
                        if (profitRaw > 1) profitDecimal = profitRaw / 100;

                        const row = spreadsheet.getRowData(rowIndex);
                        let hpp = parseNumber(row[7]);
                        let qty = parseNumber(row[3]);
                        let isMitra = row[8] ? true : false;
                        let addedCost = parseNumber(row[9]) || 0;

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
                        const profitRaw = parseNumber(document.getElementById('profitInput').value) || 0;
                        let profitDecimal = profitRaw;
                        if (profitRaw > 1) profitDecimal = profitRaw / 100;

                        sections.forEach((section, sectionIdx) => {
                            const allData = section.spreadsheet.getData();
                            allData.forEach((row, i) => {
                                const hpp = parseNumber(row[7]);
                                const qty = parseNumber(row[3]);
                                const isMitra = row[8] ? true : false;
                                const addedCost = parseNumber(row[9]) || 0;

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
                        document.getElementById('profitInput').disabled = !enable;

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
                            row.added_cost || 0
                        ]) : [
                            ['', '', '', 0, '', 0, 0, 0, false, 0],
                            ['', '', '', 0, '', 0, 0, 0, false, 0],
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
                        <div class="text-right mt-3 font-semibold text-gray-700">
                            Subtotal: Rp <span id="${sectionId}-subtotal">0</span>
                        </div>
                    </div>`;

                        document.getElementById('sectionsContainer').insertAdjacentHTML('beforeend', sectionHTML);

                        const spreadsheet = jspreadsheet(document.getElementById(spreadsheetId), {
                            data: initialData,
                            columns: [{
                                    title: 'No',
                                    width: 60
                                },
                                {
                                    title: 'Tipe',
                                    width: 150,
                                    wordWrap: true
                                },
                                {
                                    title: 'Deskripsi',
                                    width: 300,
                                    wordWrap: true
                                },
                                {
                                    title: 'QTY',
                                    width: 100,
                                    type: 'numeric'
                                },
                                {
                                    title: 'Satuan',
                                    width: 100
                                },
                                {
                                    title: 'Harga Satuan',
                                    width: 150,
                                    type: 'numeric',
                                    readOnly: true,

                                },
                                {
                                    title: 'Harga Total',
                                    width: 150,
                                    type: 'numeric',
                                    readOnly: true,

                                },
                                {
                                    title: 'HPP',
                                    width: 100,
                                    type: 'numeric',

                                },
                                {
                                    title: 'Mitra',
                                    width: 80,
                                    type: 'checkbox'
                                },
                                {
                                    title: 'Added Cost',
                                    width: 120,
                                    type: 'numeric',

                                }

                            ],
                            tableOverflow: true,
                            tableWidth: '100%',
                            tableHeight: '400px',
                            editable: isEditMode,
                            onchange: function(instance, cell, colIndex, rowIndex, value) {
                                console.log('📝 Spreadsheet onChange:', {
                                    spreadsheetId,
                                    colIndex,
                                    rowIndex,
                                    value,
                                    columnName: ['No', 'Tipe', 'Deskripsi', 'QTY', 'Satuan',
                                        'Harga Satuan', 'Harga Total', 'HPP', 'Mitra', 'Added Cost'
                                    ][colIndex]
                                });

                                if (colIndex == 3 || colIndex == 7 || colIndex == 8 || colIndex == 9) {
                                    console.log('✨ Triggering recalculateRow with new value:', value);
                                    recalculateRow(spreadsheet, rowIndex, colIndex, value);
                                } else {
                                    console.log('⏭️ Skip calculation (column not QTY/HPP/Mitra/Added Cost)');
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
                                            `Hapus ${validRows.length} baris: ${validRows.sort((a,b) => a-b).join(', ')}?`
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
                    document.getElementById('isBestPrice').addEventListener('change', function() {
                        setBestPriceInputState();
                        updateTotalKeseluruhan();
                    });

                    document.getElementById('bestPriceInput').addEventListener('input', updateTotalKeseluruhan);

                    // =====================================================
                    // EVENT LISTENERS PENAWARAN
                    // =====================================================

                    document.getElementById('profitInput').addEventListener('input', function() {
                        console.log('💰 Profit input changed to:', this.value);
                        recalculateAll();
                    });

                    document.getElementById('addSectionBtn').addEventListener('click', () => createSection());

                    document.getElementById('editModeBtn').addEventListener('click', () => {
                        toggleEditMode(true);
                    });

                    document.getElementById('cancelEditBtn').addEventListener('click', () => {
                        if (confirm('Batalkan perubahan dan kembali ke mode view?')) {
                            window.location.reload();
                        }
                    });

                    document.getElementById('saveAllBtn').addEventListener('click', function() {
                        const btn = this;
                        btn.innerHTML = "⏳ Menyimpan...";
                        btn.disabled = true;

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
                                    added_cost: parseNumber(row[9]) || 0
                                }))
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
                                    profit: parseNumber(document.getElementById('profitInput').value) ||
                                        0,
                                    ppn_persen: parseNumber(document.getElementById('ppnInput')
                                        .value) || 11,
                                    is_best_price: document.getElementById('isBestPrice').checked ? 1 :
                                        0,
                                    best_price: parseNumber(document.getElementById('bestPriceInput')
                                        .value) || 0,
                                    sections: allSectionsData,
                                    version: {{ $activeVersion ? $activeVersion : 1 }}
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
                                btn.innerHTML = "✅ Tersimpan!";
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            })
                            .catch(error => {
                                console.error('❌ Save failed:', error);
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
                        const profitInput = document.getElementById('profitInput');
                        if (profitInput && profitInput.value) {
                            console.log('🚀 Initial calculation with profit:', profitInput.value);
                            recalculateAll();
                        } else {
                            console.log('⚠️ No profit value found');
                        }
                    } else {
                        console.log('🆕 Creating new empty section...');
                        createSection();
                        toggleEditMode(true);
                        console.log('✏️ Mode: EDIT (new data)');
                    }
                });
            </script>
        @endpush
