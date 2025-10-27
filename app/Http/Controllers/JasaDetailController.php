<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JasaDetail;

class JasaDetailController extends Controller
{
    public function show(Request $request)
    {
        $id = $request->query('id');
        $penawaran = \App\Models\Penawaran::find($id);

        $details = $penawaran ? $penawaran->jasaDetails()->get() : collect();
        $profit = $details->first()->profit ?? 0;
        $pph = $details->first()->pph ?? 0;

        $sections = $details->groupBy(function ($item) {
            return $item->nama_section;
        })->map(function ($items, $key) {
            return [
                'nama_section' => $key,
                'pembulatan' => $items->first()->pembulatan ?? 0,
                'data' => $items->map(function ($d) {
                    return [
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
        $sections = $data['sections'] ?? [];
        $profit = $data['profit'] ?? 0;
        $pph = $data['pph'] ?? 0;

        if (!$penawaranId) {
            return response()->json(['error' => 'Penawaran ID tidak ditemukan'], 400);
        }

        $existingDetails = JasaDetail::where('id_penawaran', $penawaranId)->get()->keyBy(function ($item) {
            return $item->no . '|' . $item->nama_section;
        });

        $newKeys = [];

        foreach ($sections as $section) {
            $namaSection = $section['nama_section'] ?? null;
            foreach ($section['data'] as $row) {
                $key = ($row['no'] ?? '') . '|' . $namaSection;
                $newKeys[] = $key;

                $values = [
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'vol' => $row['vol'] ?? null,
                    'hari' => $row['hari'] ?? null,
                    'orang' => $row['orang'] ?? null,
                    'unit' => $row['unit'] ?? null,
                    'total' => $row['total'] ?? null,
                    'profit' => $profit,
                    'pph' => $pph,
                    'nama_section' => $namaSection,
                ];

                if (isset($existingDetails[$key])) {
                    $existingDetails[$key]->update($values);
                } else {
                    JasaDetail::create(array_merge($values, [
                        'id_penawaran' => $penawaranId,
                        'no' => $row['no'] ?? null,
                    ]));
                }
            }
        }

        JasaDetail::where('id_penawaran', $penawaranId)
            ->whereNotIn(DB::raw("CONCAT(no, '|', nama_section)"), $newKeys)
            ->delete();

        return response()->json(['success' => true]);
    }
}
