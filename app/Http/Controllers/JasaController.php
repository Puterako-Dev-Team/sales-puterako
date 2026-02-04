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

    /**
     * Helper method untuk recalculate grand_total otomatis saat jasa disimpan
     * Formula: Grand Total = (Total Penawaran + Total Jasa) + PPN
     * 
     * @param $penawaranId ID penawaran
     * @param $version Version penawaran
     * @return array dengan grand_total dan ppn_nominal
     */
    private function recalculateGrandTotal($penawaranId, $version)
    {
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)
            ->first();
        
        if (!$versionRow) {
            return ['grand_total' => 0, 'ppn_nominal' => 0];
        }
        
        // Ambil komponen-komponen
        $totalPenawaran = floatval($versionRow->penawaran_total_awal ?? 0);
        $totalJasa = floatval($versionRow->jasa_grand_total ?? 0);
        $ppnPercent = floatval($versionRow->ppn_persen ?? 11);
        $isBestPrice = boolval($versionRow->is_best_price ?? false);
        $bestPrice = floatval($versionRow->best_price ?? 0);
        
        // Hitung base amount (gunakan best price jika ada, sebaliknya gunakan penawaran total)
        $baseAmount = $isBestPrice && $bestPrice > 0 ? $bestPrice : $totalPenawaran;
        
        // Hitung subtotal (penawaran/best price + jasa)
        $subtotal = $baseAmount + $totalJasa;
        
        // Hitung PPN dari subtotal
        $ppnNominal = ($subtotal * $ppnPercent) / 100;
        
        // Grand Total = subtotal + PPN
        $grandTotal = $subtotal + $ppnNominal;
        
        // Update grand_total dan ppn_nominal di database
        $versionRow->grand_total = $grandTotal;
        $versionRow->ppn_nominal = $ppnNominal;
        $versionRow->save();
        
        return ['grand_total' => $grandTotal, 'ppn_nominal' => $ppnNominal];
    }
    public function save(Request $request)
    {
        $data = $request->all();
        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $profitPercent = floatval($data['profit'] ?? 0);
        $pphPercent = floatval($data['pph'] ?? 0);
        $useBpjs = boolval($data['use_bpjs'] ?? false);
        $ringkasan = $data['ringkasan'] ?? null;
        $version = $data['version'] ?? 1;

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)->first();
        if (!$versionRow) {
            $versionRow = \App\Models\PenawaranVersion::create([
                'penawaran_id' => $penawaranId,
                'version' => $version,
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
                $pembulatan = intval($section['pembulatan'] ?? 0);
                $totalAwal += $pembulatan; // ✅ gunakan pembulatan yang sudah dibulatkan
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

            // Hitung BPJS Konstruksi (hanya jika useBpjs = true)
            $bpjskPercent = 0;
            $bpjskValue = 0;
            
            if ($useBpjs) {
                $bpjskPercent = $this->getBpjskPercent($totalPenawaran);
                $bpjskValue = ($totalPenawaran + $grandTotalPembulatan) * $bpjskPercent;
            }

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
            $versionRow->jasa_use_bpjs       = $useBpjs ? 1 : 0;
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
                    // Skip hanya jika semua field penting kosong
                    if (empty($row['deskripsi']) && empty($row['no']) && empty($row['vol']) && empty($row['hari']) && empty($row['orang']) && empty($row['unit'])) continue;
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
                ->whereNotIn('id_jasa_detail', $processedIds)
                ->delete();

            // OTOMATIS HITUNG & UPDATE GRAND_TOTAL dengan semua komponen
            $grandTotalResult = $this->recalculateGrandTotal($penawaranId, $version);

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
                'grand_total_penawaran' => $grandTotalResult['grand_total'],
                'ppn_nominal' => $grandTotalResult['ppn_nominal'],
                'processed_ids' => $processedIds,
                'message' => 'Rincian jasa berhasil disimpan. Grand total telah otomatis terupdate!'
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
