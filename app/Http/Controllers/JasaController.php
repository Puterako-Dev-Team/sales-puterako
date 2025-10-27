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
        Log::debug('JasaController::save payload', $data);

        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $profitPercent = floatval($data['profit'] ?? 0);
        $pphPercent = floatval($data['pph'] ?? 0);
        $ringkasan = $data['ringkasan'] ?? null;

        if (!$penawaranId) {
            return response()->json(['error' => 'penawaran_id required'], 400);
        }

        DB::beginTransaction();
        try {
            // hitung total dari semua section/row
            $totalAwal = 0;
            foreach ($sections as $section) {
                foreach ($section['data'] as $row) {
                    $totalAwal += floatval($row['total'] ?? 0);
                }
            }

            // formula inverse
            $afterProfit = $profitPercent > 0 ? ($totalAwal / (1 - ($profitPercent / 100))) : $totalAwal;
            $afterPph    = $pphPercent > 0 ? ($afterProfit / (1 - ($pphPercent / 100))) : $afterProfit;

            $profitValueToStore = round($afterProfit, 2);
            $pphValueToStore    = round($afterPph, 2);
            $grandTotalPembulatan = array_sum(array_map(function ($section) {
                return intval($section['pembulatan'] ?? 0);
            }, $sections));

            $penawaran = \App\Models\Penawaran::find($penawaranId);
            $totalPenawaran = $penawaran ? floatval($penawaran->total) : 0;

            // Hitung BPJS Konstruksi
            $bpjskPercent = $this->getBpjskPercent($totalPenawaran);
            $bpjskValue = ($totalPenawaran + $grandTotalPembulatan) * $bpjskPercent;

            Log::debug('BPJS debug', [
                'totalPenawaran' => $totalPenawaran,
                'grandTotalPembulatan' => $grandTotalPembulatan,
                'sum_for_bpjsk' => ($totalPenawaran + $grandTotalPembulatan),
                'bpjskPercent_decimal' => $bpjskPercent,
                'bpjskPercent_saved' => $bpjskPercent * 100,
                'bpjskValue_calculated' => $bpjskValue,
                'grandTotalJasaFinal' => $grandTotalPembulatan + $bpjskValue
            ]);
            // Update grand total jasa
            $grandTotalJasaFinal = $grandTotalPembulatan + $bpjskValue;

            // jika header jasa sudah ada -> update, jika tidak -> create
            $existingJasa = Jasa::where('id_penawaran', $penawaranId)->first();
            if ($existingJasa) {
                Log::debug('Existing Jasa found - updating', ['id_jasa' => $existingJasa->id_jasa]);

                $existingJasa->update([
                    'profit_percent' => $profitPercent,
                    'profit_value'   => $profitValueToStore,
                    'pph_percent'    => $pphPercent,
                    'pph_value'      => $pphValueToStore,
                    'bpjsk_percent'  => $bpjskPercent * 100,
                    'bpjsk_value'    => $bpjskValue,
                    'grand_total'    => $grandTotalJasaFinal,
                    'ringkasan'      => $ringkasan,
                ]);
                Log::debug('Existing Jasa updated - after', ['after' => $existingJasa->fresh()->toArray()]);


                $jasa = $existingJasa;
            } else {
                $jasa = Jasa::create([
                    'id_penawaran'   => $penawaranId,
                    'profit_percent' => $profitPercent,
                    'profit_value'   => $profitValueToStore,
                    'pph_percent'    => $pphPercent,
                    'pph_value'      => $pphValueToStore,
                    'bpjsk_percent'  => $bpjskPercent * 100,
                    'bpjsk_value'    => $bpjskValue,
                    'grand_total'    => $grandTotalJasaFinal,
                    'ringkasan'      => $ringkasan,
                ]);
                Log::debug('Created Jasa header', ['id_jasa' => $jasa->id_jasa]);
            }

            // --- PERBAIKAN: Gunakan ID sebagai key utama ---
            $processedIds = [];

            foreach ($sections as $section) {
                $namaSection = $section['nama_section'] ?? '';
                $pembulatan = $section['pembulatan'] ?? 0;
                foreach ($section['data'] as $row) {
                    // Skip empty rows
                    if (empty($row['deskripsi']) && empty($row['no'])) {
                        continue;
                    }

                    $idJasaDetail = $row['id_jasa_detail'] ?? null;

                    $attrs = [
                        'id_penawaran'  => $penawaranId,
                        'id_jasa'       => $jasa->id_jasa,
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
                        'pembulatan'   => $pembulatan,
                    ];

                    if ($idJasaDetail) {
                        // UPDATE existing record by ID
                        $detail = JasaDetail::find($idJasaDetail);
                        if ($detail) {
                            $detail->update($attrs);
                            $processedIds[] = $idJasaDetail;
                            Log::debug('Updated Jasa detail by ID', [
                                'id_jasa_detail' => $idJasaDetail,
                                'attrs' => $attrs
                            ]);
                        } else {
                            // ID tidak ditemukan, create new
                            $detail = JasaDetail::create($attrs);
                            $processedIds[] = $detail->getKey();
                            Log::debug('ID not found, created new Jasa detail', [
                                'id_jasa_detail' => $detail->getKey(),
                                'attrs' => $attrs
                            ]);
                        }
                    } else {
                        // CREATE new record
                        $detail = JasaDetail::create($attrs);
                        $processedIds[] = $detail->getKey();
                        Log::debug('Created new Jasa detail', [
                            'id_jasa_detail' => $detail->getKey(),
                            'attrs' => $attrs
                        ]);
                    }
                }
            }

            // DELETE records yang tidak ada di payload (dihapus user)
            $deleted = JasaDetail::where('id_jasa', $jasa->id_jasa)
                ->whereNotIn(JasaDetail::query()->getModel()->getKeyName(), $processedIds)
                ->delete();

            if ($deleted > 0) {
                Log::debug('Deleted unused Jasa details', [
                    'id_jasa' => $jasa->id_jasa,
                    'deleted_count' => $deleted
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'id_jasa' => $jasa->id_jasa,
                'total_awal' => $totalAwal,
                'profit_percent' => $profitPercent,
                'profit_value' => $profitValueToStore,
                'pph_percent' => $pphPercent,
                'pph_value' => $pphValueToStore,
                'grand_total' => $grandTotalPembulatan,
                'processed_ids' => $processedIds,
                'deleted_count' => $deleted ?? 0
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Jasa save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $data
            ]);
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
        $jasa = \App\Models\Jasa::where('id_penawaran', $id_penawaran)->first();
        if ($jasa) {
            $jasa->ringkasan = $request->ringkasan;
            $jasa->save();
            return back()->with('success', 'Ringkasan jasa berhasil disimpan.');
        }
        return back()->with('error', 'Data jasa tidak ditemukan.');
    }
}
