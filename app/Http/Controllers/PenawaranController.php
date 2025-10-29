<?php

namespace App\Http\Controllers;

use App\Models\PenawaranDetail;
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
        $version = $request->query('version');

        $penawaran = \App\Models\Penawaran::find($id);

        // Ambil versi aktif
        $activeVersion = $version ?? \App\Models\PenawaranVersion::where('penawaran_id', $id)->max('version');
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)->where('version', $activeVersion)->first();
        $activeVersionId = $versionRow ? $versionRow->id : null;

        $details = PenawaranDetail::where('version_id', $activeVersionId)
            ->orderBy('id_penawaran_detail', 'asc')
            ->get();
        $profit = $details->first()->profit ?? 0;
        $jasa = $versionRow ? $versionRow->jasa : null;
        $jasaDetails = $versionRow ? $versionRow->jasaDetails : collect();

        $totalPenawaran = $details->sum('harga_total');
        $grandTotalJasa = $jasa ? $jasa->grand_total : 0;

        $grandTotal = $totalPenawaran + $grandTotalJasa;


        // Ambil field dinamis dari versionRow
        $ppnPersen = $versionRow->ppn_persen ?? 11;
        $isBest = $versionRow->is_best_price ?? false;
        $bestPrice = $versionRow->best_price ?? 0;
        $baseAmount = ($isBest && $bestPrice > 0) ? $bestPrice : ($totalPenawaran + $grandTotalJasa);

        $ppnNominal = ($baseAmount * $ppnPersen) / 100;
        $grandTotalWithPpn = $baseAmount + $ppnNominal;

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
                        'added_cost' => $d->added_cost,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return view('penawaran.detail', compact(
            'penawaran',
            'sections',
            'profit',
            'jasaDetails',
            'jasa',
            'activeVersion',
            'versionRow',
            'totalPenawaran',
            'grandTotalJasa',
            'grandTotal',
            'ppnPersen',
            'ppnNominal',
            'grandTotalWithPpn',
            'isBest',
            'bestPrice'
        ));
    }

    public function save(Request $request)
    {
        $data = $request->all();
        Log::debug('PenawaranController::save payload', $data);

        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $profit = $data['profit'] ?? 0;
        $ppnPersen = $data['ppn_persen'] ?? 11; // Default 11%
        $version = $data['version'] ?? 1;

        if (!$penawaranId) {
            Log::warning('PenawaranController::save missing penawaran_id', $data);
            return response()->json(['error' => 'Penawaran ID tidak ditemukan'], 400);
        }

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)->where('version', $version)->first();
        if (!$versionRow) {
            $versionRow = \App\Models\PenawaranVersion::create([
                'penawaran_id' => $penawaranId,
                'version' => 1,
                'notes' => 'Penawaran Awal',
                'status' => 'draft'
            ]);
        }
        $version_id = $versionRow->id;

        try {
            // key existingDetails dengan normalisasi area & nama_section => hindari null collisions
            $existingDetails = \App\Models\PenawaranDetail::where('id_penawaran', $penawaranId)
                ->where('version_id', $version_id)
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
                        'is_mitra' => isset($row['is_mitra']) ? (int)$row['is_mitra'] : 0,
                        'added_cost' => $row['added_cost'] ?? 0,
                        'version_id' => $version_id, // pastikan selalu isi version_id
                    ];

                    if (isset($existingDetails[$key])) {
                        $existingDetails[$key]->update($values);
                    } else {
                        $createAttrs = array_merge($values, [
                            'id_penawaran' => $penawaranId,
                            'no' => $row['no'] ?? null,
                        ]);
                        \App\Models\PenawaranDetail::create($createAttrs);
                    }
                }
            }

            // Hapus data yang tidak ada lagi — gunakan nama_section juga
            \App\Models\PenawaranDetail::where('id_penawaran', $penawaranId)
                ->where('version_id', $version_id)
                ->whereNotIn(DB::raw("CONCAT(no, '|', IFNULL(area, ''), '|', IFNULL(nama_section, ''))"), $newKeys)
                ->delete();


            $isBest = !empty($data['is_best_price']) ? 1 : 0;
            $bestPrice = isset($data['best_price']) ? floatval($data['best_price']) : 0;
            $baseAmount = $isBest ? $bestPrice : $totalKeseluruhan;

            $ppnNominal = ($baseAmount * $ppnPersen) / 100;
            $grandTotal = $baseAmount + $ppnNominal;

            // Update ke penawaran_versions (bukan penawarans)
            $versionRow->ppn_persen = $ppnPersen;
            $versionRow->is_best_price = $isBest;
            $versionRow->best_price = $bestPrice;
            $versionRow->ppn_nominal = $ppnNominal;
            $versionRow->grand_total = $grandTotal;
            $versionRow->save();

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
        $version = $request->query('version');

        $penawaran = \App\Models\Penawaran::find($id);

        if (!$penawaran) {
            return redirect()->route('penawaran.list')->with('error', 'Penawaran tidak ditemukan');
        }

        // Ambil versi aktif
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)->where('version', $version)->first();
        $activeVersionId = $versionRow ? $versionRow->id : null;

        // Ambil detail dan jasa sesuai versi aktif
        $details = PenawaranDetail::where('version_id', $activeVersionId)->get();
        $totalPenawaran = $details->sum('harga_total');

        $jasa = $versionRow ? $versionRow->jasa : null;
        $grandTotalJasa = $jasa ? $jasa->grand_total : 0;

        // Hitung grand total dinamis
        $grandTotal = $totalPenawaran + $grandTotalJasa;
        $ppnPersen = $versionRow->ppn_persen ?? 11;
        $isBest = $versionRow->is_best_price ?? false;
        $bestPrice = $versionRow->best_price ?? 0;
        $baseAmount = ($isBest && $bestPrice > 0) ? $bestPrice : ($totalPenawaran + $grandTotalJasa);

        $ppnNominal = ($baseAmount * $ppnPersen) / 100;
        $grandTotalWithPpn = $baseAmount + $ppnNominal;

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

        $jasaDetails = $versionRow ? $versionRow->jasaDetails : collect();

        return view('penawaran.preview', compact(
            'penawaran',
            'sections',
            'jasaDetails',
            'jasa',
            'totalPenawaran',
            'grandTotalJasa',
            'grandTotal'
        ));
    }

    public function exportPdf(Request $request)
    {
        $id = $request->query('id');
        $penawaran = \App\Models\Penawaran::find($id);
        $version = $request->query('version');
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)->where('version', $version)->first();
        $details = PenawaranDetail::where('version_id', $versionRow->id)->get();


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

    public function createRevision($id)
    {
        $penawaran = \App\Models\Penawaran::findOrFail($id);

        // Ambil versi terakhir
        $lastVersion = \App\Models\PenawaranVersion::where('penawaran_id', $id)->max('version');
        $newVersion = $lastVersion + 1;

        // Copy versi sebelumnya
        $oldVersion = \App\Models\PenawaranVersion::where('penawaran_id', $id)->where('version', $lastVersion)->first();

        // Buat versi baru
        $newVersionRow = \App\Models\PenawaranVersion::create([
            'penawaran_id' => $id,
            'version' => $newVersion,
            'notes' => 'Revisi baru',
            'status' => 'draft'
        ]);

        // Copy penawaran_detail
        foreach ($oldVersion->details as $detail) {
            \App\Models\PenawaranDetail::create([
                'version_id'    => $newVersionRow->id,
                'id_penawaran'  => $detail->id_penawaran,
                'area'          => $detail->area,
                'nama_section'  => $detail->nama_section,
                'no'            => $detail->no,
                'tipe'          => $detail->tipe,
                'deskripsi'     => $detail->deskripsi,
                'qty'           => $detail->qty,
                'satuan'        => $detail->satuan,
                'harga_satuan'  => $detail->harga_satuan,
                'harga_total'   => $detail->harga_total,
                'hpp'           => $detail->hpp,
                'is_mitra'      => $detail->is_mitra,
                'added_cost'    => $detail->added_cost,
                'profit'        => $detail->profit,
            ]);
        }

        // Copy jasa dan jasa_detail
        $oldJasa = \App\Models\Jasa::where('version_id', $oldVersion->id)->first();
        if ($oldJasa) {
            $newJasa = \App\Models\Jasa::create([
                'version_id'     => $newVersionRow->id,
                'id_penawaran'   => $id,
                'ringkasan'      => $oldJasa->ringkasan,
                'profit_percent' => $oldJasa->profit_percent,
                'profit_value'   => $oldJasa->profit_value,
                'pph_percent'    => $oldJasa->pph_percent,
                'pph_value'      => $oldJasa->pph_value,
                'bpjsk_percent'  => $oldJasa->bpjsk_percent,
                'bpjsk_value'    => $oldJasa->bpjsk_value,
                'grand_total'    => $oldJasa->grand_total,
            ]);
            foreach ($oldJasa->details as $jasaDetail) {
                \App\Models\JasaDetail::create([
                    'version_id'    => $newVersionRow->id,
                    'id_jasa'       => $newJasa->id_jasa,
                    'id_penawaran'  => $jasaDetail->id_penawaran,
                    'nama_section'  => $jasaDetail->nama_section,
                    'no'            => $jasaDetail->no,
                    'deskripsi'     => $jasaDetail->deskripsi,
                    'vol'           => $jasaDetail->vol,
                    'hari'          => $jasaDetail->hari,
                    'orang'         => $jasaDetail->orang,
                    'unit'          => $jasaDetail->unit,
                    'total'         => $jasaDetail->total,
                    'pembulatan'    => $jasaDetail->pembulatan,
                    'profit'        => $jasaDetail->profit,
                ]);
            }
        }



        return redirect()->route('penawaran.show', ['id' => $id, 'version' => $newVersion]);
    }
}
