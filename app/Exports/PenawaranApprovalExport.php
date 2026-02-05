<?php

namespace App\Exports;

use App\Models\ExportApprovalRequest;
use App\Models\PenawaranDetail;
use App\Models\JasaDetail;
use App\Models\PenawaranVersion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\Response;

class PenawaranApprovalExport
{
    protected $approvalRequest;
    protected $penawaran;
    protected $version;
    protected $details;
    protected $jasaDetails;

    public function __construct(ExportApprovalRequest $approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
        $this->penawaran = $approvalRequest->penawaran;
        $this->version = $approvalRequest->version;
        
        // Get penawaran details by version_id - preserve insertion order by id_penawaran_detail
        $this->details = PenawaranDetail::where('version_id', $this->version->id)
            ->orderBy('id_penawaran_detail')
            ->get();
        
        // Get jasa details by version_id (if not 'barang' type)
        if ($this->penawaran->tipe !== 'barang') {
            $this->jasaDetails = JasaDetail::where('version_id', $this->version->id)
                ->orderBy('id_jasa_detail')
                ->get();
        } else {
            $this->jasaDetails = collect();
        }
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        
        // Sheet 1: Penawaran Details
        $this->createPenawaranSheet($spreadsheet);
        
        // Sheet 2: Jasa Details (if applicable)
        if ($this->penawaran->tipe !== 'barang' && $this->jasaDetails->count() > 0) {
            $this->createJasaSheet($spreadsheet);
        }
        
        // Sheet 3: Summary
        $this->createSummarySheet($spreadsheet);
        
        // Set active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        return $spreadsheet;
    }

    /**
     * Convert number to Roman numeral
     */
    protected function toRoman($num)
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1,
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

    /**
     * Round to nearest thousand (pembulatan)
     */
    protected function roundToNearest($value, $nearest = 1000)
    {
        return round($value / $nearest) * $nearest;
    }

    protected function createPenawaranSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rincian Penawaran');
        
        // Group details by section and area while preserving insertion order
        $groupedData = [];
        $sectionOrder = [];
        $areaOrder = [];
        
        foreach ($this->details as $detail) {
            $section = $detail->nama_section ?? '';
            $area = $detail->area ?? '';
            
            // Track section order
            if (!isset($groupedData[$section])) {
                $groupedData[$section] = [];
                $sectionOrder[] = $section;
            }
            
            // Track area order within section
            if (!isset($groupedData[$section][$area])) {
                $groupedData[$section][$area] = [];
                if (!isset($areaOrder[$section])) {
                    $areaOrder[$section] = [];
                }
                $areaOrder[$section][] = $area;
            }
            
            $groupedData[$section][$area][] = $detail;
        }
        
        // Header info
        $sheet->setCellValue('A1', 'RINCIAN PENAWARAN');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Penawaran Info
        $row = 3;
        $sheet->setCellValue('A' . $row, 'No Penawaran');
        $sheet->setCellValue('B' . $row, ': ' . $this->penawaran->no_penawaran . ($this->version->version > 0 ? '-Rev' . $this->version->version : ''));
        $row++;
        $sheet->setCellValue('A' . $row, 'Perusahaan');
        $sheet->setCellValue('B' . $row, ': ' . $this->penawaran->nama_perusahaan);
        $row++;
        $sheet->setCellValue('A' . $row, 'Lokasi');
        $sheet->setCellValue('B' . $row, ': ' . $this->penawaran->lokasi);
        $row++;
        $sheet->setCellValue('A' . $row, 'Perihal');
        $sheet->setCellValue('B' . $row, ': ' . $this->penawaran->perihal);
        $row++;
        $sheet->setCellValue('A' . $row, 'Tipe Penawaran');
        $sheet->setCellValue('B' . $row, ': ' . ucfirst($this->penawaran->tipe ?? 'default'));
        
        $row += 2;
        
        // Header style definition (to reuse for each section)
        $headers = ['No', 'Tipe', 'Deskripsi', 'QTY', 'Satuan', 'Harga Satuan', 'Harga Total', 'Delivery Time'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '800000'], 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F5F5DC']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        
        // Data rows with grouped sections and areas (preserving order)
        $sectionNumber = 1;
        $isFirstSection = true;
        
        foreach ($sectionOrder as $sectionName) {
            $areas = $groupedData[$sectionName];
            
            // Add 2 empty rows gap before each section (except first)
            if (!$isFirstSection) {
                $row += 2;
            }
            $isFirstSection = false;
            
            // Section header row (Roman numeral + Section name)
            $sheet->setCellValue('A' . $row, $this->toRoman($sectionNumber) . '. ' . $sectionName);
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('800000');
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            // Repeat table header for this section
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($columns[$index] . $row, $header);
            }
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($headerStyle);
            $row++;
            
            // Track section subtotal
            $sectionSubtotal = 0;
            
            $isFirstArea = true;
            foreach ($areaOrder[$sectionName] as $areaName) {
                $items = $areas[$areaName];
                
                // Add 2 empty rows gap before each area (except first in section)
                if (!$isFirstArea) {
                    $row += 2;
                }
                $isFirstArea = false;
                
                // Area sub-header row (bold, centered)
                if (!empty($areaName) && $areaName !== '-' && trim($areaName) !== '') {
                    $sheet->setCellValue('A' . $row, $areaName);
                    $sheet->mergeCells('A' . $row . ':H' . $row);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('800000');
                    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;
                }
                
                // Item rows
                foreach ($items as $detail) {
                    $sheet->setCellValue('A' . $row, $detail->no);
                    $sheet->setCellValue('B' . $row, $detail->tipe_name ?? $detail->tipe);
                    $sheet->setCellValue('C' . $row, $detail->deskripsi);
                    $sheet->setCellValue('D' . $row, $detail->qty);
                    $sheet->setCellValue('E' . $row, $detail->satuan);
                    
                    // Handle is_mitra (by User)
                    if ($detail->is_mitra) {
                        $sheet->setCellValue('F' . $row, 'by User');
                        $sheet->setCellValue('G' . $row, 'by User');
                        $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setSize(12)->setItalic(true)->setBold(true)->getColor()->setRGB('3498DB');
                    } elseif ($detail->is_judul) {
                        $sheet->setCellValue('F' . $row, '');
                        $sheet->setCellValue('G' . $row, '');
                    } else {
                        $sheet->setCellValue('F' . $row, $detail->harga_satuan);
                        $sheet->setCellValue('G' . $row, $detail->harga_total);
                        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
                        
                        // Add to section subtotal
                        $sectionSubtotal += $detail->harga_total;
                    }
                    
                    $sheet->setCellValue('H' . $row, $detail->delivery_time ?? '-');
                    
                    // Set font size 12 for data rows
                    $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setSize(12);
                    
                    // Color coding
                    $colorCode = $detail->color_code ?? 1;
                    $fontColor = '000000'; // Default black
                    if ($colorCode == 2) $fontColor = '8E44AD'; // Purple
                    if ($colorCode == 3) $fontColor = '2980B9'; // Blue
                    
                    // Only apply color if not already styled (is_mitra)
                    if (!$detail->is_mitra) {
                        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->getColor()->setRGB($fontColor);
                    }
                    
                    // Border
                    $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    
                    $row++;
                }
            }
            
            // Section Subtotal row
            $sheet->setCellValue('A' . $row, 'Subtotal');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $sectionSubtotal);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            $sectionNumber++;
        }
        
        // Add gap before grand subtotal
        $row += 1;
        
        // Grand Subtotal row
        $totalPenawaran = $this->details->where('is_mitra', false)->where('is_judul', false)->sum('harga_total');
        $sheet->setCellValue('A' . $row, 'TOTAL PENAWARAN');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('G' . $row, $totalPenawaran);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E5E5');
        $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Set column widths (adjusted for new structure)
        $sheet->getColumnDimension('A')->setWidth(8);   // No
        $sheet->getColumnDimension('B')->setWidth(22);  // Tipe
        $sheet->getColumnDimension('C')->setWidth(50);  // Deskripsi
        $sheet->getColumnDimension('D')->setWidth(8);   // QTY
        $sheet->getColumnDimension('E')->setWidth(10);  // Satuan
        $sheet->getColumnDimension('F')->setWidth(18);  // Harga Satuan
        $sheet->getColumnDimension('G')->setWidth(18);  // Harga Total
        $sheet->getColumnDimension('H')->setWidth(15);  // Delivery Time
    }

    protected function createJasaSheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Rincian Jasa');
        
        // Group jasa details by section while preserving order
        $groupedJasa = [];
        $jasaSectionOrder = [];
        
        foreach ($this->jasaDetails as $detail) {
            $section = $detail->nama_section ?? '';
            
            if (!isset($groupedJasa[$section])) {
                $groupedJasa[$section] = [];
                $jasaSectionOrder[] = $section;
            }
            
            $groupedJasa[$section][] = $detail;
        }
        
        // Header
        $sheet->setCellValue('A1', 'RINCIAN BIAYA JASA');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Header style definition (to reuse for each section)
        $row = 3;
        $headers = ['No', 'Deskripsi', 'Vol', 'Hari', 'Orang', 'Unit', 'Total'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'] // Blue-500
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        
        // Data rows grouped by section
        $sectionNumber = 1;
        $isFirstSection = true;
        $grandTotalJasa = 0; // Track grand total from pembulatan
        
        foreach ($jasaSectionOrder as $sectionName) {
            $items = $groupedJasa[$sectionName];
            
            // Add 2 empty rows gap before each section (except first)
            if (!$isFirstSection) {
                $row += 2;
            }
            $isFirstSection = false;
            
            // Section header row
            if (!empty($sectionName)) {
                $sheet->setCellValue('A' . $row, $this->toRoman($sectionNumber) . '. ' . $sectionName);
                $sheet->mergeCells('A' . $row . ':G' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E40AF');
                $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
                $sectionNumber++;
            }
            
            // Repeat table header for this section
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($columns[$index] . $row, $header);
            }
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
            $row++;
            
            // Track section subtotal
            $sectionSubtotal = 0;
            $sectionPembulatan = 0;
            
            // Get profit and pph percent from version settings
            $profitPercent = $this->version->jasa_profit_percent ?? 0;
            $pphPercent = $this->version->jasa_pph_percent ?? 0;
            
            // Item rows
            foreach ($items as $detail) {
                $sheet->setCellValue('A' . $row, $detail->no);
                $sheet->setCellValue('B' . $row, $detail->deskripsi);
                $sheet->setCellValue('C' . $row, $detail->vol);
                $sheet->setCellValue('D' . $row, $detail->hari);
                $sheet->setCellValue('E' . $row, $detail->orang);
                $sheet->setCellValue('F' . $row, $detail->unit);
                $sheet->setCellValue('G' . $row, $detail->total);
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
                
                // Add to section subtotal
                $sectionSubtotal += $detail->total ?? 0;
                
                // Get pembulatan from database (use last item's value as it should be same for section)
                $sectionPembulatan = $detail->pembulatan ?? 0;
                
                // Set font size 12 for data rows
                $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setSize(12);
                
                // Border
                $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                $row++;
            }
            
            // Calculate profit and pph using formula:
            // afterProfit = subtotal / (1 - profit% / 100)
            // afterPph = afterProfit / (1 - pph% / 100)
            $afterProfit = $profitPercent > 0 ? ($sectionSubtotal / (1 - ($profitPercent / 100))) : $sectionSubtotal;
            $afterPph = $pphPercent > 0 ? ($afterProfit / (1 - ($pphPercent / 100))) : $afterProfit;
            
            // Calculate display values
            $profitDisplay = round($afterProfit);
            $pphDisplay = round($afterPph);
            
            // Add pembulatan to grand total (pembulatan is from database input, not calculated)
            $grandTotalJasa += $sectionPembulatan;
            
            // Section Subtotal row
            $sheet->setCellValue('A' . $row, 'Subtotal');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $sectionSubtotal);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
            $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            // Profit row (dynamic %) - black font, not bold
            $sheet->setCellValue('A' . $row, 'Profit ' . $profitPercent . '%');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $profitDisplay);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setSize(12);
            $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            // PPH row (dynamic %) - black font, not bold
            $sheet->setCellValue('A' . $row, 'PPH ' . $pphPercent . '%');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $pphDisplay);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setSize(12);
            $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            
            // Pembulatan row (from database input, not calculated) - black font, bold
            $sheet->setCellValue('A' . $row, 'Pembulatan');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->setCellValue('G' . $row, $sectionPembulatan);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setSize(12)->setBold(true);
            $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }
        
        // Add gap before grand total
        $row += 1;
        
        // Grand Total row (sum of all pembulatan)
        $sheet->setCellValue('A' . $row, 'TOTAL JASA');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->setCellValue('G' . $row, $grandTotalJasa);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E5E5');
        $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Jasa Ringkasan (if exists)
        if (!empty($this->version->jasa_ringkasan)) {
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Ringkasan Jasa:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;
            $sheet->setCellValue('A' . $row, $this->version->jasa_ringkasan);
            $sheet->mergeCells('A' . $row . ':G' . ($row + 3));
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle('A' . $row)->getFont()->setSize(12);
        }
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(45);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);
    }

    protected function createSummarySheet(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');
        
        // Header
        $sheet->setCellValue('A1', 'SUMMARY PENAWARAN');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $row = 3;
        
        // Penawaran Info
        $sheet->setCellValue('A' . $row, 'No Penawaran');
        $sheet->setCellValue('B' . $row, $this->penawaran->no_penawaran . ($this->version->version > 0 ? '-Rev' . $this->version->version : ''));
        $row++;
        $sheet->setCellValue('A' . $row, 'Perusahaan');
        $sheet->setCellValue('B' . $row, $this->penawaran->nama_perusahaan);
        $row++;
        $sheet->setCellValue('A' . $row, 'Lokasi');
        $sheet->setCellValue('B' . $row, $this->penawaran->lokasi);
        $row++;
        $sheet->setCellValue('A' . $row, 'Perihal');
        $sheet->setCellValue('B' . $row, $this->penawaran->perihal);
        $row++;
        $sheet->setCellValue('A' . $row, 'Tipe');
        $sheet->setCellValue('B' . $row, ucfirst($this->penawaran->tipe ?? 'default'));
        
        // Style info section
        $sheet->getStyle('A3:A' . $row)->getFont()->setBold(true);
        
        $row += 2;
        
        // Calculations
        $totalPenawaran = $this->details->where('is_mitra', false)->where('is_judul', false)->sum('harga_total');
        $grandTotalJasa = ($this->penawaran->tipe === 'barang') ? 0 : ($this->version->jasa_grand_total ?? 0);
        $ppnPersen = $this->version->ppn_persen ?? 11;
        $isBest = $this->version->is_best_price ?? false;
        $bestPrice = $this->version->best_price ?? 0;
        
        $baseAmount = $isBest && $bestPrice > 0 ? $bestPrice : ($totalPenawaran + $grandTotalJasa);
        $ppnNominal = ($baseAmount * $ppnPersen) / 100;
        $grandTotal = $baseAmount + $ppnNominal;
        
        // Summary Table Header
        $sheet->setCellValue('A' . $row, 'Komponen');
        $sheet->setCellValue('B' . $row, 'Nominal (Rp)');
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '22C55E']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Penawaran');
        $sheet->setCellValue('B' . $row, $totalPenawaran);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        if ($this->penawaran->tipe !== 'barang') {
            $row++;
            $sheet->setCellValue('A' . $row, 'Total Jasa');
            $sheet->setCellValue('B' . $row, $grandTotalJasa);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalPenawaran + $grandTotalJasa);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        if ($isBest && $bestPrice > 0) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Best Price');
            $sheet->setCellValue('B' . $row, $bestPrice);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setItalic(true);
            $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        
        $row++;
        $sheet->setCellValue('A' . $row, 'PPN ' . $ppnPersen . '%');
        $sheet->setCellValue('B' . $row, $ppnNominal);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'GRAND TOTAL');
        $sheet->setCellValue('B' . $row, $grandTotal);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
        $sheet->getStyle('A' . $row . ':B' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        
        // Notes
        if (!empty($this->version->notes)) {
            $row += 2;
            $sheet->setCellValue('A' . $row, 'NOTES:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, $this->version->notes);
            $sheet->mergeCells('A' . $row . ':B' . ($row + 4));
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
    }

    public function download()
    {
        $spreadsheet = $this->export();
        $writer = new Xlsx($spreadsheet);
        
        // Generate filename
        $safeNoPenawaran = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $this->penawaran->no_penawaran);
        $filename = 'Penawaran-' . $safeNoPenawaran;
        if ($this->version->version > 0) {
            $filename .= '-Rev' . $this->version->version;
        }
        $filename .= '.xlsx';
        
        // Create temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
