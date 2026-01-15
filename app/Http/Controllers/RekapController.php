<?php
namespace App\Http\Controllers;

use App\Models\Rekap;
use App\Models\Penawaran;
use App\Models\RekapKategori;
use App\Models\RekapItem;
use App\Models\Tipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapController extends Controller
{
    // List page
    public function index(Request $request)
    {
        $query = Rekap::with(['user', 'items']);

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->filled('nama_rekap')) {
            $query->where('nama', 'like', '%' . $request->nama_rekap . '%');
        }
        if ($request->filled('no_penawaran')) {
            $query->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
        }
        if ($request->filled('nama_perusahaan')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }
        if ($request->filled('pic_admin')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }

        $rekaps = $query->paginate(10)->appends($request->query());
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        $picAdmins = \App\Models\User::whereHas('rekaps')->distinct('name')->orderBy('name')->pluck('name');

        if ($request->ajax()) {
            return view('rekap.list', compact('rekaps', 'penawarans', 'kategoris', 'picAdmins'))->render();
        }
        return view('rekap.list', compact('rekaps', 'penawarans', 'kategoris', 'picAdmins'));
    }

    // Show detail rekap
    public function show($id)
    {
        $rekap = Rekap::with(['items.kategori', 'user', 'penawaran'])->findOrFail($id);
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();

        // Gabungkan semua nama detail unik
        $detailNames = [];
        foreach ($rekap->items as $item) {
            foreach ($item->detail ?? [] as $d) {
                $detailNames[] = $d['nama_detail'];
            }
        }
        $previewDetails = array_values(array_unique($detailNames));

        // Gabungkan detail dengan nama_detail sama dan jumlahkan
        $previewKategori = [];
        foreach ($rekap->items as $item) {
            $kategoriNama = $item->kategori->nama ?? '-';
            if (!isset($previewKategori[$kategoriNama])) {
                $previewKategori[$kategoriNama] = ['nama' => $kategoriNama, 'items' => []];
            }

            // Gabungkan detail per nama_detail
            $detailMap = [];
            foreach ($item->detail ?? [] as $d) {
                $nama = $d['nama_detail'];
                if (!isset($detailMap[$nama])) {
                    $detailMap[$nama] = [
                        'nama_detail' => $nama,
                        'jumlah' => floatval($d['jumlah']),
                        'keterangan' => $d['keterangan'] ?? '',
                    ];
                } else {
                    $detailMap[$nama]['jumlah'] += floatval($d['jumlah']);
                }
            }

            $previewKategori[$kategoriNama]['items'][] = [
                'nama_item' => $item->nama_item,
                'detail' => array_values($detailMap),
            ];
        }
        $previewKategori = array_values($previewKategori);

        $isEdit = $rekap->exists;

        return view('rekap.detail', compact('rekap', 'penawarans', 'kategoris', 'previewKategori', 'previewDetails', 'isEdit'));
    }

    // Form tambah rekap
    public function create()
    {
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        return view('rekap.detail', compact('penawarans', 'kategoris'));
    }

    // Simpan rekap baru beserta items dan detail JSON
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'penawaran_id' => 'nullable|exists:penawarans,id_penawaran',
            'nama_perusahaan' => 'required|string|max:255',
            
            // Tambahkan validasi keterangan jika perlu
        ]);

        $rekap = Rekap::create([
            'penawaran_id' => $request->penawaran_id ?? null,
            'user_id' => Auth::id(),
            'nama' => $request->nama,
            'no_penawaran' => $request->penawaran_id ? Penawaran::findOrFail($request->penawaran_id)->no_penawaran : ($request->no_penawaran ?? null),
            'nama_perusahaan' => $request->nama_perusahaan,
        ]);

        return redirect()->route('rekap.list')->with('success', 'Rekap berhasil ditambahkan');
    }

    public function addItem(Request $request, $rekap_id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.nama_item' => 'required|string|max:255',
            'items.*.detail' => 'required|array|min:1',
            'items.*.detail.*.nama_detail' => 'required|string|max:255',
            'items.*.detail.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        foreach ($request->items as $item) {
            // Cari atau buat tipe berdasarkan nama_item
            $tipe = Tipe::firstOrCreate(
                ['nama' => $item['nama_item']],
                ['nama' => $item['nama_item']]
            );

            RekapItem::create([
                'rekap_id' => $rekap_id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $tipe->id,
                'nama_item' => $item['nama_item'],
                'detail' => $item['detail'], // otomatis array->json via casts
            ]);
        }

        return redirect()->route('rekap.show', $rekap_id)->with('success', 'Item berhasil ditambahkan');
    }

    // Form edit rekap
    public function edit($id)
    {
        $rekap = Rekap::with('items')->findOrFail($id);
        
        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'id' => $rekap->id,
                'nama' => $rekap->nama,
                'penawaran_id' => $rekap->penawaran_id,
                'nama_perusahaan' => $rekap->nama_perusahaan,
                'no_penawaran' => $rekap->no_penawaran,
                'items' => $rekap->items
            ]);
        }
        
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        return view('rekap.detail', compact('rekap', 'penawarans', 'kategoris'));
    }

    // Update rekap beserta items dan detail JSON
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'penawaran_id' => 'required|exists:penawarans,id_penawaran',
            'nama_perusahaan' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.nama_item' => 'required|string|max:255',
            'items.*.detail' => 'required|array|min:1',
            'items.*.detail.*.nama_detail' => 'required|string|max:255',
            'items.*.detail.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        $rekap = Rekap::findOrFail($id);
        $penawaran = Penawaran::findOrFail($request->penawaran_id);

        $rekap->update([
            'penawaran_id' => $penawaran->id_penawaran,
            'nama' => $request->nama,
            'no_penawaran' => $penawaran->no_penawaran,
            'nama_perusahaan' => $request->nama_perusahaan,
        ]);

        // Hapus semua item lama, lalu insert ulang
        $rekap->items()->delete();
        foreach ($request->items as $item) {
            // Cari atau buat tipe berdasarkan nama_item
            $tipe = Tipe::firstOrCreate(
                ['nama' => $item['nama_item']],
                ['nama' => $item['nama_item']]
            );

            RekapItem::create([
                'rekap_id' => $rekap->id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $tipe->id,
                'nama_item' => $item['nama_item'],
                'detail' => $item['detail'],
            ]);
        }

        return redirect()->route('rekap.show', $rekap->id)->with('success', 'Rekap berhasil diupdate');
    }

    public function updateItems(Request $request, $rekap_id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.nama_item' => 'required|string|max:255',
            'items.*.detail' => 'required|array|min:1',
            'items.*.detail.*.nama_detail' => 'required|string|max:255',
            'items.*.detail.*.jumlah' => 'required|numeric|min:0.01',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);

        // Hapus semua item lama
        $rekap->items()->delete();

        // Insert ulang item dari form
        foreach ($request->items as $item) {
            // Cari atau buat tipe berdasarkan nama_item
            $tipe = Tipe::firstOrCreate(
                ['nama' => $item['nama_item']],
                ['nama' => $item['nama_item']]
            );

            RekapItem::create([
                'rekap_id' => $rekap_id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $tipe->id,
                'nama_item' => $item['nama_item'],
                'detail' => $item['detail'],
            ]);
        }

        return redirect()->route('rekap.show', $rekap_id)->with('success', 'Item berhasil diupdate');
    }

    // Hapus rekap (soft delete)
    public function destroy($id)
    {
        $rekap = Rekap::findOrFail($id);
        // Soft delete (hanya tandai deleted_at)
        $rekap->delete();
        
        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Rekap berhasil dihapus (data dapat dipulihkan)'
            ]);
        }
        
        return redirect()->route('rekap.list')->with('success', 'Rekap berhasil dihapus');
    }

    // API endpoint untuk mendapatkan daftar nama tipe unik dari tabel tipes
    public function getItemNames(Request $request)
    {
        $query = $request->get('q', '');
        
        $tipes = Tipe::query();
        
        if (!empty($query)) {
            $tipes->where('nama', 'like', "%{$query}%");
        }
        
        $tipes = $tipes->select('nama')
            ->distinct()
            ->orderBy('nama')
            ->limit(20)
            ->get()
            ->pluck('nama');

        return response()->json($tipes);
    }

    // Approve Rekap List Page
    public function approveList(Request $request)
    {
        $query = Rekap::with(['user', 'items'])->where('status', 'pending');

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->filled('nama_rekap')) {
            $query->where('nama', 'like', '%' . $request->nama_rekap . '%');
        }
        if ($request->filled('no_penawaran')) {
            $query->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
        }
        if ($request->filled('nama_perusahaan')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }
        if ($request->filled('pic_admin')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }

        $rekaps = $query->paginate(10)->appends($request->query());
        $picAdmins = \App\Models\User::whereHas('rekaps')->distinct('name')->orderBy('name')->pluck('name');

        if ($request->ajax()) {
            return view('rekap.approve-table', compact('rekaps', 'picAdmins'))->render();
        }
        return view('rekap.approve-list', compact('rekaps', 'picAdmins'));
    }

    // Approve Rekap
    public function approve(Request $request, $id)
    {
        $rekap = Rekap::findOrFail($id);
        $rekap->update(['status' => 'approved']);

        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Rekap berhasil diapprove'
            ]);
        }

        return redirect()->route('rekap.approve-list')->with('success', 'Rekap berhasil diapprove');
    }
}

