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
    public function index(Request $request)
    {
        $query = \App\Models\Penawaran::with('user'); // Eager load user

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

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
        $pagination = $penawarans->links('penawaran.pagination')->render();

        $info = '';
        if ($request->hasAny(['tanggal_dari', 'no_penawaran', 'nama_perusahaan', 'status', 'pic_admin'])) {
            $activeFilters = [];
            if ($request->tanggal_dari) $activeFilters[] = 'Tanggal';
            if ($request->no_penawaran) $activeFilters[] = 'No Penawaran';
            if ($request->nama_perusahaan) $activeFilters[] = 'Perusahaan';
            if ($request->status) $activeFilters[] = 'Status';
            if ($request->pic_admin) $activeFilters[] = 'PIC';

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
        $data = $request->all();

        // TAMBAH: Auto-set user_id dari Auth user
        $data['user_id'] = Auth::id();

        // Generate full no_penawaran dari suffix yang diinput user
        if ($request->has('no_penawaran_suffix')) {
            $userId = Auth::id();

            // Ambil nomor urut terakhir dari database
            $lastPenawaran = \App\Models\Penawaran::orderBy('id_penawaran', 'desc')->first();
            $nextSequence = $lastPenawaran ? ($lastPenawaran->id_penawaran + 1) : 1;

            // Format nomor dengan padding 0 di depan (minimal 3 digit)
            $paddedSequence = str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

            // Format: PIB/SS-SBY/JK/{user_id}-{padded_sequence}/{user_input}
            $data['no_penawaran'] = "PIB/SS-SBY/JK/{$userId}-{$paddedSequence}/{$request->no_penawaran_suffix}";

            // Hapus field suffix karena tidak ada di database
            unset($data['no_penawaran_suffix']);
        }

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
        $penawaran = Penawaran::findOrFail($id);
        return response()->json($penawaran);
    }

    public function update(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $data = $request->validate([
            'perihal'         => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'lokasi'          => 'required|string|max:255',
            'pic_perusahaan'  => 'nullable|string|max:255',
        ]);
        $penawaran->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'notify' => [
                'type' => 'success',
                'title' => 'Updated',
                'message' => 'Penawaran diperbarui'
            ]]);
        }
        return back()->with('success', 'Penawaran diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $penawaran = Penawaran::findOrFail($id);
        $penawaran->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'notify' => [
                'type' => 'success',
                'title' => 'Deleted',
                'message' => 'Penawaran dihapus (soft)'
            ]]);
        }
        return back()->with('success', 'Penawaran dihapus');
    }

    public function restore($id)
    {
        $penawaran = Penawaran::onlyTrashed()->findOrFail($id);
        $penawaran->restore();
        return back()->with('success', 'Penawaran dipulihkan');
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

            // Hitung total awal penawaran
            $versionRow->penawaran_total_awal = $totalKeseluruhan;


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

        // Download PDF
        return $pdf->download($filename);
    }

    public function saveNotes(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string',
            'version' => 'required|integer'
        ]);

        $version = $request->input('version');

        // Cari penawaran version berdasarkan penawaran_id dan version
        $versionRow = \App\Models\PenawaranVersion::where('penawaran_id', $id)
            ->where('version', $version)
            ->first();

        if (!$versionRow) {
            return redirect()->back()->with('error', 'Versi penawaran tidak ditemukan.');
        }

        // Simpan note ke penawaran_versions
        $versionRow->notes = $request->note;
        $versionRow->save();

        return redirect()->back()->with('success', 'Notes berhasil disimpan ke versi penawaran.');
    }

    public function saveBestPrice(Request $request, $id)
    {
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

    public function createRevision($id)
    {
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
            'penawaran_id'        => $id,
            'version'             => $newVersion,
            'notes'               => $oldVersion ? ($oldVersion->notes ?? null) : null,
            'status'              => 'draft',
            'jasa_ringkasan'      => $oldVersion ? ($oldVersion->jasa_ringkasan ?? null) : null,
            'jasa_profit_percent' => $oldVersion ? ($oldVersion->jasa_profit_percent ?? 0) : 0,
            'jasa_profit_value'   => $oldVersion ? ($oldVersion->jasa_profit_value ?? 0) : 0,
            'jasa_pph_percent'    => $oldVersion ? ($oldVersion->jasa_pph_percent ?? 0) : 0,
            'jasa_pph_value'      => $oldVersion ? ($oldVersion->jasa_pph_value ?? 0) : 0,
            'jasa_bpjsk_percent'  => $oldVersion ? ($oldVersion->jasa_bpjsk_percent ?? 0) : 0,
            'jasa_bpjsk_value'    => $oldVersion ? ($oldVersion->jasa_bpjsk_value ?? 0) : 0,
            'jasa_grand_total'    => $oldVersion ? ($oldVersion->jasa_grand_total ?? 0) : 0,
        ]);

        // Copy penawaran_detail hanya jika ada versi sebelumnya
        if ($oldVersion && $oldVersion->details) {
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
        }

        // Copy jasa dan jasa_detail hanya jika ada versi sebelumnya
        if ($oldVersion) {
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

                // Copy JasaDetail
                $oldJasaDetails = \App\Models\JasaDetail::where('version_id', $oldVersion->id)->get();
                foreach ($oldJasaDetails as $jasaDetail) {
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
        }

        return redirect()->route('penawaran.show', ['id' => $id, 'version' => $newVersion])
            ->with('success', 'Revisi baru berhasil dibuat (Rev ' . $newVersion . ')');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,success,lost',
            'note' => 'nullable|string|max:1000'
        ]);

        $penawaran = \App\Models\Penawaran::findOrFail($id);
        $penawaran->status = $request->status;

        if ($request->note) {
            $penawaran->note = $request->note;
        }

        $penawaran->save();

        return redirect()->back()->with('success', 'Status penawaran berhasil diupdate');
    }
}
