<?php

namespace App\Exports;

use App\Models\Rekap;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Log;

class RekapDetailExport
{
    protected $rekap;

    public function __construct(Rekap $rekap)
    {
        $this->rekap = $rekap->load('items.kategori', 'items.tipe', 'items.satuan', 'user', 'penawaran');
    }

    /**
     * Export to Excel file using maatwebsite/excel v1.x
     */
    public function export()
    {
        $rekap = $this->rekap;
        $filename = 'Rekap_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $rekap->nama) . '_' . now()->format('Ymd_His');

        return \Excel::create($filename, function($excel) use ($rekap) {
            $excel->sheet('Rekap Survey', function($sheet) use ($rekap) {
                // Title row
                $sheet->row(1, ['DETAIL REKAP SURVEY']);
                $sheet->mergeCells('A1:D1');
                $sheet->cells('A1:D1', function($cells) {
                    $cells->setFontSize(14);
                    $cells->setFontWeight('bold');
                    $cells->setAlignment('center');
                    $cells->setBackground('#02ADB8');
                    $cells->setFontColor('#ffffff');
                });

                // Info row
                $sheet->row(2, ['Nama Rekap: ' . $rekap->nama, '', 'Status: ' . ($rekap->status === 'approved' ? 'Approved' : 'Pending')]);
                $sheet->mergeCells('A2:B2');
                
                // Header row
                $sheet->row(3, ['Kategori', 'Nama Item', 'Jumlah', 'Satuan']);
                $sheet->cells('A3:D3', function($cells) {
                    $cells->setFontWeight('bold');
                    $cells->setBackground('#02ADB8');
                    $cells->setFontColor('#ffffff');
                    $cells->setAlignment('center');
                });

                // Group items by area
                $groupedByArea = $rekap->items->sortBy('id')->groupBy('nama_area');
                $areaOrder = [];
                foreach ($rekap->items->sortBy('id') as $item) {
                    if (!in_array($item->nama_area, $areaOrder)) {
                        $areaOrder[] = $item->nama_area;
                    }
                }

                $rowNum = 4;
                
                // Data rows for each area
                foreach ($areaOrder as $area) {
                    if (!isset($groupedByArea[$area])) continue;
                    
                    $areaItems = $groupedByArea[$area];
                    
                    // Area header
                    $sheet->row($rowNum, ['AREA: ' . $area]);
                    $sheet->mergeCells("A{$rowNum}:D{$rowNum}");
                    $sheet->cells("A{$rowNum}:D{$rowNum}", function($cells) {
                        $cells->setFontWeight('bold');
                        $cells->setBackground('#BAE9E9');
                        $cells->setFontColor('#155E3A');
                    });
                    $rowNum++;
                    
                    // Items grouped by kategori
                    $groupedByKategori = $areaItems->groupBy('rekap_kategori_id');
                    
                    foreach ($groupedByKategori as $kategoriId => $kategoriItems) {
                        foreach ($kategoriItems as $index => $item) {
                            $sheet->row($rowNum, [
                                $index === 0 ? ($item->kategori->nama ?? '-') : '',
                                $item->tipe->nama ?? '-',
                                $item->jumlah,
                                $item->satuan->nama ?? '-'
                            ]);
                            $rowNum++;
                        }
                    }
                }

                // Empty row before accumulation
                $rowNum++;

                // Accumulation section
                $accumulation = [];
                foreach ($rekap->items as $item) {
                    $key = ($item->tipe->nama ?? '-') . '|' . ($item->satuan->nama ?? '-');
                    if (!isset($accumulation[$key])) {
                        $accumulation[$key] = [
                            'nama_item' => $item->tipe->nama ?? '-',
                            'satuan' => $item->satuan->nama ?? '-',
                            'jumlah' => 0
                        ];
                    }
                    $accumulation[$key]['jumlah'] += $item->jumlah;
                }

                if (count($accumulation) > 0) {
                    // Accumulation header
                    $sheet->row($rowNum, ['AKUMULASI (SUBTOTAL SEMUA AREA)']);
                    $sheet->mergeCells("A{$rowNum}:D{$rowNum}");
                    $sheet->cells("A{$rowNum}:D{$rowNum}", function($cells) {
                        $cells->setFontWeight('bold');
                        $cells->setBackground('#02ADB8');
                        $cells->setFontColor('#ffffff');
                    });
                    $rowNum++;

                    // Accumulation items
                    foreach ($accumulation as $acc) {
                        $sheet->row($rowNum, [
                            $acc['nama_item'],
                            '',
                            $acc['jumlah'],
                            $acc['satuan']
                        ]);
                        $rowNum++;
                    }
                }

                // Set column widths
                $sheet->setWidth([
                    'A' => 25,
                    'B' => 35,
                    'C' => 15,
                    'D' => 15
                ]);

                // Apply borders to all data cells
                $lastRow = $rowNum - 1;
                $sheet->setBorder("A3:D{$lastRow}", 'thin');
            });
        })->download('xlsx');
    }
}
