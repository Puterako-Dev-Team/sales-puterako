<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
            ->get(['name']);

        // 3. Pie chart status
        $statusCounts = \App\Models\Penawaran::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $dateStats = \App\Models\Penawaran::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $start = $request->query('start', now()->subMonth()->format('Y-m-d'));
        $end = $request->query('end', now()->format('Y-m-d'));

        $dateStats = \App\Models\Penawaran::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return view('dashboard', compact('topCompanies', 'picStats', 'statusCounts', 'dateStats'));
    }
}
