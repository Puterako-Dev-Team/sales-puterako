<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userRole = auth()->user()->role ?? null;
        $allowedRoles = ['supervisor', 'manajer', 'administrator', 'direktur'];
        $canViewCharts = in_array($userRole, $allowedRoles);

        $topCompanies = null;
        $picStats = null;
        $statusCounts = null;
        $dateStats = null;
        $month = null;

        // Hanya ambil data chart jika role diizinkan
        if ($canViewCharts) {
            // 1. Perusahaan paling sering penawaran
            $topCompanies = \App\Models\Penawaran::select('nama_perusahaan', DB::raw('count(*) as total'))
                ->groupBy('nama_perusahaan')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            // 2. Penawaran per PIC Admin per status with company details
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
                    $penawarans = \App\Models\Penawaran::where('user_id', $user->id)
                        ->select('nama_perusahaan', 'created_at')
                        ->orderByDesc('created_at')
                        ->get()
                        ->map(function ($penawaran) {
                            $daysElapsed = max(0, intval(now()->diffInDays($penawaran->created_at)));
                            return [
                                'nama_perusahaan' => $penawaran->nama_perusahaan,
                                'created_at' => $penawaran->created_at,
                                'days_elapsed' => $daysElapsed
                            ];
                        });

                    return [
                        'name' => $user->name,
                        'draft' => $user->draft,
                        'success' => $user->success,
                        'lost' => $user->lost,
                        'total' => $user->draft + $user->success + $user->lost,
                        'companies' => $penawarans
                    ];
                });

            // 3. Pie chart status
            $statusCounts = \App\Models\Penawaran::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            // Line chart - per tanggal dalam satu bulan
            $month = $request->query('month', now()->format('Y-m'));
            list($year, $monthNum) = explode('-', $month);
            
            $dateStats = \App\Models\Penawaran::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get();
        }

        return view('dashboard', compact('topCompanies', 'picStats', 'statusCounts', 'dateStats', 'month', 'canViewCharts'));
    }
}
