<?php

namespace App\Exports;

use App\Models\Rekap;
use App\Models\RekapSurvey;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RekapSurveyExport
{
    protected $rekap;
    protected $surveys;
    protected $versionId;
    protected $versionNum;

    public function __construct(Rekap $rekap, $versionId = null, $versionNum = null)
    {
        $this->rekap = $rekap;
        $this->versionId = $versionId;
        $this->versionNum = $versionNum;
        
        // Filter surveys by version if provided
        if ($versionId) {
            $this->surveys = $rekap->surveys()->where('version_id', $versionId)->get();
        } else {
            // Support both single and multiple surveys (legacy - no version)
            $this->surveys = $rekap->surveys()->whereNull('version_id')->get();
            if ($this->surveys->isEmpty() && $rekap->survey) {
                $this->surveys = collect([$rekap->survey]);
            }
        }
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Survey Data');

        $currentRow = 1;

        // If no surveys, export empty template
        if ($this->surveys->isEmpty()) {
            $sheet->setCellValue('A1', 'DATA SURVEY - ' . strtoupper($this->rekap->nama));
            $sheet->setCellValue('A2', 'Belum ada data survey.');
            return $spreadsheet;
        }

        // Export each area
        foreach ($this->surveys as $areaIndex => $survey) {
            $currentRow = $this->exportArea($sheet, $survey, $currentRow, $areaIndex + 1);
            $currentRow += 2; // Add spacing between areas
        }

        return $spreadsheet;
    }

    /**
     * Export a single area to the sheet
     */
    protected function exportArea($sheet, $survey, $startRow, $areaNumber)
    {
        $headers = $survey->headers ?? RekapSurvey::getDefaultHeaders();
        $data = $survey->data ?? [];
        $areaName = $survey->area_name ?? "Area {$areaNumber}";

        // Get total columns count
        $totalColumns = 1; // Starting from column B (A is for row number)
        foreach ($headers as $group) {
            $totalColumns += count($group['columns'] ?? []);
        }
        
        $lastCol = $this->getColumnLetter($totalColumns);
        $currentRow = $startRow;

        // Area title row
        $versionSuffix = $this->versionNum !== null ? " (Rev {$this->versionNum})" : '';
        $titleText = strtoupper($areaName) . $versionSuffix ?: "AREA {$areaNumber}";
        $sheet->setCellValue("A{$currentRow}", $titleText);
        $sheet->mergeCells("A{$currentRow}:{$lastCol}{$currentRow}");
        
        $sheet->getStyle("A{$currentRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '02ADB8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow++;

        // Info row (only on first area)
        if ($areaNumber === 1) {
            $sheet->setCellValue("A{$currentRow}", 'Penawaran: ' . ($this->rekap->penawaran->nama_penawaran ?? '-'));
            $sheet->setCellValue("C{$currentRow}", 'Customer: ' . ($this->rekap->penawaran->customer ?? '-'));
            $sheet->setCellValue("E{$currentRow}", 'Status: ' . ucfirst($this->rekap->status));
            $currentRow++;
        }

        // Group Headers row
        $colIndex = 1;
        $sheet->setCellValue($this->getColumnLetter($colIndex) . $currentRow, 'No');
        $colIndex++;

        foreach ($headers as $group) {
            $startCol = $colIndex;
            $groupColCount = count($group['columns'] ?? []);
            $endCol = $colIndex + $groupColCount - 1;
            
            $sheet->setCellValue($this->getColumnLetter($startCol) . $currentRow, $group['group']);
            
            if ($groupColCount > 1) {
                $sheet->mergeCells($this->getColumnLetter($startCol) . $currentRow . ':' . $this->getColumnLetter($endCol) . $currentRow);
            }
            
            $bgColor = $this->getGroupColor($group['color'] ?? 'default');
            $sheet->getStyle($this->getColumnLetter($startCol) . $currentRow . ':' . $this->getColumnLetter($endCol) . $currentRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
            
            $colIndex = $endCol + 1;
        }
        $groupHeaderRow = $currentRow;
        $currentRow++;

        // Column Headers row
        $colIndex = 1;
        $sheet->setCellValue($this->getColumnLetter($colIndex) . $currentRow, 'No');
        $colIndex++;

        foreach ($headers as $group) {
            foreach ($group['columns'] ?? [] as $col) {
                $sheet->setCellValue($this->getColumnLetter($colIndex) . $currentRow, $col['title']);
                $colIndex++;
            }
        }

        $sheet->getStyle('A' . $currentRow . ':' . $this->getColumnLetter($totalColumns) . $currentRow)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        $columnHeaderRow = $currentRow;
        $currentRow++;

        // Data rows
        $dataStartRow = $currentRow;
        $rowCount = 1;
        foreach ($data as $row) {
            $colIndex = 1;
            $sheet->setCellValue($this->getColumnLetter($colIndex) . $currentRow, $rowCount);
            $colIndex++;

            foreach ($headers as $group) {
                foreach ($group['columns'] ?? [] as $col) {
                    $value = $row[$col['key']] ?? '';
                    $cellRef = $this->getColumnLetter($colIndex) . $currentRow;
                    
                    if ($col['type'] === 'numeric' && is_numeric($value)) {
                        $sheet->setCellValue($cellRef, (float)$value);
                        $sheet->getStyle($cellRef)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                    } else {
                        $sheet->setCellValue($cellRef, $value);
                    }
                    
                    $colIndex++;
                }
            }
            
            $currentRow++;
            $rowCount++;
        }

        // Style data area
        if ($currentRow > $dataStartRow) {
            $sheet->getStyle('A' . $dataStartRow . ':' . $this->getColumnLetter($totalColumns) . ($currentRow - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        // Totals row - each cell aligned with column above
        $totalsRow = $currentRow;
        
        // Groups that should NOT have totals
        $excludedGroups = ['lokasi', 'dimensi'];
        
        // First cell with label
        $sheet->setCellValue('A' . $totalsRow, 'TOTAL KEBUTUHAN');
        
        // Each column matches header column
        $colIndex = 2;
        foreach ($headers as $group) {
            $groupName = strtolower($group['group'] ?? '');
            $isExcludedGroup = in_array($groupName, $excludedGroups);
            
            foreach ($group['columns'] ?? [] as $col) {
                if ($col['type'] === 'numeric' && !$isExcludedGroup) {
                    $sum = 0;
                    foreach ($data as $row) {
                        $sum += (float)($row[$col['key']] ?? 0);
                    }
                    $sheet->setCellValue($this->getColumnLetter($colIndex) . $totalsRow, $sum);
                }
                // Non-numeric columns and excluded groups left empty
                $colIndex++;
            }
        }

        $sheet->getStyle('A' . $totalsRow . ':' . $this->getColumnLetter($totalColumns) . $totalsRow)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEF3C7'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        $currentRow++;

        // Auto-size columns
        for ($i = 1; $i <= $totalColumns; $i++) {
            $sheet->getColumnDimension($this->getColumnLetter($i))->setAutoSize(true);
        }

        return $currentRow;
    }

    /**
     * Get column letter from index (1 = A, 2 = B, etc.)
     */
    protected function getColumnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }

    /**
     * Get background color for group
     */
    protected function getGroupColor($colorName)
    {
        $colors = [
            'lokasi' => '3B82F6',   // Blue
            'dimensi' => '8B5CF6',  // Purple
            'kabel' => 'F59E0B',    // Amber
            'pipa' => '22C55E',     // Green
            'box' => '06B6D4',      // Cyan
            'default' => '6B7280',  // Gray
        ];

        return $colors[$colorName] ?? $colors['default'];
    }
}