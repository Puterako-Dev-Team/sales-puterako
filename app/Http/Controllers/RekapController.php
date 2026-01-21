<?php
namespace App\Http\Controllers;

use App\Models\Rekap;
use App\Models\Penawaran;
use App\Models\RekapKategori;
use App\Models\RekapItem;
use App\Models\Tipe;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapController extends Controller
{
    // List page
    public function index(Request $request)
    {
        // Manager role tidak bisa melihat list rekap survey
        if (Auth::user()->role === 'manager') {
            abort(403, 'Unauthorized access. Manager tidak memiliki akses ke halaman ini.');
        }

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
            $table = view('rekap.table-content', compact('rekaps'))->render();
            $pagination = view('components.paginator', ['paginator' => $rekaps])->render();
            
            return response()->json([
                'table' => $table,
                'pagination' => $pagination
            ]);
        }
        return view('rekap.list', compact('rekaps', 'penawarans', 'kategoris', 'picAdmins'));
    }

    // Show detail rekap
    public function show($id)
    {
        $rekap = Rekap::with(['items.kategori', 'items.tipe', 'items.satuan', 'user', 'penawaran'])->findOrFail($id);
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        $tipes = Tipe::all();
        $satuans = Satuan::all();

        $isEdit = $rekap->exists;

        return view('rekap.detail', compact('rekap', 'penawarans', 'kategoris', 'tipes', 'satuans', 'isEdit'));
    }

    // Form tambah rekap
    public function create()
    {
        // Manager role tidak bisa membuat rekap baru
        if (Auth::user()->role === 'manager') {
            abort(403, 'Unauthorized. Manager tidak dapat membuat rekap baru.');
        }

        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        $tipes = Tipe::all();
        $satuans = Satuan::all();
        return view('rekap.detail', compact('penawarans', 'kategoris', 'tipes', 'satuans'));
    }

    // Simpan rekap baru beserta items dan detail JSON
    public function store(Request $request)
    {
        // Manager role tidak bisa menyimpan rekap baru
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan rekap baru.'], 403);
        }

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rekap berhasil ditambahkan']);
        }

        return redirect()->route('rekap.list')->with('success', 'Rekap berhasil ditambahkan');
    }

    public function addItem(Request $request, $rekap_id)
    {
        // Manager role tidak bisa menambah item
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menambah item.'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.tipes_id' => 'required|exists:tipes,id',
            'items.*.nama_area' => 'required|string|max:255',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.satuan_id' => 'required|exists:satuans,id',
        ], [
            'items.required' => 'Minimal harus ada satu item',
            'items.*.rekap_kategori_id.required' => 'Kategori harus dipilih untuk setiap item',
            'items.*.rekap_kategori_id.exists' => 'Kategori yang dipilih tidak valid',
            'items.*.tipes_id.required' => 'Nama Item harus dipilih atau dibuat',
            'items.*.tipes_id.exists' => 'Nama Item yang dipilih tidak valid',
            'items.*.nama_area.required' => 'Nama Area harus diisi untuk setiap item',
            'items.*.jumlah.required' => 'Jumlah harus diisi untuk setiap item',
            'items.*.jumlah.numeric' => 'Jumlah harus berupa angka',
            'items.*.jumlah.min' => 'Jumlah harus lebih dari 0',
            'items.*.satuan_id.required' => 'Satuan harus dipilih untuk setiap item',
            'items.*.satuan_id.exists' => 'Satuan yang dipilih tidak valid',
        ]);

        // Check for duplicate items (same area, kategori, and tipes)
        $existingItems = RekapItem::where('rekap_id', $rekap_id)
            ->get()
            ->map(function($item) {
                return $item->nama_area . '|' . $item->rekap_kategori_id . '|' . $item->tipes_id;
            })
            ->toArray();

        foreach ($request->items as $item) {
            $itemKey = $item['nama_area'] . '|' . $item['rekap_kategori_id'] . '|' . $item['tipes_id'];
            
            if (in_array($itemKey, $existingItems)) {
                if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Item dengan area, kategori, dan tipe yang sama sudah ada di rekap ini.'
                    ], 422);
                }
                return back()->withErrors(['Item dengan area, kategori, dan tipe yang sama sudah ada di rekap ini.'])->withInput();
            }
            
            RekapItem::create([
                'rekap_id' => $rekap_id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $item['tipes_id'],
                'nama_area' => $item['nama_area'],
                'jumlah' => $item['jumlah'],
                'satuan_id' => $item['satuan_id'],
            ]);
            
            $existingItems[] = $itemKey;
        }

        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan'
            ]);
        }

        return redirect()->route('rekap.show', $rekap_id)->with('success', 'Item berhasil ditambahkan');
    }

    // Add new area to existing rekap
    public function addArea(Request $request, $rekap_id)
    {
        // Manager role tidak bisa menambah area
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menambah area.'], 403);
        }

        $request->validate([
            'nama_area' => 'required|string|max:255|filled',
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.tipes_id' => 'required|exists:tipes,id',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.satuan_id' => 'required|exists:satuans,id',
        ], [
            'nama_area.required' => 'Nama Area harus diisi',
            'nama_area.filled' => 'Nama Area tidak boleh kosong',
            'items.required' => 'Minimal harus ada satu item',
            'items.*.rekap_kategori_id.required' => 'Kategori harus dipilih untuk setiap item',
            'items.*.rekap_kategori_id.exists' => 'Kategori yang dipilih tidak valid',
            'items.*.tipes_id.required' => 'Nama Item harus dipilih atau dibuat',
            'items.*.tipes_id.exists' => 'Nama Item yang dipilih tidak valid',
            'items.*.jumlah.required' => 'Jumlah harus diisi untuk setiap item',
            'items.*.jumlah.numeric' => 'Jumlah harus berupa angka',
            'items.*.jumlah.min' => 'Jumlah harus lebih dari 0',
            'items.*.satuan_id.required' => 'Satuan harus dipilih untuk setiap item',
            'items.*.satuan_id.exists' => 'Satuan yang dipilih tidak valid',
        ]);

        // Check for duplicate tipes_id within same rekap
        $existingTipes = RekapItem::where('rekap_id', $rekap_id)
            ->pluck('tipes_id')
            ->toArray();

        foreach ($request->items as $item) {
            if (in_array($item['tipes_id'], $existingTipes)) {
                if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipe item sudah ada di rekap ini.'
                    ], 422);
                }
                return back()->withErrors(['Tipe item sudah ada di rekap ini.'])->withInput();
            }
            
            RekapItem::create([
                'rekap_id' => $rekap_id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $item['tipes_id'],
                'nama_area' => $request->nama_area,
                'jumlah' => $item['jumlah'],
                'satuan_id' => $item['satuan_id'],
            ]);
            
            $existingTipes[] = $item['tipes_id'];
        }

        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'Area dan item berhasil ditambahkan'
            ]);
        }

        return redirect()->route('rekap.show', $rekap_id)->with('success', 'Area dan item berhasil ditambahkan');
    }

    // Form edit rekap
    public function edit($id)
    {
        // Manager role tidak bisa edit rekap
        if (Auth::user()->role === 'manager') {
            abort(403, 'Unauthorized. Manager tidak dapat mengedit rekap.');
        }

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
        // Manager role tidak bisa update rekap
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengupdate rekap.'], 403);
        }

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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rekap berhasil diperbarui']);
        }

        return redirect()->route('rekap.show', $rekap->id)->with('success', 'Rekap berhasil diupdate');
    }

    public function updateItems(Request $request, $rekap_id)
    {
        // Manager role tidak bisa update items
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengupdate items.'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.rekap_kategori_id' => 'required|exists:rekap_kategoris,id',
            'items.*.tipes_id' => 'required|exists:tipes,id',
            'items.*.nama_area' => 'required|string|max:255',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.satuan_id' => 'required|exists:satuans,id',
        ], [
            'items.required' => 'Minimal harus ada satu item',
            'items.*.rekap_kategori_id.required' => 'Kategori harus dipilih untuk setiap item',
            'items.*.rekap_kategori_id.exists' => 'Kategori yang dipilih tidak valid',
            'items.*.tipes_id.required' => 'Nama Item harus dipilih atau dibuat',
            'items.*.tipes_id.exists' => 'Nama Item yang dipilih tidak valid',
            'items.*.nama_area.required' => 'Nama Area harus diisi untuk setiap item',
            'items.*.jumlah.required' => 'Jumlah harus diisi untuk setiap item',
            'items.*.jumlah.numeric' => 'Jumlah harus berupa angka',
            'items.*.jumlah.min' => 'Jumlah harus lebih dari 0',
            'items.*.satuan_id.required' => 'Satuan harus dipilih untuk setiap item',
            'items.*.satuan_id.exists' => 'Satuan yang dipilih tidak valid',
        ]);

        // Check for duplicate items (same area, kategori, and tipes)
        $itemKeys = [];
        foreach ($request->items as $item) {
            $itemKey = $item['nama_area'] . '|' . $item['rekap_kategori_id'] . '|' . $item['tipes_id'];
            if (in_array($itemKey, $itemKeys)) {
                return back()->withErrors(['Tidak boleh ada item duplikat dengan area, kategori, dan tipe yang sama.'])->withInput();
            }
            $itemKeys[] = $itemKey;
        }

        $rekap = Rekap::findOrFail($rekap_id);

        // Hapus semua item lama
        $rekap->items()->delete();

        // Insert ulang item dari form
        foreach ($request->items as $item) {
            RekapItem::create([
                'rekap_id' => $rekap_id,
                'rekap_kategori_id' => $item['rekap_kategori_id'],
                'tipes_id' => $item['tipes_id'],
                'nama_area' => $item['nama_area'],
                'jumlah' => $item['jumlah'],
                'satuan_id' => $item['satuan_id'],
            ]);
        }

        return redirect()->route('rekap.show', $rekap_id)->with('success', 'Item berhasil diupdate');
    }

    // Hapus rekap (soft delete)
    public function destroy($id)
    {
        // Manager role tidak bisa delete rekap
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menghapus rekap.'], 403);
        }

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
        $totalRecords = Rekap::count();

        if ($request->ajax()) {
            $table = view('rekap.approve-table', compact('rekaps'))->render();
            $pagination = view('components.paginator', ['paginator' => $rekaps])->render();
            
            // Generate filter info
            $activeFilters = [];
            if ($request->tanggal_dari) $activeFilters[] = 'Tanggal';
            if ($request->nama_rekap) $activeFilters[] = 'Nama Rekap';
            if ($request->no_penawaran) $activeFilters[] = 'No Penawaran';
            if ($request->nama_perusahaan) $activeFilters[] = 'Perusahaan';
            if ($request->pic_admin) $activeFilters[] = 'Dibuat Oleh';
            
            $info = '';
            if (count($activeFilters) > 0) {
                $info = view('rekap.approve-filter-info', [
                    'count' => $rekaps->count(),
                    'total' => $totalRecords,
                    'filters' => implode(', ', $activeFilters),
                    'currentPage' => $rekaps->currentPage(),
                    'lastPage' => $rekaps->lastPage(),
                    'from' => $rekaps->firstItem(),
                    'to' => $rekaps->lastItem()
                ])->render();
            }
            
            return response()->json([
                'table' => $table,
                'pagination' => $pagination,
                'info' => $info
            ]);
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

    // Search tipes for autocomplete
    public function searchTipes(Request $request)
    {
        $query = $request->get('q', '');
        
        // Search for tipes by nama (case-insensitive)
        $tipes = Tipe::where('nama', 'like', '%' . $query . '%')
            ->distinct('nama')
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nama'])
            ->map(fn($tipe) => [
                'value' => $tipe->id,
                'text' => $tipe->nama
            ])
            ->values()
            ->toArray();

        return response()->json($tipes);
    }

    // Create new tipe from form (allowed for all authenticated users)
    public function createTipe(Request $request)
    {
        // Manager role tidak bisa membuat tipe baru
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat membuat tipe baru.'], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:tipes,nama'
        ]);

        try {
            $tipe = Tipe::create($validated);

            return response()->json([
                'id' => $tipe->id,
                'nama' => $tipe->nama,
                'success' => true,
                'message' => 'Tipe berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat tipe: ' . $e->getMessage()
            ], 400);
        }
    }}