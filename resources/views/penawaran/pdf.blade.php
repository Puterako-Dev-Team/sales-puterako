<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Penawaran - {{ $penawaran->no_penawaran }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .pdf-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            text-align: center;
            z-index: 10;
            width: 100%;
        }

        .pdf-header img {
            width: 100%;
            max-width: 100%;
            max-height: 100px;
            object-fit: cover;
            display: block;
            margin: 0 auto;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            margin-top: 120px;
            margin-bottom: 50px;
        }

        .container {
            max-width: 100%;
            padding: 25px;
        }

        .header {
            margin-bottom: 15px;
            text-align: center;
            padding: 0;
        }

        .header img {
            width: 100%;
            max-height: 120px;
            object-fit: contain;
            display: block;
            margin-top: 0;
            margin-bottom: 0;
            padding: -15px;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 12px;
        }

        .info-section p {
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .info-section .greeting {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        /* Section */
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 6px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 10px;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 5px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table thead th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: center;
        }

        /* Column Widths */
        table th:nth-child(1),
        table td:nth-child(1) {
            width: 4%;
            text-align: center;
        }

        /* No */
        table th:nth-child(2),
        table td:nth-child(2) {
            width: 12%;
        }

        /* Tipe */
        table th:nth-child(3),
        table td:nth-child(3) {
            width: 28%;
        }

        /* Deskripsi (LEBAR) */
        table th:nth-child(4),
        table td:nth-child(4) {
            width: 6%;
            text-align: center;
        }

        /* Qty (SEMPIT) */
        table th:nth-child(5),
        table td:nth-child(5) {
            width: 8%;
            text-align: center;
        }

        /* Satuan */
        table th:nth-child(6),
        table td:nth-child(6) {
            width: 14%;
            text-align: right;
        }

        /* Harga Satuan */
        table th:nth-child(7),
        table td:nth-child(7) {
            width: 14%;
            text-align: right;
        }

        /* Harga Total */

        table tbody tr.area-header td {
            background-color: #67BC4B;
            font-weight: bold;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 12px;
        }

        table tfoot tr td {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        table tfoot tr td:last-child {
            text-align: right;
        }

        .pre-wrap {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Summary */
        .summary {
            margin-top: 15px;
            width: 100%;
        }

        .summary-inner {
            float: right;
            width: 280px;
        }

        .summary-table {
            width: 100%;
            border: none;
            font-size: 11px;
        }

        .summary-table td {
            border: none;
            padding: 4px 0;
        }

        .summary-table td:first-child {
            font-weight: bold;
            text-align: left;
        }

        .summary-table td:last-child {
            text-align: right;
        }

        .summary-table tr.grand-total {
            border-top: 2px solid #333;
        }

        .summary-table tr.grand-total td {
            padding-top: 6px;
            font-size: 12px;
        }

        /* Notes */
        .notes {
            clear: both;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }

        .notes h4 {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .notes pre {
            font-family: Arial, sans-serif; /* Sama dengan body font */
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            font-size: 11px;
            line-height: 1.4;
        }

        /* Style untuk ringkasan jasa di table */
        table td pre {
            font-family: Arial, sans-serif; /* Sama dengan body font */
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            font-size: 10px; /* Sesuai dengan font size table */
            line-height: 1.4;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
        }

        .footer p {
            margin-bottom: 3px;
        }

        .signature {
            margin-top: 50px;
        }

        .signature-line {
            display: inline-block;
            width: 180px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }

        .signature-name {
            font-size: 10px;
            margin-top: 3px;
        }

        /* Clear floats */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Page Break */
        @page {
            margin-top: 120px;
            margin-bottom: 1cm;
            margin-left: 0;
            margin-right: 0;
        }

        @media print {
            body {
                padding: 0;
            }

            .section {
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
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
        }
    </style>
</head>

<body>
    <div class="pdf-header">
        <img src="{{ public_path('assets/banner.png') }}" alt="Kop Perusahaan" style="max-height:100px;">
    </div>
    <div class="container">
        <!-- Info Penawaran -->
        <div class="info-section">
            <p><strong>Surabaya,
                    {{ \Carbon\Carbon::parse($penawaran->created_at ?? now())->locale('id')->translatedFormat('d F Y') }}</strong>
            </p>
            <p style="margin-top: 20px;">Kepada Yth:</p>
            <p><strong>{{ $penawaran->nama_perusahaan }}</strong></p>
            <p>{{ $penawaran->lokasi }}</p>
            @if ($penawaran->pic_perusahaan)
                <p><strong>Up. {{ $penawaran->pic_perusahaan }}</strong></p>
            @endif

            <p style="margin-top: 20px;"><strong>Perihal:</strong> {{ $penawaran->perihal }}</p>
            <p><strong>No:</strong> {{ $penawaran->no_penawaran }}@if ($activeVersion > 1)
                    -Rev{{ $activeVersion }}
                @endif
            </p>
            <p class="greeting" style="margin-top: 20px;"><strong>Dengan Hormat,</strong></p>
            <p>Bersama ini kami PT. Puterako Inti Buana memberitahukan Penawaran Harga {{ $penawaran->perihal }} dengan
                perincian sebagai berikut:</p>
        </div>

        @php
            // Ambil data dari versionRow
            $ppnPersen = $versionRow->ppn_persen ?? 11;
            $isBest = $versionRow->is_best_price ?? false;
            $bestPrice = $versionRow->best_price ?? 0;
            $totalPenawaran = $details->sum('harga_total');
            $grandTotalJasa = $jasa->grand_total ?? 0;
            $baseAmount = $isBest && $bestPrice > 0 ? $bestPrice : $totalPenawaran + $grandTotalJasa;
            $ppnNominal = ($baseAmount * $ppnPersen) / 100;
            $grandTotal = $baseAmount + $ppnNominal;
        @endphp

        <!-- Sections -->
        @php
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
            $sectionNumber = 1;
        @endphp

        @foreach ($groupedSections as $namaSection => $areas)
            <h3 style="font-weight: bold; font-size: 12px; margin-bottom: 6px; margin-top: 10px;">
                {{ convertToRoman($sectionNumber) }}. {{ $namaSection }}
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tipe</th>
                        <th>Deskripsi</th>
                        <th>Qty</th>
                        <th>Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Harga Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $subtotal = 0; @endphp
                    @foreach ($areas as $area => $rows)
                        @if ($area && $area !== '' && $area !== '-' && trim($area) !== '')
                            <tr class="area-header">
                                <td colspan="7">{{ $area }}</td>
                            </tr>
                        @endif
                        @foreach ($rows as $row)
                            @php
                                $subtotal += $row->harga_total;
                            @endphp
                            <tr>
                                <td>{{ $row->no }}</td>
                                <td>{{ $row->tipe }}</td>
                                <td>{{ $row->deskripsi }}</td>
                                <td>{{ $row->qty }}</td>
                                <td>{{ $row->satuan }}</td>
                                <td>
                                    @if (!empty($row->is_mitra))
                                        <span style="color:#3498db;font-weight:bold; font-style: italic;">by
                                            User</span>
                                    @else
                                        {{ $row->harga_satuan > 0 ? number_format($row->harga_satuan, 0, ',', '.') : '' }}
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($row->is_mitra))
                                        <span style="color:#3498db;font-weight:bold; font-style: italic;">by
                                            Mitra</span>
                                    @else
                                        {{ $row->harga_total > 0 ? number_format($row->harga_total, 0, ',', '.') : '' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">Subtotal</td>
                        <td>{{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
            @php $sectionNumber++; @endphp
        @endforeach

        <!-- Tabel Quotation Jasa -->
        <h3 style="font-weight: bold; font-size: 12px; margin-bottom: 6px; margin-top: 10px;">
            {{ convertToRoman($sectionNumber) }}. Biaya Quotation Jasa
        </h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tipe</th>
                    <th>Deskripsi</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Harga Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Jasa</td>
                    <td>
                        <pre>{{ $versionRow->jasa_ringkasan ?? '' }}</pre>
                    </td>
                    <td>1</td>
                    <td>Lot</td>
                    <td>Rp {{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6">Subtotal</td>
                    <td>{{ number_format($versionRow->jasa_grand_total ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Summary -->
        <div class="summary clearfix">
            <div class="summary-inner">
                <table class="summary-table">
                    <tr>
                        <td>Total</td>
                        <td>Rp {{ number_format($totalPenawaran + $grandTotalJasa, 0, ',', '.') }}</td>
                    </tr>
                    @if ($isBest)
                        <tr>
                            <td>Best Price</td>
                            <td>Rp {{ number_format($bestPrice, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>PPN {{ number_format($ppnPersen, 0, ',', '.') }}%</td>
                        <td>Rp {{ number_format($ppnNominal, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>Grand Total</td>
                        <td><span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <div class="notes">
            <h4>NOTE:</h4>
            @if (!empty($versionRow->notes))
                <pre>{{ $versionRow->notes }}</pre>
            @else
                <p class="text-gray-500 text-sm">Belum ada catatan untuk versi ini.</p>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Demikian penawaran ini kami sampaikan</p>
            <p style="margin-top: 8px;"><strong>Hormat kami,</strong></p>
            <div class="signature">
                <p class="signature-line"></p>
                <p class="signature-name">Junly Kodradjaya</p>
            </div>
        </div>
    </div>
</body>

</html>
