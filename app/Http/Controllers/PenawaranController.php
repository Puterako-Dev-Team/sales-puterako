<?php

namespace App\Http\Controllers;

use App\Models\Penawaran;
use App\Models\PenawaranDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PenawaranController extends Controller
{
    /**
     * Helper method untuk recalculate grand_total otomatis
     * Formula: Grand Total = (Total Penawaran + Total Jasa) + PPN
     * 
     * @param $penawaranId ID penawaran
     * @param $version Version penawaran
     * @return float grand_total yang sudah dihitung
     */
    private function recalculateGrandTotal($penawaranId, $version)
    {
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
            ->where('version', $version)
            ->first();
        
        if (!$versionRow) {
            return 0;
        }
        
        // Ambil komponen-komponen
        $totalPenawaran = floatval($versionRow->penawaran_total_awal ?? 0);
        $totalJasa = floatval($versionRow->jasa_grand_total ?? 0);
        $ppnPercent = floatval($versionRow->ppn_persen ?? 11);
        $isBestPrice = boolval($versionRow->is_best_price ?? false);
        $bestPrice = floatval($versionRow->best_price ?? 0);
        $isDiskon = boolval($versionRow->is_diskon ?? false);
        $diskon = floatval($versionRow->diskon ?? 0);
        
        // Hitung base amount (gunakan best price jika ada, sebaliknya gunakan penawaran total)
        $baseAmount = $isBestPrice ? $bestPrice : $totalPenawaran;
        
        // Hitung subtotal (penawaran/best price + jasa)
        $subtotal = $baseAmount + $totalJasa;
        
        // Hitung diskon sebagai persen dari subtotal
        $diskonNominal = 0;
        if ($isDiskon && $diskon > 0) {
            $diskonNominal = ($subtotal * $diskon) / 100;
            $subtotal = $subtotal - $diskonNominal;
        }
        
        // Hitung PPN dari subtotal (setelah diskon)
        $ppnNominal = ($subtotal * $ppnPercent) / 100;
        
        // Grand Total = subtotal + PPN
        $grandTotal = $subtotal + $ppnNominal;
        
        // Update grand_total dan ppn_nominal di database
        $versionRow->grand_total = $grandTotal;
        $versionRow->ppn_nominal = $ppnNominal;
        $versionRow->save();
        
        return $grandTotal;
    }
    
    public function index(Request $request)
    {
        // Manager role tidak bisa melihat list penawaran
        $userRole = Auth::user()->role ?? null;
        if ($userRole === 'manager') {
            abort(403, 'Unauthorized access. Manager tidak memiliki akses ke halaman ini.');
        }

        $query = \App\Models\Penawaran::with('user'); // Eager load user

        // Staff dari departemen Sales hanya bisa melihat penawaran mereka sendiri
        if ($userRole === 'staff' && (Auth::user()->departemen && Auth::user()->departemen->value === 'Sales')) {
            $query->where('user_id', Auth::id());
        }

        // Filter berdasarkan tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        // Filter berdasarkan no penawaran
        if ($request->filled('no_penawaran')) {
            $query->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
        }

        // Filter berdasarkan nama perusahaan
        if ($request->filled('nama_perusahaan')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }

        // Filter berdasarkan status (termasuk deleted untuk admin)
        if ($request->filled('status')) {
            if ($request->status === 'deleted' && $userRole === 'administrator') {
                // Untuk admin, tampilkan hanya yang soft deleted
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status);
            }
        }

        // PERBAIKI: Filter berdasarkan PIC Admin dari tabel users
        if ($request->filled('pic_admin')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'asc');

        // Validasi kolom yang bisa di-sort
        $allowedSorts = ['id_penawaran', 'created_at', 'no_penawaran', 'perihal', 'nama_perusahaan', 'pic_perusahaan', 'status'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'id_penawaran';
        }

        $query->orderBy($sortColumn, $sortDirection);

        // Ambil data dengan pagination
        $penawarans = $query->paginate(10)->appends($request->query());

        // Untuk info hasil filter
        $totalRecords = \App\Models\Penawaran::count();

        // PERBAIKI: Ambil daftar PIC Admin dari tabel users yang punya penawaran
        $picAdmins = \App\Models\User::whereHas('penawarans')
            ->distinct('name')
            ->orderBy('name')
            ->pluck('name');

        $mitras = \App\Models\Mitra::orderBy('nama_mitra')
            ->orderBy('kota')
            ->get(['id_mitra', 'nama_mitra', 'kota', 'provinsi'])
            ->map(function ($mitra) {
                return [
                    'id' => $mitra->id_mitra,
                    'nama' => $mitra->nama_mitra,
                    'kota' => $mitra->kota,
                    'provinsi' => $mitra->provinsi,
                    'display' => $mitra->nama_mitra . ' (' . $mitra->kota . ')'
                ];
            });

        return view('penawaran.list', compact('penawarans', 'totalRecords', 'picAdmins', 'mitras'));
    }

    public function filter(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('penawaran.list');
        }

        $query = \App\Models\Penawaran::with('user'); // Eager load user

        // Staff role hanya bisa melihat penawaran mereka sendiri
        $userRole = Auth::user()->role ?? null;
        if ($userRole === 'staff') {
            $query->where('user_id', Auth::id());
        }

        // Apply filters
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('no_penawaran')) {
            $query->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
        }

        if ($request->filled('nama_perusahaan')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }

        // Filter berdasarkan status (termasuk deleted untuk admin)
        if ($request->filled('status')) {
            if ($request->status === 'deleted' && $userRole === 'administrator') {
                // Untuk admin, tampilkan hanya yang soft deleted
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->status);
            }
        }

        // PERBAIKI: Filter PIC Admin berdasarkan user name
        if ($request->filled('pic_admin')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'asc');

        // Validasi kolom yang bisa di-sort
        $allowedSorts = ['id_penawaran', 'created_at', 'no_penawaran', 'perihal', 'nama_perusahaan', 'pic_perusahaan', 'status'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'id_penawaran';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $penawarans = $query->paginate(10)->appends($request->query());
        $totalRecords = \App\Models\Penawaran::count();

        $table = view('penawaran.table-content', compact('penawarans'))->render();

        // Generate pagination links
        $pagination = view('components.paginator', ['paginator' => $penawarans])->render();
        $info = '';
        if ($request->hasAny(['tanggal_dari', 'no_penawaran', 'nama_perusahaan', 'status', 'pic_admin'])) {
            $activeFilters = [];
            if ($request->tanggal_dari)
                $activeFilters[] = 'Tanggal';
            if ($request->no_penawaran)
                $activeFilters[] = 'No Penawaran';
            if ($request->nama_perusahaan)
                $activeFilters[] = 'Perusahaan';
            if ($request->status)
                $activeFilters[] = 'Status';
            if ($request->pic_admin)
                $activeFilters[] = 'PIC';

            $info = view('penawaran.filter-info', [
                'count' => $penawarans->count(),
                'total' => $totalRecords,
                'filters' => implode(', ', $activeFilters),
                'currentPage' => $penawarans->currentPage(),
                'lastPage' => $penawarans->lastPage(),
                'from' => $penawarans->firstItem(),
                'to' => $penawarans->lastItem()
            ])->render();
        }

        $mitras = \App\Models\Mitra::orderBy('nama_mitra')
            ->orderBy('kota')
            ->get(['id_mitra', 'nama_mitra', 'kota', 'provinsi'])
            ->map(function ($mitra) {
                return [
                    'id' => $mitra->id_mitra,
                    'nama' => $mitra->nama_mitra,
                    'kota' => $mitra->kota,
                    'provinsi' => $mitra->provinsi,
                    'display' => $mitra->nama_mitra . ' (' . $mitra->kota . ')'
                ];
            });

        return response()->json([
            'table' => $table,
            'info' => $info,
            'pagination' => $pagination,
            'mitras' => $mitras
        ]);
    }

    public function store(Request $request)
    {
        // Manager role tidak bisa membuat penawaran baru
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat membuat penawaran baru.'], 403);
        }

        // Presales department tidak bisa membuat penawaran baru
        $user = Auth::user();
        if ($user->departemen && $user->departemen->value === 'Presales') {
            return response()->json(['error' => 'Unauthorized. Departemen Presales tidak dapat membuat penawaran.'], 403);
        }


        $data = $request->all();

        // Validasi lokasi pengerjaan
        $lokasi = $request->input('lokasi_pengerjaan', 'SBY');
        if (!in_array($lokasi, ['SBY', 'JKT'])) {
            $lokasi = 'SBY';
        }
        $data['lokasi_pengerjaan'] = $lokasi;

        // Optional validation for tipe
        $tipe = $request->input('tipe');
        if (!in_array($tipe, ['soc', 'barang'])) {
            $tipe = null;
        }
        $data['tipe'] = $tipe;

        // Handle template type
        $templateType = $request->input('template_type', 'template_puterako');
        $data['template_type'] = $templateType;

        // Handle BoQ file upload if template is template_boq
        if ($templateType === 'template_boq' && $request->hasFile('boq_file')) {
            try {
                $result = uploadFile($request->file('boq_file'), 'boq-files');
                if ($result['success']) {
                    $data['boq_file_path'] = $result['data']['path'];
                } else {
                    return response()->json([
                        'success' => false,
                        'notify' => [
                            'type' => 'error',
                            'title' => 'Error',
                            'message' => 'Gagal upload file: ' . $result['message']
                        ]
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'notify' => [
                        'type' => 'error',
                        'title' => 'Error',
                        'message' => 'Error saat upload: ' . $e->getMessage()
                    ]
                ], 422);
            }
        }

        // Auto-set user_id dari Auth user
        $data['user_id'] = Auth::id();

        // Generate no_penawaran dari lokasi, user, urut, bulan, tahun
        $userId = Auth::id();
        $nextSequence = $this->getMaxSequenceForUser($userId) + 1;
        $paddedSequence = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
        $bulanRomawi = [1=>'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $bulan = $bulanRomawi[intval(date('n'))];
        $tahun = date('Y');
        $data['no_penawaran'] = "PIB/SS-{$lokasi}/{$userId}-{$paddedSequence}/{$bulan}/{$tahun}";

        \App\Models\Penawaran::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Penawaran berhasil ditambahkan'
                ]
            ]);
        }
        return redirect()->route('penawaran.list');
    }

    public function edit($id)
    {
        // Manager role tidak bisa edit penawaran
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengedit penawaran.'], 403);
        }

        $penawaran = Penawaran::findOrFail($id);
        return response()->json($penawaran);
    }

    public function update(Request $request, $id)
    {
        // Manager role tidak bisa update penawaran
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengupdate penawaran.'], 403);
        }

        $penawaran = Penawaran::findOrFail($id);
        $data = $request->validate([
            'perihal' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'pic_perusahaan' => 'nullable|string|max:255',
            'tipe' => 'nullable|in:soc,barang',
            'template_type' => 'nullable|in:template_puterako,template_boq',
            'no_penawaran_edit' => 'nullable|string|max:255|regex:/^PIB\/SS-SBY\/JK\/\d+-\d+\/[IVX]+\/\d{4}$/',
        ]);

        // Handle no_penawaran edit for administrator only
        if (Auth::user()->role === 'administrator' && $request->filled('no_penawaran_edit')) {
            $newNoPenawaran = $request->input('no_penawaran_edit');
            
            // Check if new no_penawaran is already used by another penawaran
            $existingPenawaran = Penawaran::where('no_penawaran', $newNoPenawaran)
                ->where('id_penawaran', '!=', $id)
                ->withTrashed()
                ->first();
            
            if ($existingPenawaran) {
                return response()->json([
                    'success' => false,
                    'notify' => [
                        'type' => 'error',
                        'title' => 'Error',
                        'message' => 'No Penawaran sudah digunakan oleh penawaran lain'
                    ]
                ], 422);
            }
            
            $data['no_penawaran'] = $newNoPenawaran;
        }
        
        // Remove the edit field from data since we've already processed it
        unset($data['no_penawaran_edit']);

        // Handle template type
        if ($request->has('template_type')) {
            $data['template_type'] = $request->input('template_type');
        }

        // Handle BoQ file upload if template is template_boq
        if ($request->input('template_type') === 'template_boq' && $request->hasFile('boq_file')) {
            try {
                // Delete old file if exists
                if ($penawaran->boq_file_path) {
                    deleteFile($penawaran->boq_file_path);
                }

                $result = uploadFile($request->file('boq_file'), 'boq-files');
                if ($result['success']) {
                    $data['boq_file_path'] = $result['data']['path'];
                } else {
                    return response()->json([
                        'success' => false,
                        'notify' => [
                            'type' => 'error',
                            'title' => 'Error',
                            'message' => 'Gagal upload file: ' . $result['message']
                        ]
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'notify' => [
                        'type' => 'error',
                        'title' => 'Error',
                        'message' => 'Error saat upload: ' . $e->getMessage()
                    ]
                ], 422);
            }
        } elseif ($request->input('template_type') === 'template_puterako') {
            // Clear boq_file_path if switching to template_puterako
            if ($penawaran->boq_file_path) {
                deleteFile($penawaran->boq_file_path);
            }
            $data['boq_file_path'] = null;
        }

        $penawaran->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Updated',
                    'message' => 'Penawaran diperbarui'
                ]
            ]);
        }
        return back()->with('success', 'Penawaran diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        // Manager role tidak bisa delete penawaran
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menghapus penawaran.'], 403);
        }

        $penawaran = Penawaran::findOrFail($id);
        $penawaran->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Deleted',
                    'message' => 'Penawaran dihapus (soft)'
                ]
            ]);
        }
        return back()->with('success', 'Penawaran dihapus');
    }

    public function restore(Request $request, $id)
    {
        // Hanya administrator yang bisa restore penawaran
        if (Auth::user()->role !== 'administrator') {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized. Hanya administrator yang dapat memulihkan penawaran.'], 403);
            }
            return back()->with('error', 'Unauthorized. Hanya administrator yang dapat memulihkan penawaran.');
        }

        $penawaran = Penawaran::onlyTrashed()->findOrFail($id);
        $penawaran->restore();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Penawaran berhasil dipulihkan'
                ]
            ]);
        }
        return back()->with('success', 'Penawaran dipulihkan');
    }

    public function forceDelete(Request $request, $id)
    {
        // Hanya administrator yang bisa hard delete penawaran
        if (Auth::user()->role !== 'administrator') {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized. Hanya administrator yang dapat menghapus permanen.'], 403);
            }
            return back()->with('error', 'Unauthorized. Hanya administrator yang dapat menghapus permanen.');
        }

        $penawaran = Penawaran::onlyTrashed()->findOrFail($id);
        
        $penawaran->forceDelete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Penawaran dihapus secara permanen'
                ]
            ]);
        }
        return back()->with('success', 'Penawaran dihapus permanen');
    }

    public function followUp($id)
    {
        $penawaran = Penawaran::with('user')->findOrFail($id);

        // Ambil follow ups dari database
        $followUps = DB::table('follow_ups')
            ->where('penawaran_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('penawaran.follow-up', compact('penawaran', 'followUps'));
    }

    public function storeFollowUp(Request $request, $id)
    {
        // Manager role tidak bisa menambah follow up
        if (Auth::user()->role === 'manager') {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized. Manager tidak dapat menambah follow up.'], 403);
            }
            return back()->with('error', 'Unauthorized. Manager tidak dapat menambah follow up.');
        }

        $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'hasil_progress' => 'nullable|string',
            'jenis' => 'required|in:whatsapp,email,telepon,kunjungan',
            'pic_perusahaan' => 'nullable|string|max:255',
            'status' => 'required|in:progress,deal,closed'
        ]);

        DB::table('follow_ups')->insert([
            'penawaran_id' => $id,
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
            'hasil_progress' => $request->hasil_progress,
            'jenis' => $request->jenis,
            'pic_perusahaan' => $request->pic_perusahaan,
            'status' => $request->status,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // If follow-up status is "deal", update the related penawaran status to "po"
        if ($request->status === 'deal') {
            Penawaran::where('id_penawaran', $id)->update(['status' => 'po']);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Follow up berhasil ditambahkan'
                ]
            ]);
        }

        return back()->with('success', 'Follow up berhasil ditambahkan');
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

        $satuans = \App\Models\Satuan::orderBy('nama')->get();

        // Staff role hanya bisa melihat penawaran mereka sendiri
        $userRole = Auth::user()->role ?? null;
        if ($userRole === 'staff' && $penawaran && $penawaran->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this penawaran');
        }

        $hasVersions = \App\Models\PenawaranVersion::where('penawaran_id', $id)->exists();
        if (!$hasVersions) {
            \App\Models\PenawaranVersion::create([
                'penawaran_id' => $id,
                'version' => 0,
                'status' => 'draft'
            ]);
        }

        // PERBAIKAN: Handle version 0 explicitly
        if ($version === null || $version === '') {
            // Jika tidak ada version di URL, ambil version tertinggi
            $activeVersion = \App\Models\PenawaranVersion::where('penawaran_id', $id)->max('version');
            // Jika masih null, default ke 0
            $activeVersion = $activeVersion ?? 0;
        } else {
            // Gunakan version dari URL (bisa 0, 1, 2, dst)
            $activeVersion = intval($version);
        }

        // Ambil version row berdasarkan activeVersion
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)
            ->where('version', $activeVersion)
            ->first();

        if (!$versionRow) {
            $versionRow = \App\Models\PenawaranVersion::create([
                'penawaran_id' => $id,
                'version' => 0,
                'status' => 'draft'
            ]);
            $activeVersion = 0;
        }

        $activeVersionId = $versionRow->id;

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
        $isDiskon = $versionRow->is_diskon ?? false;
        $diskon = $versionRow->diskon ?? 0;
        $baseAmount = ($isBest && $bestPrice > 0) ? $bestPrice : ($totalPenawaran + $grandTotalJasa);
        
        // Hitung diskon sebagai persen dari baseAmount
        $diskonNominal = 0;
        if ($isDiskon && $diskon > 0) {
            $diskonNominal = ($baseAmount * $diskon) / 100;
            $baseAmount = $baseAmount - $diskonNominal;
        }

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
                        'tipe' => $d->tipe_name ?? $d->tipe, // Use tipe_name if available, fallback to tipe
                        'deskripsi' => $d->deskripsi,
                        'qty' => $d->qty,
                        'satuan' => $d->satuan,
                        'harga_satuan' => $d->harga_satuan,
                        'harga_total' => $d->harga_total,
                        'hpp' => $d->hpp,
                        'is_mitra' => $d->is_mitra,
                        'is_judul' => $d->is_judul,
                        'color_code' => $d->color_code,
                        'added_cost' => $d->added_cost,
                        'delivery_time' => $d->delivery_time,
                        'profit' => $d->profit,
                        'comments' => $d->comments,
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        // Ambil status approval export PDF untuk slider verification
        $approval = \App\Models\ExportApprovalRequest::where('penawaran_id', $penawaran->id_penawaran ?? $id)
            ->where('version_id', $activeVersionId)
            ->orderBy('created_at', 'desc')
            ->first();

        return view('penawaran.detail', compact(
            'penawaran',
            'sections',
            'profit',
            'jasaDetails',
            'jasa',
            'activeVersion',
            'versionRow',
            'activeVersionId',
            'totalPenawaran',
            'grandTotalJasa',
            'grandTotal',
            'ppnPersen',
            'ppnNominal',
            'grandTotalWithPpn',
            'isBest',
            'bestPrice',
            'isDiskon',
            'diskon',
            'diskonNominal',
            'satuans',
            'approval'
        ));
    }

    public function save(Request $request)
    {
        // Manager role tidak bisa save/edit penawaran
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan perubahan penawaran.'], 403);
        }

        $data = $request->all();
        Log::debug('PenawaranController::save payload', $data);

        $penawaranId = $data['penawaran_id'] ?? null;
        $sections = $data['sections'] ?? [];
        $ppnPersen = $data['ppn_persen'] ?? 11; // Default 11%
        $version = $data['version'] ?? 0;

        if (!$penawaranId) {
            Log::warning('PenawaranController::save missing penawaran_id', $data);
            return response()->json(['error' => 'Penawaran ID tidak ditemukan'], 400);
        }

        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)->where('version', $version)->first();
        if (!$versionRow) {
            $versionRow = \App\Models\PenawaranVersion::create([
                'penawaran_id' => $penawaranId,
                'version' => 0,
                'status' => 'draft'
            ]);
        }
        $version_id = $versionRow->id;

        try {
            // Sederhanakan: selalu rebuild detail untuk versi ini
            DB::beginTransaction();

            \App\Models\PenawaranDetail::where('id_penawaran', $penawaranId)
                ->where('version_id', $version_id)
                ->delete();

            $totalKeseluruhan = 0;

            foreach ($sections as $section) {
                $area = (string) ($section['area'] ?? '');
                $namaSection = (string) ($section['nama_section'] ?? '');

                foreach ($section['data'] as $row) {
                    $hargaTotal = floatval($row['harga_total'] ?? 0);
                    $totalKeseluruhan += $hargaTotal;

                    $values = [
                        'tipe' => $row['tipe'] ?? null,
                        'tipe_name' => $row['tipe'] ?? null,
                        'deskripsi' => $row['deskripsi'] ?? null,
                        'qty' => $row['qty'] ?? null,
                        'satuan' => $row['satuan'] ?? null,
                        'harga_satuan' => $row['harga_satuan'] ?? null,
                        'harga_total' => $hargaTotal,
                        'hpp' => $row['hpp'] ?? null,
                        'profit' => $row['profit'] ?? 0,
                        'nama_section' => $namaSection,
                        'area' => $area,
                        'is_mitra' => isset($row['is_mitra']) ? (int) $row['is_mitra'] : 0,
                        'is_judul' => isset($row['is_judul']) ? (int) $row['is_judul'] : 0,
                        'color_code' => isset($row['color_code']) ? (int) $row['color_code'] : 1,
                        'added_cost' => $row['added_cost'] ?? 0,
                        'delivery_time' => $row['delivery_time'] ?? null,
                        'comments' => isset($row['comments']) ? $row['comments'] : null,
                        'version_id' => $version_id, // pastikan selalu isi version_id
                    ];

                    // Cari tipe_id berdasarkan tipe_name
                    if (!empty($row['tipe'])) {
                        $tipeRecord = \App\Models\Tipe::where('nama', $row['tipe'])->first();
                        if ($tipeRecord) {
                            $values['tipe_id'] = $tipeRecord->id;
                        }
                    }

                    $createAttrs = array_merge($values, [
                        'id_penawaran' => $penawaranId,
                        'no' => $row['no'] ?? null,
                    ]);

                    \App\Models\PenawaranDetail::create($createAttrs);
                }
            }

            // Hitung total awal penawaran
            $versionRow->penawaran_total_awal = $totalKeseluruhan;

            $isBest = !empty($data['is_best_price']) ? 1 : 0;
            $bestPrice = isset($data['best_price']) ? floatval($data['best_price']) : 0;

            $versionRow->ppn_persen = $ppnPersen;
            $versionRow->is_best_price = $isBest;
            $versionRow->best_price = $bestPrice;
            $versionRow->save();
            
            // OTOMATIS HITUNG & UPDATE GRAND_TOTAL dengan semua komponen
            $grandTotal = $this->recalculateGrandTotal($penawaranId, $version);

            // Ambil data terbaru untuk response (termasuk ppn_nominal dan jasa)
            $versionRowUpdated = \App\Models\PenawaranVersion::where('penawaran_id', $penawaranId)
                ->where('version', $version)
                ->first();
            
            $totalJasa = floatval($versionRowUpdated->jasa_grand_total ?? 0);
            $ppnNominal = floatval($versionRowUpdated->ppn_nominal ?? 0);

            DB::commit();

            // Log activity for editing penawaran
            $penawaran = Penawaran::find($penawaranId);
            if ($penawaran) {
                activity()
                    ->performedOn($penawaran)
                    ->causedBy(Auth::user())
                    ->withProperties(['version' => $version])
                    ->log('Edited penawaran');
            }

            Log::debug('Penawaran saved', ['id_penawaran' => $penawaranId, 'total' => $totalKeseluruhan, 'grand_total' => $grandTotal]);

            return response()->json([
                'success' => true,
                'total' => $totalKeseluruhan,
                'grand_total' => $grandTotal,
                'ppn_nominal' => $ppnNominal,
                'total_jasa' => $totalJasa,
                'message' => 'Penawaran berhasil disimpan. Grand total telah otomatis terupdate!'
            ]);
        } catch (\Throwable $e) {
            // Pastikan transaksi dibatalkan jika terjadi error
            try {
                DB::rollBack();
            } catch (\Throwable $rollbackException) {
                Log::error('PenawaranController::save rollback error: ' . $rollbackException->getMessage());
            }

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
                        'is_judul' => $d->is_judul,
                        'color_code' => $d->color_code ?? 1,
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
        $version = $request->query('version');

        $penawaran = \App\Models\Penawaran::findOrFail($id);

        // Ambil versi aktif
        $activeVersion = $version ?? \App\Models\PenawaranVersion::where('penawaran_id', $id)->max('version');
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)->where('version', $activeVersion)->first();

        // Tambahkan pengecekan jika versionRow null
        if (!$versionRow) {
            return redirect()->route('penawaran.list')->with('error', 'Versi penawaran tidak ditemukan');
        }

        // Ambil detail penawaran sesuai versi
        $details = PenawaranDetail::where('version_id', $versionRow->id)
            ->orderBy('id_penawaran_detail', 'asc')
            ->get();

        // Hitung total penawaran
        $totalPenawaran = $details->sum('harga_total');

        // Ambil jasa sesuai versi
        $jasa = \App\Models\Jasa::where('version_id', $versionRow->id)->first();
        $jasaDetails = \App\Models\JasaDetail::where('version_id', $versionRow->id)->get();
        $grandTotalJasa = $jasa ? $jasa->grand_total : 0;

        // Hitung grand total
        $grandTotal = $totalPenawaran + $grandTotalJasa;

        // Ambil field dinamis dari versionRow untuk kalkulasi
        $ppnPersen = $versionRow->ppn_persen ?? 11;
        $isBest = $versionRow->is_best_price ?? false;
        $bestPrice = $versionRow->best_price ?? 0;
        $baseAmount = ($isBest && $bestPrice > 0) ? $bestPrice : $grandTotal;

        $ppnNominal = ($baseAmount * $ppnPersen) / 100;
        $grandTotalWithPpn = $baseAmount + $ppnNominal;

        // Grouping sections untuk PDF - group by nama_section kemudian by area
        $groupedSections = [];
        foreach ($details as $row) {
            $section = $row->nama_section ?: 'Umum';
            $area = $row->area ?: '-';
            $groupedSections[$section][$area][] = $row;
        }

        // Data yang akan dikirim ke PDF
        $pdfData = compact(
            'penawaran',
            'groupedSections',
            'jasa',
            'jasaDetails',
            'versionRow',
            'details',
            'activeVersion',
            'totalPenawaran',
            'grandTotalJasa',
            'grandTotal',
            'ppnPersen',
            'ppnNominal',
            'baseAmount',
            'grandTotalWithPpn',
            'isBest',
            'bestPrice'
        );

        // Generate PDF
        $pdf = Pdf::loadView('penawaran.pdf', $pdfData);

        // Set paper size dan orientasi
        $pdf->setPaper('A4', 'portrait');

        // Generate filename dengan format yang aman untuk file system
        $safeNoPenawaran = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $penawaran->no_penawaran);

        // Tambahkan suffix revisi ke filename jika bukan versi 1
        $filename = 'Penawaran-' . $safeNoPenawaran;
        if ($activeVersion > 1) {
            $filename .= '-Rev' . $activeVersion;
        }
        $filename .= '.pdf';

        // Log activity
        activity()
            ->performedOn($penawaran)
            ->causedBy(Auth::user())
            ->withProperties(['version' => $activeVersion])
            ->log('Exported PDF');

        // Download PDF
        return $pdf->download($filename);
    }

    public function showLog(Request $request)
    {
        $id = $request->query('id');
        $penawaran = Penawaran::findOrFail($id);
        
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Penawaran::class)
            ->where('subject_id', $id)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'activities' => $activities->map(function($activity) {
                return [
                    'description' => $activity->description,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'System',
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->format('d/m/Y H:i:s'),
                    'created_at_formatted' => $activity->created_at->translatedFormat('l, d F Y H:i')
                ];
            })
        ]);
    }

    public function countUnreadActivities(Request $request)
    {
        $id = $request->query('id');
        $userId = Auth::id();
        
        // Get last read timestamp for this user and penawaran
        $lastRead = DB::table('activity_reads')
            ->where('user_id', $userId)
            ->where('penawaran_id', $id)
            ->value('last_read_at');
        
        // Count activities after last read
        $query = \Spatie\Activitylog\Models\Activity::where('subject_type', Penawaran::class)
            ->where('subject_id', $id);
        
        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }
        
        $count = $query->count();
        
        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    public function markActivitiesAsRead(Request $request)
    {
        $id = $request->input('id');
        $userId = Auth::id();
        
        DB::table('activity_reads')->updateOrInsert(
            ['user_id' => $userId, 'penawaran_id' => $id],
            ['last_read_at' => now(), 'updated_at' => now()]
        );
        
        return response()->json([
            'success' => true
        ]);
    }

    public function saveNotes(Request $request, $id)
    {
        // Manager role tidak bisa save notes
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan catatan.'], 403);
        }

        $request->validate([
            'note' => 'nullable|string',
            'version' => 'required|integer',
            'grand_total_calculated' => 'nullable|numeric'
        ]);

        $version = $request->input('version');
        $grandTotalCalculated = $request->input('grand_total_calculated', 0);

        Log::info("SaveNotes Request Data", [
            'penawaran_id' => $id,
            'version' => $version,
            'grand_total_calculated_raw' => $request->input('grand_total_calculated'),
            'grand_total_calculated_parsed' => $grandTotalCalculated,
            'all_request_data' => $request->all()
        ]);

        // Cari penawaran version berdasarkan penawaran_id dan version
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)
            ->where('version', $version)
            ->first();

        if (!$versionRow) {
            return redirect()->back()->with('error', 'Versi penawaran tidak ditemukan.');
        }

        // Simpan grand_total yang dikirim dari frontend (update jika nilai dikirim dan bukan string '0')
        if ($grandTotalCalculated !== null && $grandTotalCalculated !== 0 && $grandTotalCalculated !== '0') {
            $versionRow->grand_total = (int) $grandTotalCalculated;
            Log::info("SaveNotes: Updated grand_total to {$grandTotalCalculated} for penawaran_id={$id}, version={$version}");
        } else {
            Log::info("SaveNotes: grand_total NOT updated. Value: {$grandTotalCalculated}, is_null: " . ($grandTotalCalculated === null ? 'true' : 'false'));
        }

        // Simpan note ke penawaran_versions
        $versionRow->notes = $request->note;
        $versionRow->save();

        return redirect()->back()->with('success', 'Notes berhasil disimpan ke versi penawaran.');
    }

    public function saveBestPrice(Request $request, $id)
    {
        // Manager role tidak bisa save best price
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan harga terbaik.'], 403);
        }

        $version = $request->input('version', 1);
        $isBest = $request->has('is_best_price') ? 1 : 0;
        $bestPrice = $request->input('best_price', 0);

        // Cari versi aktif
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)
            ->where('version', $version)
            ->first();

        if ($versionRow) {
            $versionRow->is_best_price = $isBest;
            $versionRow->best_price = $bestPrice;
            $versionRow->save();
            return redirect()->back()->with('success', 'Best Price berhasil disimpan ke versi penawaran.');
        }

        return redirect()->back()->with('error', 'Data versi penawaran tidak ditemukan.');
    }

    public function saveDiskon(Request $request, $id)
    {
        // Manager role tidak bisa save diskon
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan diskon.'], 403);
        }

        $version = $request->input('version', 1);
        $isDiskon = $request->has('is_diskon') ? 1 : 0;
        $diskon = $request->input('diskon', 0);
        
        // Jika diskon 0, maka set is_diskon ke 0 juga
        if (floatval($diskon) == 0) {
            $isDiskon = 0;
        }

        // Cari versi aktif
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)
            ->where('version', $version)
            ->first();

        if ($versionRow) {
            $versionRow->is_diskon = $isDiskon;
            $versionRow->diskon = $diskon;
            $versionRow->save();
            
            // Recalculate grand total setelah update diskon
            $this->recalculateGrandTotal($id, $version);
            
            return redirect()->back()->with('success', 'Diskon berhasil disimpan.');
        }

        return redirect()->back()->with('error', 'Data versi penawaran tidak ditemukan.');
    }

    public function createRevision($id)
    {
        // Manager role tidak bisa membuat revisi
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat membuat revisi.'], 403);
        }

        $penawaran = \App\Models\Penawaran::findOrFail($id);

        // Ambil versi terakhir
        $lastVersion = \App\Models\PenawaranVersion::where('penawaran_id', $id)->max('version');

        // Jika belum ada versi sama sekali, buat versi 0 (bukan 1)
        if ($lastVersion === null) {
            $lastVersion = -1; // Set ke -1 agar newVersion jadi 0
        }

        $newVersion = $lastVersion + 1;

        // Copy versi sebelumnya jika ada
        $oldVersion = null;
        if ($lastVersion >= 0) { // Ubah kondisi dari > 0 ke >= 0
            $oldVersion = \App\Models\PenawaranVersion::where('penawaran_id', $id)
                ->where('version', $lastVersion)
                ->first();
        }

        // Buat versi baru
        $newVersionRow = \App\Models\PenawaranVersion::create([
            'penawaran_id' => $id,
            'version' => $newVersion,
            'notes' => $oldVersion ? ($oldVersion->notes ?? null) : null,
            'status' => 'draft',
            'jasa_ringkasan' => $oldVersion ? ($oldVersion->jasa_ringkasan ?? null) : null,
            'jasa_profit_percent' => $oldVersion ? ($oldVersion->jasa_profit_percent ?? 0) : 0,
            'jasa_profit_value' => $oldVersion ? ($oldVersion->jasa_profit_value ?? 0) : 0,
            'jasa_pph_percent' => $oldVersion ? ($oldVersion->jasa_pph_percent ?? 0) : 0,
            'jasa_pph_value' => $oldVersion ? ($oldVersion->jasa_pph_value ?? 0) : 0,
            'jasa_bpjsk_percent' => $oldVersion ? ($oldVersion->jasa_bpjsk_percent ?? 0) : 0,
            'jasa_bpjsk_value' => $oldVersion ? ($oldVersion->jasa_bpjsk_value ?? 0) : 0,
            'jasa_grand_total' => $oldVersion ? ($oldVersion->jasa_grand_total ?? 0) : 0,
            'is_best_price' => $oldVersion ? ($oldVersion->is_best_price ?? 0) : 0,
            'best_price' => $oldVersion ? ($oldVersion->best_price ?? 0) : 0,
            'is_diskon' => $oldVersion ? ($oldVersion->is_diskon ?? 0) : 0,
            'diskon' => $oldVersion ? ($oldVersion->diskon ?? 0) : 0,
            'ppn_persen' => $oldVersion ? ($oldVersion->ppn_persen ?? 11) : 11,
        ]);

        // Copy penawaran_detail hanya jika ada versi sebelumnya
        if ($oldVersion && $oldVersion->details) {
            foreach ($oldVersion->details as $detail) {
                \App\Models\PenawaranDetail::create([
                    'version_id' => $newVersionRow->id,
                    'id_penawaran' => $detail->id_penawaran,
                    'area' => $detail->area,
                    'nama_section' => $detail->nama_section,
                    'no' => $detail->no,
                    'tipe' => $detail->tipe,
                    'deskripsi' => $detail->deskripsi,
                    'qty' => $detail->qty,
                    'satuan' => $detail->satuan,
                    'harga_satuan' => $detail->harga_satuan,
                    'harga_total' => $detail->harga_total,
                    'hpp' => $detail->hpp,
                    'is_mitra' => $detail->is_mitra,
                    'is_judul' => $detail->is_judul,
                    'color_code' => $detail->color_code,
                    'added_cost' => $detail->added_cost,
                    'delivery_time' => $detail->delivery_time,
                    'profit' => $detail->profit,
                ]);
            }
        }

        // Copy jasa dan jasa_detail hanya jika ada versi sebelumnya
        if ($oldVersion) {
            $oldJasa = \App\Models\Jasa::where('version_id', $oldVersion->id)->first();
            if ($oldJasa) {
                $newJasa = \App\Models\Jasa::create([
                    'version_id' => $newVersionRow->id,
                    'id_penawaran' => $id,
                    'ringkasan' => $oldJasa->ringkasan,
                    'profit_percent' => $oldJasa->profit_percent,
                    'profit_value' => $oldJasa->profit_value,
                    'pph_percent' => $oldJasa->pph_percent,
                    'pph_value' => $oldJasa->pph_value,
                    'bpjsk_percent' => $oldJasa->bpjsk_percent,
                    'bpjsk_value' => $oldJasa->bpjsk_value,
                    'grand_total' => $oldJasa->grand_total,
                ]);

                // Copy JasaDetail
                $oldJasaDetails = \App\Models\JasaDetail::where('version_id', $oldVersion->id)->get();
                foreach ($oldJasaDetails as $jasaDetail) {
                    \App\Models\JasaDetail::create([
                        'version_id' => $newVersionRow->id,
                        'id_jasa' => $newJasa->id_jasa,
                        'id_penawaran' => $jasaDetail->id_penawaran,
                        'nama_section' => $jasaDetail->nama_section,
                        'no' => $jasaDetail->no,
                        'deskripsi' => $jasaDetail->deskripsi,
                        'vol' => $jasaDetail->vol,
                        'hari' => $jasaDetail->hari,
                        'orang' => $jasaDetail->orang,
                        'unit' => $jasaDetail->unit,
                        'total' => $jasaDetail->total,
                        'pembulatan' => $jasaDetail->pembulatan,
                        'profit' => $jasaDetail->profit,
                    ]);
                }
            }
        }

        // Log activity
        activity()
            ->performedOn($penawaran)
            ->causedBy(Auth::user())
            ->withProperties(['new_version' => $newVersion])
            ->log('Created revision');

        return redirect()->route('penawaran.show', ['id' => $id, 'version' => $newVersion])
            ->with('success', 'Revisi baru berhasil dibuat (Rev ' . $newVersion . ')');
    }

    public function updateStatus(Request $request, $id)
    {
        // Manager role tidak bisa update status
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengupdate status.'], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,success,lost,po',
            'note' => 'nullable|string|max:1000'
        ]);

        $penawaran = \App\Models\Penawaran::findOrFail($id);
        $penawaran->status = $request->status;

        if ($request->note) {
            $penawaran->note = $request->note;
        }

        $penawaran->save();

        $statusLabel = match ($request->status) {
            'success' => 'Selesai',
            'lost' => 'Gagal',
            'draft' => 'Draft',
            'po' => 'Purchase Order',
        };

        return redirect()->back()->with('toast', [
            'type' => $request->status === 'lost' ? 'error' : 'success',
            'message' => "Status penawaran berhasil diupdate menjadi {$statusLabel}"
        ]);
    }

    public function countThisMonth()
    {
        // Kembalikan sequence terakhir (termasuk data yang di-soft delete) untuk user saat ini
        $max = $this->getMaxSequenceForUser(Auth::id());
        return response()->json(['count' => $max]);
    }

    /**
     * Upload supporting document
     */
    public function uploadSupportingDocument(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);

        $request->validate([
            'file' => 'required|file|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = uploadFile($request->file('file'), 'supporting-documents');

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            $doc = \App\Models\PenawaranSupportingDocument::create([
                'id_penawaran' => $penawaran->id_penawaran,
                'file_path' => $result['data']['path'],
                'original_filename' => $result['data']['original_name'],
                'file_type' => $result['data']['extension'],
                'file_size' => $result['data']['size'],
                'uploaded_by' => Auth::user()->name,
                'notes' => $request->input('notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload',
                'document' => $doc
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supporting documents
     */
    public function getSupportingDocuments($id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $documents = $penawaran->supportingDocuments()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'documents' => $documents
        ]);
    }

    /**
     * Delete supporting document
     */
    public function deleteSupportingDocument($id, $docId)
    {
        $penawaran = Penawaran::findOrFail($id);
        $document = \App\Models\PenawaranSupportingDocument::where('id', $docId)
            ->where('id_penawaran', $penawaran->id_penawaran)
            ->firstOrFail();

        try {
            deleteFile($document->file_path);
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download supporting document
     */
    public function downloadSupportingDocument($id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $docId = request('doc_id');
        
        $document = \App\Models\PenawaranSupportingDocument::where('id', $docId)
            ->where('id_penawaran', $penawaran->id_penawaran)
            ->firstOrFail();

        if (!$document->file_path) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($document->file_path);
    }

    /**
     * Ambil sequence maksimum dari format no_penawaran untuk user tertentu.
     * Meng-include data yang sudah di-soft delete agar penomoran tidak reset.
     */
    private function getMaxSequenceForUser(int $userId): int
    {
        $rows = Penawaran::withTrashed()
            ->where('user_id', $userId)
            ->select('no_penawaran')
            ->orderBy('id_penawaran', 'desc')
            ->get();

        $max = 0;
        foreach ($rows as $row) {
            $no = (string) ($row->no_penawaran ?? '');
            // Support format lama dan baru
            if (preg_match('/PIB\/SS-(SBY|JKT)\/\d+-(\d+)\//', $no, $m)) {
                $seq = (int) $m[2];
                if ($seq > $max) {
                    $max = $seq;
                }
            } elseif (preg_match('/PIB\/SS-SBY\/JK\/\d+-(\d+)\//', $no, $m)) {
                $seq = (int) $m[1];
                if ($seq > $max) {
                    $max = $seq;
                }
            }
        }
        return $max;
    }

}
