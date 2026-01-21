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
                    ->limit(10)
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
            } else {
                // Untuk role lain (supervisor, manager, administrator, direktur)
                // 1. Perusahaan paling sering penawaran
                $topCompanies = \App\Models\Penawaran::select('nama_perusahaan', DB::raw('count(*) as total'))
                    ->groupBy('nama_perusahaan')
                    ->orderByDesc('total')
                    ->limit(10)
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
            }
        }

        return view('dashboard', compact('topCompanies', 'picStats', 'statusCounts', 'dateStats', 'month', 'canViewCharts'));
    }
}
