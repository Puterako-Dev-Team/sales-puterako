<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenawaranController extends Controller
{
    public function index()
    {
        $penawarans = \App\Models\Penawaran::all();
        return view('penawaran.list', compact('penawarans'));
    }

    public function store(Request $request)
    {
        \App\Models\Penawaran::create($request->all());
        return redirect()->route('penawaran.list');
    }

    public function followUp()
    {
        // Halaman Follow Up
        return view('penawaran.followUp');
    }

    public function rekapSurvey()
    {
        // Halaman Rekap Survey
        return view('penawaran.rekapSurvey');
    }
    public function show(Request $request)
    {
        $id = $request->query('id');
        $penawaran = \App\Models\Penawaran::find($id);

        $details = $penawaran ? $penawaran->details()->get() : collect();

        $profit = $details->first()->profit ?? 0;

        $jasaDetails = \App\Models\JasaDetail::where('id_penawaran', $penawaran->id_penawaran)->get();
        $jasa = \App\Models\Jasa::where('id_penawaran', $penawaran->id_penawaran)->first();

        $sections = $details->groupBy(function ($item) {
            return $item->area . '|' . $item->nama_section;
        })->map(function ($items, $key) {
            [$area, $nama_section] = explode('|', $key);
            return [
                'area' => $area,
                'nama_section' => $nama_section,
                'data' => $items->map(function ($d) {
                    return [
                        'no' => $d->no,
                        'tipe' => $d->tipe,
                        'deskripsi' => $d->deskripsi,
                        'qty' => $d->qty,
                        'satuan' => $d->satuan,
                        'harga_satuan' => $d->harga_satuan,
                        'harga_total' => $d->harga_total,
                        'hpp' => $d->hpp,
                        'is_mitra' => $d->is_mitra,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return view('penawaran.detail', compact('penawaran', 'sections', 'profit', 'jasaDetails', 'jasa'));
    }

    public function save(Request $request)
    {
        $data = $request->all();
        Log::debug('PenawaranController::save payload', $data);

        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $profit = $data['profit'] ?? 0;
        $ppnPersen = $data['ppn_persen'] ?? 11; // Default 11%

        if (!$penawaranId) {
            Log::warning('PenawaranController::save missing penawaran_id', $data);
            return response()->json(['error' => 'Penawaran ID tidak ditemukan'], 400);
        }

        try {
            // key existingDetails dengan normalisasi area & nama_section => hindari null collisions
            $existingDetails = \App\Models\PenawaranDetail::where('id_penawaran', $penawaranId)
                ->get()
                ->keyBy(function ($item) {
                    $area = (string) ($item->area ?? '');
                    $nama = (string) ($item->nama_section ?? '');
                    $no = (string) ($item->no ?? '');
                    return $no . '|' . $area . '|' . $nama;
                });

            Log::debug('Existing details count', ['count' => $existingDetails->count()]);

            $newKeys = [];
            $totalKeseluruhan = 0;

            foreach ($sections as $section) {
                // normalisasi area & nama_section agar tidak null
                $area = (string) ($section['area'] ?? '');
                $namaSection = (string) ($section['nama_section'] ?? '');

                foreach ($section['data'] as $row) {
                    $noStr = (string) ($row['no'] ?? '');
                    $key = $noStr . '|' . $area . '|' . $namaSection;
                    $newKeys[] = $key;

                    $hargaTotal = floatval($row['harga_total'] ?? 0);
                    $totalKeseluruhan += $hargaTotal;

                    $values = [
                        'tipe' => $row['tipe'] ?? null,
                        'deskripsi' => $row['deskripsi'] ?? null,
                        'qty' => $row['qty'] ?? null,
                        'satuan' => $row['satuan'] ?? null,
                        'harga_satuan' => $row['harga_satuan'] ?? null,
                        'harga_total' => $hargaTotal,
                        'hpp' => $row['hpp'] ?? null,
                        'profit' => $profit,
                        'nama_section' => $namaSection,
                        'area' => $area,
                        'is_mitra' => isset($row['is_mitra']) ? (int)$row['is_mitra'] : 0, // Tambahkan ini

                    ];

                    if (isset($existingDetails[$key])) {
                        Log::debug('Updating existing detail', ['key' => $key, 'values' => $values]);
                        $existingDetails[$key]->update($values);
                    } else {
                        $createAttrs = array_merge($values, [
                            'id_penawaran' => $penawaranId,
                            'no' => $row['no'] ?? null,
                        ]);
                        Log::debug('Creating new detail', ['key' => $key, 'attrs' => $createAttrs]);
                        \App\Models\PenawaranDetail::create($createAttrs);
                    }
                }
            }

            // Hapus data yang tidak ada lagi — gunakan nama_section juga
            \App\Models\PenawaranDetail::where('id_penawaran', $penawaranId)
                ->whereNotIn(DB::raw("CONCAT(no, '|', IFNULL(area, ''), '|', IFNULL(nama_section, ''))"), $newKeys)
                ->delete();

            $isBest = !empty($data['is_best_price']) ? 1 : 0;
            $bestPrice = isset($data['best_price']) ? floatval($data['best_price']) : 0;
            $baseAmount = $isBest ? $bestPrice : $totalKeseluruhan;

            $ppnNominal = ($baseAmount * $ppnPersen) / 100;
            $grandTotal = $baseAmount + $ppnNominal;

            \App\Models\Penawaran::where('id_penawaran', $penawaranId)->update([
                'total' => $totalKeseluruhan,
                'ppn_persen' => $ppnPersen,
                'ppn_nominal' => $ppnNominal,
                'grand_total' => $grandTotal,
                'is_best_price' => $isBest,
                'best_price' => $bestPrice
            ]);

            Log::debug('Penawaran saved', ['id_penawaran' => $penawaranId, 'total' => $totalKeseluruhan]);

            return response()->json([
                'success' => true,
                'total' => $totalKeseluruhan,
                'base_amount' => $baseAmount,
                'ppn_nominal' => $ppnNominal,
                'grand_total' => $grandTotal
            ]);
        } catch (\Throwable $e) {
            Log::error('PenawaranController::save error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'payload' => $data]);
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    public function preview(Request $request)
    {
        $id = $request->query('id');
        $penawaran = \App\Models\Penawaran::find($id);

        if (!$penawaran) {
            return redirect()->route('penawaran.list')->with('error', 'Penawaran tidak ditemukan');
        }

        $details = $penawaran->details()->get();
        $jasaDetails = \App\Models\JasaDetail::where('id_penawaran', $penawaran->id_penawaran)->get();

        $sections = $details->groupBy(function ($item) {
            return $item->area . '|' . $item->nama_section;
        })->map(function ($items, $key) {
            [$area, $nama_section] = explode('|', $key);
            return [
                'area' => $area,
                'nama_section' => $nama_section,
                'data' => $items->map(function ($d) {
                    return [
                        'no' => $d->no,
                        'tipe' => $d->tipe,
                        'deskripsi' => $d->deskripsi,
                        'qty' => $d->qty,
                        'satuan' => $d->satuan,
                        'harga_satuan' => $d->harga_satuan,
                        'harga_total' => $d->harga_total,
                        'hpp' => $d->hpp,
                        'is_mitra' => $d->is_mitra,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return view('penawaran.preview', compact('penawaran', 'sections', 'jasaDetails'));
    }

    public function exportPdf(Request $request)
    {
        $id = $request->query('id');
        $penawaran = \App\Models\Penawaran::find($id);
        $details = $penawaran ? $penawaran->details()->get() : collect();


        // Grouping section, sama seperti preview
        $sections = $details->groupBy(function ($item) {
            return $item->nama_section;
        })->map(function ($items, $nama_section) {
            return [
                'nama_section' => $nama_section,
                'data' => $items->map(function ($d) {
                    return [
                        'no' => $d->no,
                        'tipe' => $d->tipe,
                        'deskripsi' => $d->deskripsi,
                        'qty' => $d->qty,
                        'satuan' => $d->satuan,
                        'harga_satuan' => $d->harga_satuan,
                        'harga_total' => $d->harga_total,
                        'is_mitra' => $d->is_mitra,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        $groupedSections = [];
        foreach ($details as $row) {
            $section = $row->nama_section ?: 'Section';
            $area = $row->area ?: '-';
            $groupedSections[$section][$area][] = $row;
        }

        $jasa = \App\Models\Jasa::where('id_penawaran', $penawaran->id_penawaran)->first();

        $pdf = Pdf::loadView('penawaran.pdf', compact('penawaran', 'groupedSections', 'jasa'));
        $safeNoPenawaran = str_replace(['/', '\\'], '-', $penawaran->no_penawaran);
        return $pdf->download('Penawaran-' . $safeNoPenawaran . '.pdf');
    }

    public function saveNotes(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string'
        ]);

        $penawaran = \App\Models\Penawaran::findOrFail($id);
        $penawaran->note = $request->note;
        $penawaran->save();

        return redirect()->back()->with('success', 'Notes berhasil disimpan.');
    }

    public function saveBestPrice(Request $request, $id)
    {
        $isBest = $request->has('is_best_price') ? 1 : 0;
        $bestPrice = $isBest ? ($request->best_price ?? 0) : 0;

        $penawaran = \App\Models\Penawaran::findOrFail($id);
        $penawaran->best_price = $bestPrice;
        $penawaran->is_best_price = $isBest;
        $penawaran->save();

        return redirect()->back()->with('success', 'Best Price berhasil disimpan.');
    }
}
