<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JasaDetail;

class JasaDetailController extends Controller
{
    public function show(Request $request)
    {
        $penawaranId = $request->query('id');
        $version = $request->query('version', 1);

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)->first();

        if (!$versionRow) {
            return response()->json([
                'sections' => [],
                'profit' => 0,
                'pph' => 0
            ]);
        }

        $details = JasaDetail::where('version_id', $versionRow->id)->get();
        $profit = $versionRow->jasa_profit_percent ?? 0;
        $pph = $versionRow->jasa_pph_percent ?? 0;

        $sections = $details->groupBy('nama_section')->map(function ($items, $key) {
            return [
                'nama_section' => $key,
                'pembulatan' => $items->first()->pembulatan ?? 0,
                'data' => $items->map(function ($d) {
                    return [
                        'id_jasa_detail' => $d->id_jasa_detail,
                        'no' => $d->no,
                        'deskripsi' => $d->deskripsi,
                        'vol' => $d->vol,
                        'hari' => $d->hari,
                        'orang' => $d->orang,
                        'unit' => $d->unit,
                        'total' => $d->total,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return response()->json([
            'sections' => $sections,
            'profit' => $profit,
            'pph' => $pph
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->all();
        $penawaranId = $data['penawaran_id'] ?? null;
        $version = $data['version'] ?? 1;
        $sections = $data['sections'] ?? [];
        $profit = $data['profit'] ?? 0;
        $pph = $data['pph'] ?? 0;

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)->first();

        if (!$versionRow) {
            return response()->json(['error' => 'Version not found'], 400);
        }
        $version_id = $versionRow->id;

        $processedIds = [];

        foreach ($sections as $section) {
            $namaSection = $section['nama_section'] ?? null;
            $pembulatan = $section['pembulatan'] ?? 0;
            foreach ($section['data'] as $row) {
                // Skip hanya jika semua field penting kosong
                if (empty($row['deskripsi']) && empty($row['no']) && empty($row['vol']) && empty($row['hari']) && empty($row['orang']) && empty($row['unit'])) continue;

                $idJasaDetail = $row['id_jasa_detail'] ?? null;
                $values = [
                    'id_penawaran' => $penawaranId,
                    'version_id' => $version_id,
                    'nama_section' => $namaSection,
                    'pembulatan' => $pembulatan,
                    'no' => $row['no'] ?? null,
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'vol' => $row['vol'] ?? null,
                    'hari' => $row['hari'] ?? null,
                    'orang' => $row['orang'] ?? null,
                    'unit' => $row['unit'] ?? null,
                    'total' => $row['total'] ?? null,
                    'profit' => $profit,
                    'pph' => $pph,
                ];

                if ($idJasaDetail) {
                    $detail = JasaDetail::find($idJasaDetail);
                    if ($detail) {
                        $detail->update($values);
                        $processedIds[] = $idJasaDetail;
                    } else {
                        $detail = JasaDetail::create($values);
                        $processedIds[] = $detail->getKey();
                    }
                } else {
                    $detail = JasaDetail::create($values);
                    $processedIds[] = $detail->getKey();
                }
            }
        }

        // Hapus JasaDetail yang tidak ada di payload untuk versi ini
        JasaDetail::where('version_id', $version_id)
            ->where('id_penawaran', $penawaranId)
            ->whereNotIn('id_jasa_detail', $processedIds)
            ->delete();

        return response()->json(['success' => true]);
    }
}
