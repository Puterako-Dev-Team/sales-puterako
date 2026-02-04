<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userRole = auth()->user()->role ?? null;
        $userId = auth()->id();
        $allowedRoles = ['supervisor', 'manager', 'administrator', 'direktur'];
        $canViewCharts = in_array($userRole, $allowedRoles) || $userRole === 'staff';

        $topCompanies = null;
        $picStats = null;
        $statusCounts = null;
        $dateStats = null;
        $omzetPerSalesSuccess = null;
        $omzetPerSalesPO = null;
        $omzetPerBulanSuccess = null;
        $omzetPerBulanPO = null;
        $totalOmzetKeseluruhanSuccess = null;
        $totalOmzetKeseluruhanPO = null;
        $month = null;

        // Hanya ambil data chart jika role diizinkan (termasuk staff)
        if ($canViewCharts) {
            $month = $request->query('month', now()->format('Y-m'));
            list($year, $monthNum) = explode('-', $month);

            // Untuk staff, filter hanya penawaran milik mereka
            if ($userRole === 'staff') {
                // 1. Perusahaan paling sering penawaran (milik staff tersebut)
                $topCompanies = \App\Models\Penawaran::select('nama_perusahaan', DB::raw('count(*) as total'))
                    ->where('user_id', $userId)
                    ->groupBy('nama_perusahaan')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get();

                // 2. Pie chart status (milik staff tersebut)
                $statusCounts = \App\Models\Penawaran::select('status', DB::raw('count(*) as total'))
                    ->where('user_id', $userId)
                    ->groupBy('status')
                    ->pluck('total', 'status');

                // 3. Line chart - per tanggal dalam satu bulan (milik staff tersebut)
                $dateStats = \App\Models\Penawaran::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
                    ->where('user_id', $userId)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $monthNum)
                    ->groupBy('tanggal')
                    ->orderBy('tanggal')
                    ->get();
                    
                // 4. Omzet per bulan untuk staff - SUCCESS
                $latestVersionStaffSuccess = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerBulanSuccess = \App\Models\PenawaranVersion::selectRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") as bulan, SUM(penawaran_versions.grand_total) as total_omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->joinSub($latestVersionStaffSuccess, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.user_id', $userId)
                    ->where('penawarans.status', 'success')
                    ->groupBy(DB::raw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01")'))
                    ->orderByRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") desc')
                    ->limit(12)
                    ->get();
                    
                // 4b. Omzet per bulan untuk staff - PO
                $latestVersionStaffPO = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerBulanPO = \App\Models\PenawaranVersion::selectRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") as bulan, SUM(penawaran_versions.grand_total) as total_omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->joinSub($latestVersionStaffPO, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.user_id', $userId)
                    ->where('penawarans.status', 'po')
                    ->groupBy(DB::raw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01")'))
                    ->orderByRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") desc')
                    ->limit(12)
                    ->get();
                    
                // 6. Total omzet keseluruhan untuk staff - SUCCESS
                $latestVersionStaffTotalSuccess = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $totalOmzetKeseluruhanSuccess = \App\Models\PenawaranVersion::joinSub($latestVersionStaffTotalSuccess, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.user_id', $userId)
                    ->where('penawarans.status', 'success')
                    ->sum('penawaran_versions.grand_total');
                    
                // 6b. Total omzet keseluruhan untuk staff - PO
                $latestVersionStaffTotalPO = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $totalOmzetKeseluruhanPO = \App\Models\PenawaranVersion::joinSub($latestVersionStaffTotalPO, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.user_id', $userId)
                    ->where('penawarans.status', 'po')
                    ->sum('penawaran_versions.grand_total');
            } else {
                // Untuk role lain (supervisor, manager, administrator, direktur)
                // 1. Perusahaan paling sering penawaran
                $topCompanies = \App\Models\Penawaran::select('nama_perusahaan', DB::raw('count(*) as total'))
                    ->groupBy('nama_perusahaan')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get();

                // 2. Penawaran per PIC Admin per status
                $picStats = \App\Models\User::whereHas('penawarans')
                    ->withCount([
                        'penawarans as draft' => function ($q) {
                            $q->where('status', 'draft');
                        },
                        'penawarans as success' => function ($q) {
                            $q->where('status', 'success');
                        },
                        'penawarans as lost' => function ($q) {
                            $q->where('status', 'lost');
                        },
                    ])
                    ->get(['id', 'name'])
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'draft' => $user->draft,
                            'success' => $user->success,
                            'lost' => $user->lost,
                            'total' => $user->draft + $user->success + $user->lost
                        ];
                    });

                // 3. Pie chart status
                $statusCounts = \App\Models\Penawaran::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                // Line chart - per tanggal dalam satu bulan
                $dateStats = \App\Models\Penawaran::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $monthNum)
                    ->groupBy('tanggal')
                    ->orderBy('tanggal')
                    ->get();
                    
                // 4. Omzet per Sales/Staff untuk bulan terpilih - SUCCESS
                $latestVersionPerSalesSuccess = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerSalesSuccess = \App\Models\User::select('users.id', 'users.name')
                    ->join('penawarans', 'users.id', '=', 'penawarans.user_id')
                    ->join('penawaran_versions', 'penawarans.id_penawaran', '=', 'penawaran_versions.penawaran_id')
                    ->joinSub($latestVersionPerSalesSuccess, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->where('penawarans.status', 'success')
                    ->whereYear('penawarans.created_at', $year)
                    ->whereMonth('penawarans.created_at', $monthNum)
                    ->groupBy('users.id', 'users.name')
                    ->selectRaw('users.id, users.name, SUM(penawaran_versions.grand_total) as omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->orderByDesc('omzet')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'omzet' => $user->omzet,
                            'jumlah_penawaran' => $user->jumlah_penawaran
                        ];
                    })
                    ->values();
                    
                // 4b. Omzet per Sales/Staff untuk bulan terpilih - PO
                $latestVersionPerSalesPO = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerSalesPO = \App\Models\User::select('users.id', 'users.name')
                    ->join('penawarans', 'users.id', '=', 'penawarans.user_id')
                    ->join('penawaran_versions', 'penawarans.id_penawaran', '=', 'penawaran_versions.penawaran_id')
                    ->joinSub($latestVersionPerSalesPO, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->where('penawarans.status', 'po')
                    ->whereYear('penawarans.created_at', $year)
                    ->whereMonth('penawarans.created_at', $monthNum)
                    ->groupBy('users.id', 'users.name')
                    ->selectRaw('users.id, users.name, SUM(penawaran_versions.grand_total) as omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->orderByDesc('omzet')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'omzet' => $user->omzet,
                            'jumlah_penawaran' => $user->jumlah_penawaran
                        ];
                    })
                    ->values();
                    
                // 5. Omzet per bulan (12 bulan terakhir) - SUCCESS
                // Subquery untuk mendapatkan versi terbaru per penawaran
                $latestVersionSuccess = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerBulanSuccess = \App\Models\PenawaranVersion::selectRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") as bulan, SUM(penawaran_versions.grand_total) as total_omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->joinSub($latestVersionSuccess, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.status', 'success')
                    ->groupBy(DB::raw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01")'))
                    ->orderByRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") desc')
                    ->limit(12)
                    ->get();
                    
                // 5b. Omzet per bulan (12 bulan terakhir) - PO
                $latestVersionPO = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $omzetPerBulanPO = \App\Models\PenawaranVersion::selectRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") as bulan, SUM(penawaran_versions.grand_total) as total_omzet, COUNT(DISTINCT penawarans.id_penawaran) as jumlah_penawaran')
                    ->joinSub($latestVersionPO, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.status', 'po')
                    ->groupBy(DB::raw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01")'))
                    ->orderByRaw('DATE_FORMAT(penawarans.created_at, "%Y-%m-01") desc')
                    ->limit(12)
                    ->get();
                    
                // 7. Total omzet keseluruhan - SUCCESS
                $latestVersionTotalSuccess = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $totalOmzetKeseluruhanSuccess = \App\Models\PenawaranVersion::joinSub($latestVersionTotalSuccess, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.status', 'success')
                    ->sum('penawaran_versions.grand_total');
                    
                // 7b. Total omzet keseluruhan - PO
                $latestVersionTotalPO = \App\Models\PenawaranVersion::selectRaw('penawaran_id, MAX(id) as max_id')
                    ->groupBy('penawaran_id');
                
                $totalOmzetKeseluruhanPO = \App\Models\PenawaranVersion::joinSub($latestVersionTotalPO, 'latest_versions', function($join) {
                        $join->on('penawaran_versions.penawaran_id', '=', 'latest_versions.penawaran_id')
                             ->on('penawaran_versions.id', '=', 'latest_versions.max_id');
                    })
                    ->join('penawarans', 'penawaran_versions.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->where('penawarans.status', 'po')
                    ->sum('penawaran_versions.grand_total');
            }
        }

        return view('dashboard', compact(
            'topCompanies', 
            'picStats', 
            'statusCounts', 
            'dateStats', 
            'omzetPerSalesSuccess',
            'omzetPerSalesPO',
            'omzetPerBulanSuccess',
            'omzetPerBulanPO',
            'totalOmzetKeseluruhanSuccess',
            'totalOmzetKeseluruhanPO',
            'month', 
            'canViewCharts'
        ));
    }
}
