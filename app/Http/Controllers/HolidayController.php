<?php
// filepath: c:\laragon\www\sales-puterako\app\Http\Controllers\HolidayController.php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    /**
     * Display holiday management page
     */
    public function index(Request $request)
    {
        $query = Holiday::query()->orderBy('tanggal_libur', 'asc');

        // Filter tahun
        if ($request->has('year')) {
            $query->whereYear('tanggal_libur', $request->year);
        }

        // Filter bulan (default: bulan sekarang)
        $month = $request->input('month', now()->month);
        $query->whereMonth('tanggal_libur', $month);

        // Filter status (default: libur nasional)
        $status = $request->input('status', 'nasional');
        if ($status === 'nasional') {
            $query->where('libur_nasional', true);
        } elseif ($status === 'cuti') {
            $query->where('libur_nasional', false);
        }

        $holidays = $query->paginate(10)->appends($request->query());
        return view('users.holiday', compact('holidays', 'month', 'status'));
    }

    /**
     * Get holidays data for table
     */
    public function getData(Request $request)
    {
        $query = Holiday::query()->orderBy('tanggal_libur', 'asc');

        // Filter by year if provided
        if ($request->has('year')) {
            $query->whereYear('tanggal_libur', $request->year);
        }

        $holidays = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $holidays->items(),
            'pagination' => [
                'current_page' => $holidays->currentPage(),
                'last_page' => $holidays->lastPage(),
                'per_page' => $holidays->perPage(),
                'total' => $holidays->total(),
                'from' => $holidays->firstItem(),
                'to' => $holidays->lastItem(),
                'has_more_pages' => $holidays->hasMorePages(),
            ]
        ]);
    }

    /**
     * Sync holidays from public API
     */
    public function syncFromAPI(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            
            // API endpoint yang benar sesuai screenshot Postman
            $apiUrl = "https://hari-libur-api.vercel.app/api";
            
            Log::info("Syncing holidays from API: {$apiUrl}?year={$year}");
            
            $response = Http::timeout(30)->get($apiUrl, [
                'year' => $year
            ]);

            if (!$response->successful()) {
                Log::error("API request failed: " . $response->status());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API. Status: ' . $response->status()
                ], 500);
            }

            $holidays = $response->json();
            
            Log::info("Received " . count($holidays) . " holidays from API");
            
            $imported = 0;
            $updated = 0;

            foreach ($holidays as $holiday) {
                // Validasi data dari API
                if (!isset($holiday['event_date']) || !isset($holiday['event_name'])) {
                    Log::warning("Skipping invalid holiday data", $holiday);
                    continue;
                }
                
                $existingHoliday = Holiday::where('tanggal_libur', $holiday['event_date'])->first();
                
                if ($existingHoliday) {
                    // Update hanya libur_nasional, jangan timpa hari_kerja (override dari admin)
                    $existingHoliday->update([
                        'nama_libur' => $holiday['event_name'],
                        'libur_nasional' => $holiday['is_national_holiday'] ?? true,
                    ]);
                    $updated++;
                } else {
                    Holiday::create([
                        'tanggal_libur' => $holiday['event_date'],
                        'nama_libur' => $holiday['event_name'],
                        'libur_nasional' => $holiday['is_national_holiday'] ?? true,
                    ]);
                    $imported++;
                }
            }

            Log::info("Sync completed: {$imported} imported, {$updated} updated");

            return response()->json([
                'success' => true,
                'message' => "Berhasil import {$imported} libur baru dan update {$updated} libur",
                'imported' => $imported,
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync holidays: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update holiday status
     */
    public function update(Request $request, $id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            $isLiburNasional = $request->input('libur_nasional') == '1' || $request->input('libur_nasional') === 1 || $request->input('libur_nasional') === true;

            $holiday->libur_nasional = $isLiburNasional;
            $holiday->save();

            $status = $holiday->libur_nasional ? 'Libur Nasional' : 'Hari Kerja';

            return response()->json([
                'success' => true,
                'message' => "Status berhasil diubah menjadi {$status}",
                'holiday' => $holiday
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete holiday
     */
    public function destroy($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Libur berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }
}