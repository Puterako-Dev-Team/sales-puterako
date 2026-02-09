<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapSurvey extends Model
{
    protected $fillable = [
        'rekap_id',
        'area_name',
        'headers',
        'data',
        'totals',
        'version',
    ];

    protected $casts = [
        'headers' => 'array',
        'data' => 'array',
        'totals' => 'array',
    ];

    /**
     * Get the rekap that owns the survey data.
     */
    public function rekap(): BelongsTo
    {
        return $this->belongsTo(Rekap::class);
    }

    /**
     * Get default headers structure for new survey.
     */
    public static function getDefaultHeaders(): array
    {
        return [
            [
                'group' => 'Lokasi',
                'columns' => [
                    ['key' => 'lantai', 'title' => 'Lantai', 'type' => 'text', 'width' => 60],
                    ['key' => 'nama', 'title' => 'Nama', 'type' => 'text', 'width' => 80],
                    ['key' => 'dari', 'title' => 'Dari', 'type' => 'text', 'width' => 80],
                    ['key' => 'ke', 'title' => 'Ke', 'type' => 'text', 'width' => 80],
                ]
            ],
            [
                'group' => 'Dimensi',
                'columns' => [
                    ['key' => 'horizon', 'title' => 'Horizon', 'type' => 'numeric', 'width' => 80],
                    ['key' => 'vertical', 'title' => 'Vertical', 'type' => 'numeric', 'width' => 80],
                    ['key' => 'up_08', 'title' => 'UP 0.8', 'type' => 'numeric', 'width' => 80],
                ]
            ],
            [
                'group' => 'Kabel',
                'columns' => [
                    ['key' => 'utp', 'title' => 'UTP', 'type' => 'numeric', 'width' => 80],
                    ['key' => 'awg_22', 'title' => 'AWG 22', 'type' => 'numeric', 'width' => 80],
                    ['key' => 'nyy_3x15', 'title' => 'NYY 3 X 1,5', 'type' => 'numeric', 'width' => 80],
                    ['key' => 'fo', 'title' => 'FO', 'type' => 'numeric', 'width' => 80],
                ]
            ],
        ];
    }

    /**
     * Calculate totals for numeric columns.
     */
    public function calculateTotals(): array
    {
        $totals = [];
        $data = $this->data ?? [];
        $headers = $this->headers ?? [];

        // Get all column keys that are numeric
        $numericKeys = [];
        foreach ($headers as $group) {
            foreach ($group['columns'] ?? [] as $col) {
                if (($col['type'] ?? 'text') === 'numeric') {
                    $numericKeys[] = $col['key'];
                }
            }
        }

        // Calculate sum for each numeric column
        foreach ($numericKeys as $key) {
            $sum = 0;
            foreach ($data as $row) {
                $sum += floatval($row[$key] ?? 0);
            }
            $totals[$key] = $sum;
        }

        return $totals;
    }
}
