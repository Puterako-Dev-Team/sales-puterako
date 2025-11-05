<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Jasa;
use App\Models\JasaDetail;
use Illuminate\Support\Facades\Log;

class JasaController extends Controller
{
    private function getBpjskPercent($total)
    {
        if ($total <= 100_000_000) return 0.0024;
        if ($total <= 500_000_000) return 0.0019;
        if ($total <= 1_000_000_000) return 0.0015;
        if ($total <= 5_000_000_000) return 0.0012;
        return 0.0010;
    }
    public function save(Request $request)
    {
        $data = $request->all();
        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $profitPercent = floatval($data['profit'] ?? 0);
        $pphPercent = floatval($data['pph'] ?? 0);
        $ringkasan = $data['ringkasan'] ?? null;
        $version = $data['version'] ?? 1;

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)->first();
        if (!$versionRow) {
            $versionRow = \App\Models\PenawaranVersion::create([
                'penawaran_id' => $penawaranId,
                'version' => $version,
                'notes' => 'Penawaran Awal',
                'status' => 'draft'
            ]);
        }
        $version_id = $versionRow->id;

        if (!$penawaranId) {
            return response()->json(['error' => 'penawaran_id required'], 400);
        }

        DB::beginTransaction();
        try {
            // Hitung total awal dari semua section/row
            $totalAwal = 0;
            foreach ($sections as $section) {
                foreach ($section['data'] as $row) {
                    $totalAwal += floatval($row['total'] ?? 0);
                }
            }

            // Formula inverse profit & pph
            $afterProfit = $profitPercent > 0 ? ($totalAwal / (1 - ($profitPercent / 100))) : $totalAwal;
            $afterPph    = $pphPercent > 0 ? ($afterProfit / (1 - ($pphPercent / 100))) : $afterProfit;

            $profitValueToStore = round($afterProfit, 2);
            $pphValueToStore    = round($afterPph, 2);

            $grandTotalPembulatan = array_sum(array_map(function ($section) {
                return intval($section['pembulatan'] ?? 0);
            }, $sections));

            // $penawaran = \App\Models\Penawaran::find($penawaranId);
            // $totalPenawaran = $penawaran ? floatval($penawaran->total) : 0;

            $totalPenawaran = floatval($versionRow->penawaran_total_awal ?? 0);

            // Hitung BPJS Konstruksi
            $bpjskPercent = $this->getBpjskPercent($totalPenawaran);
            $bpjskValue = ($totalPenawaran + $grandTotalPembulatan) * $bpjskPercent;

            $grandTotalJasaFinal = $grandTotalPembulatan + $bpjskValue;

            // Simpan summary jasa ke penawaran_versions
            $versionRow->jasa_total_awal      = $totalAwal;
            $versionRow->jasa_profit_percent = $profitPercent;
            $versionRow->jasa_profit_value   = $profitValueToStore;
            $versionRow->jasa_pph_percent    = $pphPercent;
            $versionRow->jasa_pph_value      = $pphValueToStore;
            $versionRow->jasa_bpjsk_percent  = $bpjskPercent * 100;
            $versionRow->jasa_bpjsk_value    = $bpjskValue;
            $versionRow->jasa_grand_total    = $grandTotalJasaFinal;
            $versionRow->jasa_ringkasan      = $ringkasan;
            $versionRow->save();

            // Simpan ke tabel jasa (per versi)
            $jasa = Jasa::updateOrCreate(
                ['id_penawaran' => $penawaranId, 'version_id' => $version_id],
                [
                    'profit_percent' => $profitPercent,
                    'profit_value'   => $profitValueToStore,
                    'pph_percent'    => $pphPercent,
                    'pph_value'      => $pphValueToStore,
                    'bpjsk_percent'  => $bpjskPercent * 100,
                    'bpjsk_value'    => $bpjskValue,
                    'grand_total'    => $grandTotalJasaFinal,
                    'ringkasan'      => $ringkasan,
                    'version_id'     => $version_id
                ]
            );

            // Simpan JasaDetail
            $processedIds = [];
            foreach ($sections as $section) {
                $namaSection = $section['nama_section'] ?? '';
                $pembulatan = $section['pembulatan'] ?? 0;
                foreach ($section['data'] as $row) {
                    if (empty($row['deskripsi']) && empty($row['no'])) continue;
                    $idJasaDetail = $row['id_jasa_detail'] ?? null;
                    $attrs = [
                        'id_penawaran'  => $penawaranId,
                        'id_jasa'       => $jasa->id_jasa,
                        'version_id'    => $version_id,
                        'nama_section'  => $namaSection,
                        'no'            => $row['no'] ?? null,
                        'deskripsi'     => $row['deskripsi'] ?? null,
                        'vol'           => $row['vol'] ?? 0,
                        'hari'          => $row['hari'] ?? 0,
                        'orang'         => $row['orang'] ?? 0,
                        'unit'          => $row['unit'] ?? 0,
                        'total'         => $row['total'] ?? 0,
                        'profit'        => $profitPercent,
                        'pph'           => $pphPercent,
                        'pembulatan'    => $pembulatan,
                    ];
                    if ($idJasaDetail) {
                        $detail = JasaDetail::find($idJasaDetail);
                        if ($detail) {
                            $detail->update($attrs);
                            $processedIds[] = $idJasaDetail;
                        } else {
                            $detail = JasaDetail::create($attrs);
                            $processedIds[] = $detail->getKey();
                        }
                    } else {
                        $detail = JasaDetail::create($attrs);
                        $processedIds[] = $detail->getKey();
                    }
                }
            }

            // Hapus JasaDetail yang tidak ada di payload
            JasaDetail::where('id_jasa', $jasa->id_jasa)
                ->where('version_id', $version_id)
                ->whereNotIn(JasaDetail::query()->getModel()->getKeyName(), $processedIds)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'id_jasa' => $jasa->id_jasa,
                'total_awal' => $totalAwal,
                'profit_percent' => $profitPercent,
                'profit_value' => $profitValueToStore,
                'pph_percent' => $pphPercent,
                'pph_value' => $pphValueToStore,
                'bpjsk_percent' => $bpjskPercent * 100,
                'bpjsk_value' => $bpjskValue,
                'grand_total' => $grandTotalJasaFinal,
                'processed_ids' => $processedIds,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function saveRingkasan(Request $request, $id_penawaran)
    {
        $request->validate([
            'ringkasan' => 'nullable|string'
        ]);

        // Cari versi aktif dari request (atau default ke versi 1)
        $version = $request->input('version', 1);
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id_penawaran)
            ->where('version', $version)
            ->first();

        if ($versionRow) {
            $versionRow->jasa_ringkasan = $request->ringkasan;
            $versionRow->save();
            return back()->with('success', 'Ringkasan jasa berhasil disimpan ke versi penawaran.');
        }

        return back()->with('error', 'Data versi penawaran tidak ditemukan.');
    }
}
