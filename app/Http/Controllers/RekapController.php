<?php
namespace App\Http\Controllers;

use App\Models\Rekap;
use App\Models\RekapVersion;
use App\Models\Penawaran;
use App\Models\RekapKategori;
use App\Models\RekapItem;
use App\Models\Tipe;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public function show(Request $request, $id)
    {
        $rekap = Rekap::with(['items.kategori', 'items.tipe', 'items.satuan', 'user', 'penawaran', 'versions'])->findOrFail($id);
        $penawarans = Penawaran::all();
        $kategoris = RekapKategori::all();
        $tipes = Tipe::all();
        $satuans = Satuan::all();

        // Get version parameter (default to latest)
        $version = $request->query('version');
        $hasVersions = $rekap->versions()->exists();
        
        // Get current version row
        $currentVersion = null;
        $versions = [];
        
        if ($hasVersions) {
            $versions = $rekap->versions()->orderByDesc('version')->get();
            
            if ($version !== null) {
                $currentVersion = $rekap->versions()->where('version', $version)->first();
            } else {
                $currentVersion = $rekap->versions()->orderByDesc('version')->first();
            }
        }

        $isEdit = $rekap->exists;

        return view('rekap.detail', compact('rekap', 'penawarans', 'kategoris', 'tipes', 'satuans', 'isEdit', 'versions', 'currentVersion', 'hasVersions'));
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

    public function all(Request $request)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        $penawaranId = $request->get('penawaran_id');

        // Debug: Return all approved rekaps without complex filtering
        $rekaps = Rekap::where('status', 'approved')
            ->with('user')
            ->select('id', 'nama', 'user_id', 'imported_by', 'imported_into_penawaran_id', 'penawaran_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // Also include rekaps created for this penawaran (even if not approved yet)
        if ($penawaranId) {
            $rekapForPenawaran = Rekap::where('penawaran_id', $penawaranId)
                ->with('user')
                ->select('id', 'nama', 'user_id', 'imported_by', 'imported_into_penawaran_id', 'penawaran_id')
                ->get();
            
            $rekaps = $rekaps->merge($rekapForPenawaran)->unique('id');
        }

        // Format response to include user name
        $formatted = $rekaps->map(function($r) {
            return [
                'id' => $r->id,
                'nama' => $r->nama,
                'user_id' => $r->user_id,
                'user_name' => $r->user ? $r->user->name : 'Unknown',
                'imported_by' => $r->imported_by,
                'imported_into_penawaran_id' => $r->imported_into_penawaran_id,
                'penawaran_id' => $r->penawaran_id
            ];
        });

        return response()->json($formatted->values());
    }
    public function getItems(Request $request, $id)
    {
        $rekap = Rekap::with('versions')->findOrFail($id);
        $userId = \Illuminate\Support\Facades\Auth::id();

        if ($rekap->imported_by && $rekap->imported_by != $userId) {
            return response()->json([
                'message' => 'Rekap ini sudah diimport oleh user lain.'
            ], 403);
        }

        if (is_null($rekap->imported_by)) {
            $rekap->imported_by = $userId;
            $rekap->imported_at = now();
            $rekap->save();
        }

        // Get version_id for filtering items
        $versionNum = $request->query('version');
        $versionId = null;
        
        if ($versionNum !== null) {
            $versionRow = $rekap->versions()->where('version', $versionNum)->first();
            if ($versionRow) {
                $versionId = $versionRow->id;
            }
        } else {
            // Default to latest version
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            if ($latestVersion) {
                $versionId = $latestVersion->id;
            }
        }

        // Fetch items with version filtering
        $itemsQuery = RekapItem::with(['tipe', 'kategori', 'satuan'])
                    ->where('rekap_id', $id);
        
        if ($versionId) {
            // First try to get items for the specific version
            $items = (clone $itemsQuery)->where('version_id', $versionId)->get();
            
            // Fallback: if no items found for this version, try items without version_id (backward compat)
            if ($items->isEmpty()) {
                $items = (clone $itemsQuery)->whereNull('version_id')->get();
            }
        } else {
            // No versions exist, get items without version_id
            $items = $itemsQuery->whereNull('version_id')->get();
            
            // Fallback: if still empty, get all items regardless of version
            if ($items->isEmpty()) {
                $items = RekapItem::with(['tipe', 'kategori', 'satuan'])
                            ->where('rekap_id', $id)
                            ->get();
            }
        }

        // mapping: pastikan nama item diambil dari tipe.nama bila ada, fallback ke nama_item
        $payload = $items->map(function($it) {
            return [
                'id' => $it->id,
                'nama_item' => optional($it->tipe)->nama ?: ($it->nama_item ?? ''),
                'nama_area' => $it->nama_area ?? '',
                'jumlah' => $it->jumlah ?? 0,
                'satuan' => optional($it->satuan)->nama ?? ($it->satuan ?? ''),
                'kategori' => $it->kategori ? ['id' => $it->kategori->id, 'nama' => $it->kategori->nama] : null
            ];
        });

        return response()->json($payload);
    }

    public function import(Request $request, $id)
    {
        $request->validate([
            'penawaran_id' => 'required|exists:penawarans,id_penawaran',
            'version' => 'nullable|integer'
        ]);

        $rekap = Rekap::with('versions')->findOrFail($id);
        $userId = Auth::id();

        if ($rekap->imported_by && $rekap->imported_by != $userId) {
            return response()->json(['message' => 'Rekap ini sudah diimport oleh user lain.'], 403);
        }

        // Clear any previously imported rekaps for this penawaran (only one rekap per penawaran)
        Rekap::where('imported_into_penawaran_id', $request->penawaran_id)
            ->where('id', '!=', $id)
            ->update([
                'imported_into_penawaran_id' => null,
                'imported_by' => null,
                'imported_at' => null,
            ]);

        if (is_null($rekap->imported_by)) {
            $rekap->imported_by = $userId;
            $rekap->imported_at = now();
        }

        // tautkan ke penawaran target
        $rekap->imported_into_penawaran_id = $request->penawaran_id;
        $rekap->save();

        // Get version_id for filtering items
        $versionNum = $request->input('version');
        $versionId = null;
        
        if ($versionNum !== null) {
            $versionRow = $rekap->versions()->where('version', $versionNum)->first();
            if ($versionRow) {
                $versionId = $versionRow->id;
            }
        } else {
            // Default to latest version
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            if ($latestVersion) {
                $versionId = $latestVersion->id;
            }
        }

        // Fetch items with version filtering
        $itemsQuery = RekapItem::with(['tipe', 'kategori', 'satuan'])
                    ->where('rekap_id', $id);
        
        if ($versionId) {
            // First try to get items for the specific version
            $items = (clone $itemsQuery)->where('version_id', $versionId)->get();
            
            // Fallback: if no items found for this version, try items without version_id (backward compat)
            if ($items->isEmpty()) {
                $items = (clone $itemsQuery)->whereNull('version_id')->get();
            }
        } else {
            // No versions exist, get items without version_id
            $items = $itemsQuery->whereNull('version_id')->get();
            
            // Fallback: if still empty, get all items regardless of version
            if ($items->isEmpty()) {
                $items = RekapItem::with(['tipe', 'kategori', 'satuan'])
                            ->where('rekap_id', $id)
                            ->get();
            }
        }

        $payload = $items->map(function($it) {
            return [
                'id' => $it->id,
                'nama_item' => optional($it->tipe)->nama ?? $it->nama_item ?? '',
                'nama_area' => $it->nama_area,
                'jumlah' => $it->jumlah,
                'satuan' => optional($it->satuan)->nama ?? '',
                'kategori' => $it->kategori ? ['id' => $it->kategori->id, 'nama' => $it->kategori->nama] : null
            ];
        });

        return response()->json($payload);
    }
    public function forPenawaran(Request $request, $penawaran_id)
    {
        $userId = Auth::id();

        // Get rekaps that are:
        // 1. Imported into this penawaran by current user, OR
        // 2. Created for this penawaran (penawaran_id) and approved
        $rekaps = Rekap::where(function($q) use ($penawaran_id, $userId) {
            $q->where('imported_into_penawaran_id', $penawaran_id)
              ->where('imported_by', $userId);
        })
        ->orWhere(function($q) use ($penawaran_id) {
            $q->where('penawaran_id', $penawaran_id)
              ->where('status', 'approved');
        })
        ->with(['versions', 'items.tipe', 'items.satuan', 'items.kategori'])
        ->get();

        $payload = $rekaps->flatMap(function($rekap) {
            // Get the latest version for this rekap
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            $versionId = $latestVersion ? $latestVersion->id : null;
            
            // Filter items by version_id with fallback
            $items = collect([]);
            
            if ($versionId) {
                // First try items for the specific version
                $items = $rekap->items->filter(function($item) use ($versionId) {
                    return $item->version_id === $versionId;
                });
                
                // Fallback: if no items found, try items without version_id
                if ($items->isEmpty()) {
                    $items = $rekap->items->filter(function($item) {
                        return $item->version_id === null;
                    });
                }
            } else {
                // No versions exist, get items without version_id
                $items = $rekap->items->filter(function($item) {
                    return $item->version_id === null;
                });
                
                // Fallback: if still empty, get all items
                if ($items->isEmpty()) {
                    $items = $rekap->items;
                }
            }
            
            return $items->map(function($it) {
                return [
                    'id' => $it->id,
                    'nama_item' => optional($it->tipe)->nama ?? $it->nama_item ?? '',
                    'nama_area' => $it->nama_area,
                    'jumlah' => $it->jumlah,
                    'satuan' => optional($it->satuan)->nama ?? '',
                    'kategori' => $it->kategori ? ['id' => $it->kategori->id, 'nama' => $it->kategori->nama] : null
                ];
            });
        })->values();

        return response()->json($payload);
    }

    /**
     * Get all rekap surveys for a penawaran (for Rincian Rekap tab)
     * Returns surveys from rekaps that are either:
     * - Created for this penawaran (penawaran_id) and approved
     * - Imported into this penawaran
     */
    public function surveysForPenawaran(Request $request, $penawaran_id)
    {
        // Get rekaps linked to this penawaran
        $rekaps = Rekap::where(function($q) use ($penawaran_id) {
            // Rekaps imported into this penawaran
            $q->where('imported_into_penawaran_id', $penawaran_id);
        })
        ->orWhere(function($q) use ($penawaran_id) {
            // Rekaps created for this penawaran and approved
            $q->where('penawaran_id', $penawaran_id)
              ->where('status', 'approved');
        })
        ->with(['versions', 'surveys', 'supportingDocuments'])
        ->get();

        if ($rekaps->isEmpty()) {
            return response()->json([
                'success' => true,
                'rekaps' => []
            ]);
        }

        $result = [];

        foreach ($rekaps as $rekap) {
            // Get the latest version for this rekap
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            $versionId = $latestVersion ? $latestVersion->id : null;
            
            // Get surveys filtered by version
            $surveys = collect([]);
            
            if ($versionId) {
                $surveys = $rekap->surveys->filter(function($s) use ($versionId) {
                    return $s->version_id === $versionId;
                });
                
                // Fallback to surveys without version_id
                if ($surveys->isEmpty()) {
                    $surveys = $rekap->surveys->filter(function($s) {
                        return $s->version_id === null;
                    });
                }
            } else {
                $surveys = $rekap->surveys->filter(function($s) {
                    return $s->version_id === null;
                });
                
                // Fallback to all surveys
                if ($surveys->isEmpty()) {
                    $surveys = $rekap->surveys;
                }
            }

            if ($surveys->isNotEmpty()) {
                // Format supporting documents
                $supportingDocs = $rekap->supportingDocuments->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'filename' => $doc->original_filename,
                        'file_path' => $doc->file_path,
                        'notes' => $doc->notes,
                        'created_at' => $doc->created_at
                    ];
                })->values();

                $result[] = [
                    'rekap_id' => $rekap->id,
                    'rekap_nama' => $rekap->nama,
                    'rekap_status' => $rekap->status,
                    'version' => $latestVersion ? $latestVersion->version : null,
                    'version_notes' => $latestVersion ? $latestVersion->notes : null,
                    'supporting_documents' => $supportingDocs,
                    'rekap_updated_at' => $rekap->updated_at ? $rekap->updated_at->toISOString() : null,
                    'surveys' => $surveys->map(function($survey) {
                        return [
                            'id' => $survey->id,
                            'area_name' => $survey->area_name ?? 'Default Area',
                            'headers' => $survey->headers,
                            'data' => $survey->data,
                            'totals' => $survey->totals,
                            'comments' => $survey->comments,
                            'satuans' => $survey->satuans,
                            'updated_at' => $survey->updated_at ? $survey->updated_at->toISOString() : null,
                        ];
                    })->values()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'rekaps' => $result
        ]);
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
        
        // Get approved rekaps for history section
        $historyQuery = Rekap::with(['user'])->where('status', 'approved');
        // Apply same filters to history
        if ($request->filled('tanggal_dari')) {
            $historyQuery->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->filled('nama_rekap')) {
            $historyQuery->where('nama', 'like', '%' . $request->nama_rekap . '%');
        }
        if ($request->filled('no_penawaran')) {
            $historyQuery->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
        }
        if ($request->filled('nama_perusahaan')) {
            $historyQuery->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }
        if ($request->filled('pic_admin')) {
            $historyQuery->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }
        $historyRekaps = $historyQuery->orderBy('updated_at', 'desc')->limit(20)->get();
        
        $picAdmins = \App\Models\User::whereHas('rekaps')->distinct('name')->orderBy('name')->pluck('name');
        $totalRecords = Rekap::count();

        if ($request->ajax()) {
            $table = view('rekap.approve-table', compact('rekaps', 'historyRekaps'))->render();
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
        return view('rekap.approve-list', compact('rekaps', 'historyRekaps', 'picAdmins'));
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

    // Export Rekap to Excel using maatwebsite/excel
    public function export($id)
    {
        try {
            $rekap = Rekap::with(['items.kategori', 'items.tipe', 'items.satuan', 'user', 'penawaran'])->findOrFail($id);
            
            $exporter = new \App\Exports\RekapDetailExport($rekap);
            return $exporter->export();
        } catch (\Exception $e) {
            \Log::error('Error exporting rekap: ' . $e->getMessage());
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengexport rekap: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Gagal mengexport rekap');
        }
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
    }

    // Save survey data as JSON
    public function saveSurvey(Request $request, $rekap_id)
    {
        // Manager role tidak bisa menyimpan survey
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan survey.'], 403);
        }

        $request->validate([
            'headers' => 'required|array',
            'data' => 'required|array',
            'area_name' => 'nullable|string|max:255',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);
        
        $survey = $rekap->survey ?? new \App\Models\RekapSurvey(['rekap_id' => $rekap_id]);
        $survey->area_name = $request->area_name;
        $survey->headers = $request->headers;
        $survey->data = $request->data;
        $survey->totals = $survey->calculateTotals();
        $survey->save();

        return response()->json([
            'success' => true,
            'message' => 'Survey berhasil disimpan',
            'survey_id' => $survey->id,
            'totals' => $survey->totals
        ]);
    }

    // Get survey data (for Sales department)
    public function getSurvey($rekap_id)
    {
        $rekap = Rekap::with('survey')->findOrFail($rekap_id);
        
        if (!$rekap->survey) {
            return response()->json([
                'success' => true,
                'has_survey' => false,
                'headers' => \App\Models\RekapSurvey::getDefaultHeaders(),
                'data' => [],
                'totals' => [],
                'area_name' => ''
            ]);
        }

        return response()->json([
            'success' => true,
            'has_survey' => true,
            'survey_id' => $rekap->survey->id,
            'area_name' => $rekap->survey->area_name ?? '',
            'headers' => $rekap->survey->headers,
            'data' => $rekap->survey->data,
            'totals' => $rekap->survey->totals,
            'updated_at' => $rekap->survey->updated_at->toISOString()
        ]);
    }

    // Update survey headers structure
    public function updateSurveyHeaders(Request $request, $rekap_id)
    {
        // Manager role tidak bisa mengupdate headers
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengupdate headers.'], 403);
        }

        $request->validate([
            'headers' => 'required|array',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);
        
        $survey = $rekap->survey ?? new \App\Models\RekapSurvey(['rekap_id' => $rekap_id]);
        $survey->headers = $request->headers;
        
        // Recalculate totals if data exists
        if ($survey->data) {
            $survey->totals = $survey->calculateTotals();
        }
        
        $survey->save();

        return response()->json([
            'success' => true,
            'message' => 'Headers berhasil diupdate',
            'headers' => $survey->headers
        ]);
    }

    // Export survey data to Excel using PhpSpreadsheet
    public function exportSurvey(Request $request, $rekap_id)
    {
        $rekap = Rekap::with(['survey', 'surveys', 'penawaran', 'versions'])->findOrFail($rekap_id);
        
        // Get version parameter
        $versionNum = $request->query('version');
        $versionId = null;
        
        if ($versionNum !== null) {
            $versionRow = $rekap->versions()->where('version', $versionNum)->first();
            if ($versionRow) {
                $versionId = $versionRow->id;
            }
        } else {
            // Default to latest version
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            if ($latestVersion) {
                $versionId = $latestVersion->id;
                $versionNum = $latestVersion->version;
            }
        }
        
        $export = new \App\Exports\RekapSurveyExport($rekap, $versionId, $versionNum);
        $spreadsheet = $export->export();
        
        $versionSuffix = $versionNum !== null ? '_Rev' . $versionNum : '';
        $filename = 'Survey_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $rekap->nama) . $versionSuffix . '_' . now()->format('Ymd_His') . '.xlsx';
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Log activity for exporting survey
        activity()
            ->performedOn($rekap)
            ->causedBy(Auth::user())
            ->withProperties(['version' => $versionNum ?? 0])
            ->log('Exported Excel');
        
        return response()->stream(
            function() use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    // Get all surveys for a rekap (multi-area support)
    public function getSurveys(Request $request, $rekap_id)
    {
        $rekap = Rekap::with('surveys', 'versions')->findOrFail($rekap_id);
        
        // Get version parameter
        $versionNum = $request->query('version');
        $versionId = null;
        
        // Find the version_id for the requested version number
        if ($versionNum !== null) {
            $versionRow = $rekap->versions()->where('version', $versionNum)->first();
            if ($versionRow) {
                $versionId = $versionRow->id;
            }
        } else {
            // Default to latest version
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            if ($latestVersion) {
                $versionId = $latestVersion->id;
            }
        }
        
        // Get surveys filtered by version
        $surveysQuery = $rekap->surveys();
        if ($versionId) {
            $surveysQuery->where('version_id', $versionId);
        } else {
            // For backward compatibility - get surveys without version_id
            $surveysQuery->whereNull('version_id');
        }
        
        $surveys = $surveysQuery->get();
        
        if ($surveys->isEmpty()) {
            return response()->json([
                'success' => true,
                'surveys' => []
            ]);
        }

        $surveys = $surveys->map(function ($survey) {
            // Ensure comments is always an object, not array
            $comments = $survey->comments;
            if (empty($comments) || (is_array($comments) && array_keys($comments) === range(0, count($comments) - 1))) {
                $comments = new \stdClass();
            }
            
            // Ensure satuans is always an object, not array
            $satuans = $survey->satuans;
            if (empty($satuans) || (is_array($satuans) && array_keys($satuans) === range(0, count($satuans) - 1))) {
                $satuans = new \stdClass();
            }
            
            return [
                'id' => $survey->id,
                'area_name' => $survey->area_name ?? '',
                'headers' => $survey->headers,
                'data' => $survey->data,
                'totals' => $survey->totals,
                'comments' => $comments,
                'satuans' => $satuans,
            ];
        });

        return response()->json([
            'success' => true,
            'surveys' => $surveys
        ]);
    }

    // Save multiple surveys (multi-area support)
    public function saveSurveys(Request $request, $rekap_id)
    {
        // Manager role tidak bisa menyimpan survey
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat menyimpan survey.'], 403);
        }

        $request->validate([
            'areas' => 'required|array',
            'areas.*.area_name' => 'nullable|string|max:255',
            'areas.*.headers' => 'required|array',
            'areas.*.data' => 'required|array',
            'version' => 'nullable|integer',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);
        
        // Get version_id from version number
        $versionNum = $request->input('version');
        $versionId = null;
        
        if ($versionNum !== null) {
            $versionRow = $rekap->versions()->where('version', $versionNum)->first();
            if ($versionRow) {
                $versionId = $versionRow->id;
            }
        } else {
            // Default to latest version
            $latestVersion = $rekap->versions()->orderByDesc('version')->first();
            if ($latestVersion) {
                $versionId = $latestVersion->id;
            }
        }
        
        // Get existing survey IDs for this version
        $existingQuery = $rekap->surveys();
        if ($versionId) {
            $existingQuery->where('version_id', $versionId);
        } else {
            $existingQuery->whereNull('version_id');
        }
        $existingSurveyIds = $existingQuery->pluck('id')->toArray();
        $processedIds = [];
        $areaIds = [];

        foreach ($request->areas as $areaData) {
            if (!empty($areaData['id']) && in_array($areaData['id'], $existingSurveyIds)) {
                // Update existing survey
                $survey = \App\Models\RekapSurvey::find($areaData['id']);
            } else {
                // Create new survey
                $survey = new \App\Models\RekapSurvey(['rekap_id' => $rekap_id]);
                $survey->version_id = $versionId;
            }
            
            $survey->area_name = $areaData['area_name'] ?? '';
            $survey->headers = $areaData['headers'];
            $survey->data = $areaData['data'];
            
            // Ensure comments is saved as object (associative array) not sequential array
            $comments = $areaData['comments'] ?? [];
            if (is_array($comments) && !empty($comments)) {
                // Convert to object to preserve as associative
                $survey->comments = (object) $comments;
            } else {
                $survey->comments = new \stdClass();
            }
            
            // Save satuans data
            $satuans = $areaData['satuans'] ?? [];
            if (is_array($satuans) && !empty($satuans)) {
                $survey->satuans = (object) $satuans;
            } else {
                $survey->satuans = new \stdClass();
            }
            
            $survey->totals = $survey->calculateTotals();
            $survey->save();
            
            $processedIds[] = $survey->id;
            $areaIds[] = $survey->id;
        }
        
        // Delete surveys that were removed (only for this version)
        $toDelete = array_diff($existingSurveyIds, $processedIds);
        if (!empty($toDelete)) {
            \App\Models\RekapSurvey::whereIn('id', $toDelete)->delete();
        }

        // Log activity for saving survey
        activity()
            ->performedOn($rekap)
            ->causedBy(Auth::user())
            ->withProperties(['version' => $versionNum ?? 0, 'areas_count' => count($request->areas)])
            ->log('Saved survey');

        return response()->json([
            'success' => true,
            'message' => 'Semua area survey berhasil disimpan',
            'area_ids' => $areaIds
        ]);
    }

    // Get all versions for a rekap
    public function getVersions($rekap_id)
    {
        $rekap = Rekap::findOrFail($rekap_id);
        $versions = $rekap->versions()->orderByDesc('version')->get();
        
        return response()->json([
            'success' => true,
            'versions' => $versions->map(function ($v) {
                return [
                    'id' => $v->id,
                    'version' => $v->version,
                    'notes' => $v->notes,
                    'status' => $v->status,
                    'created_at' => $v->created_at->format('d M Y H:i'),
                ];
            })
        ]);
    }

    // Create a new version (revision)
    public function createRevision($rekap_id)
    {
        // Manager role tidak bisa membuat revisi
        if (Auth::user()->role === 'manager') {
            return redirect()->back()->with('error', 'Unauthorized. Manager tidak dapat membuat revisi.');
        }

        $rekap = Rekap::findOrFail($rekap_id);

        // Get last version number
        $lastVersion = $rekap->versions()->max('version');
        
        // If no versions exist, set to -1 so new version becomes 0
        if ($lastVersion === null) {
            $lastVersion = -1;
        }

        $newVersionNum = $lastVersion + 1;

        // Get previous version if exists
        $oldVersion = null;
        if ($lastVersion >= 0) {
            $oldVersion = $rekap->versions()->where('version', $lastVersion)->first();
        }

        // Create new version
        $newVersion = RekapVersion::create([
            'rekap_id' => $rekap_id,
            'version' => $newVersionNum,
            'notes' => $oldVersion ? ($oldVersion->notes ?? null) : null,
            'status' => 'draft',
        ]);

        // Copy items and surveys from previous version or legacy data
        if ($oldVersion) {
            // Copy from previous version
            $oldItems = RekapItem::where('version_id', $oldVersion->id)->get();
            foreach ($oldItems as $item) {
                RekapItem::create([
                    'rekap_id' => $rekap_id,
                    'version_id' => $newVersion->id,
                    'rekap_kategori_id' => $item->rekap_kategori_id,
                    'tipes_id' => $item->tipes_id,
                    'nama_area' => $item->nama_area,
                    'jumlah' => $item->jumlah,
                    'satuan_id' => $item->satuan_id,
                ]);
            }

            // Copy surveys from previous version
            $oldSurveys = \App\Models\RekapSurvey::where('version_id', $oldVersion->id)->get();
            foreach ($oldSurveys as $survey) {
                \App\Models\RekapSurvey::create([
                    'rekap_id' => $rekap_id,
                    'version_id' => $newVersion->id,
                    'area_name' => $survey->area_name,
                    'headers' => $survey->headers,
                    'data' => $survey->data,
                    'totals' => $survey->totals,
                    'comments' => $survey->comments,
                    'satuans' => $survey->satuans,
                ]);
            }
        } else {
            // First revision - copy from legacy data (surveys without version_id)
            $legacyItems = RekapItem::where('rekap_id', $rekap_id)->whereNull('version_id')->get();
            foreach ($legacyItems as $item) {
                RekapItem::create([
                    'rekap_id' => $rekap_id,
                    'version_id' => $newVersion->id,
                    'rekap_kategori_id' => $item->rekap_kategori_id,
                    'tipes_id' => $item->tipes_id,
                    'nama_area' => $item->nama_area,
                    'jumlah' => $item->jumlah,
                    'satuan_id' => $item->satuan_id,
                ]);
            }

            $legacySurveys = \App\Models\RekapSurvey::where('rekap_id', $rekap_id)->whereNull('version_id')->get();
            foreach ($legacySurveys as $survey) {
                \App\Models\RekapSurvey::create([
                    'rekap_id' => $rekap_id,
                    'version_id' => $newVersion->id,
                    'area_name' => $survey->area_name,
                    'headers' => $survey->headers,
                    'data' => $survey->data,
                    'totals' => $survey->totals,
                    'comments' => $survey->comments,
                    'satuans' => $survey->satuans,
                ]);
            }
        }

        // Log activity for creating revision
        activity()
            ->performedOn($rekap)
            ->causedBy(Auth::user())
            ->withProperties(['new_version' => $newVersionNum])
            ->log('Created revision');

        // Redirect to the same page with new version
        return redirect()->route('rekap.show', ['id' => $rekap_id, 'version' => $newVersionNum])
            ->with('success', 'Revisi ' . $newVersionNum . ' berhasil dibuat');
    }

    // Update version notes
    public function updateVersionNotes(Request $request, $rekap_id, $version)
    {
        // Manager role tidak bisa update notes
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengubah notes.'], 403);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);
        $versionRow = $rekap->versions()->where('version', $version)->firstOrFail();
        
        $versionRow->notes = $request->input('notes');
        $versionRow->save();

        return response()->json([
            'success' => true,
            'message' => 'Notes berhasil disimpan'
        ]);
    }

    // Update version status
    public function updateVersionStatus(Request $request, $rekap_id, $version)
    {
        // Manager role tidak bisa update status
        if (Auth::user()->role === 'manager') {
            return response()->json(['error' => 'Unauthorized. Manager tidak dapat mengubah status.'], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,done,loss',
        ]);

        $rekap = Rekap::findOrFail($rekap_id);
        $versionRow = $rekap->versions()->where('version', $version)->firstOrFail();
        
        $versionRow->status = $request->input('status');
        $versionRow->save();

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diubah'
        ]);
    }

    /**
     * Show activity log for a rekap.
     */
    public function showLog(Request $request)
    {
        $id = $request->query('id');
        $rekap = Rekap::findOrFail($id);
        
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Rekap::class)
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

    /**
     * Count unread activities for a rekap.
     */
    public function countUnreadActivities(Request $request)
    {
        $id = $request->query('id');
        $userId = Auth::id();
        
        // Get last read timestamp for this user and rekap
        $lastRead = DB::table('activity_reads')
            ->where('user_id', $userId)
            ->where('rekap_id', $id)
            ->value('last_read_at');
        
        // Count activities after last read
        $query = \Spatie\Activitylog\Models\Activity::where('subject_type', Rekap::class)
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

    /**
     * Mark activities as read for a rekap.
     */
    public function markActivitiesAsRead(Request $request)
    {
        $id = $request->input('id');
        $userId = Auth::id();
        
        DB::table('activity_reads')->updateOrInsert(
            ['user_id' => $userId, 'rekap_id' => $id, 'penawaran_id' => null],
            ['last_read_at' => now(), 'updated_at' => now()]
        );
        
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Get supporting documents for a rekap.
     */
    public function getSupportingDocuments($id)
    {
        $rekap = Rekap::findOrFail($id);

        $documents = $rekap->supportingDocuments()
            ->orderByDesc('created_at')
            ->get()
            ->map(function($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => $doc->original_filename,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'notes' => $doc->notes,
                    'uploaded_by' => $doc->uploaded_by,
                    'created_at' => $doc->created_at,
                ];
            });

        return response()->json($documents);
    }

    /**
     * Upload a supporting document for a rekap.
     */
    public function uploadDocument(Request $request, $id)
    {
        $rekap = Rekap::findOrFail($id);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'notes' => 'nullable|string|max:500'
        ]);

        $file = $request->file('file');
        $fileName = $id . '_' . time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('rekap/' . $id . '/documents', $fileName, 'public');

        $document = \App\Models\RekapSupportingDocument::create([
            'id_rekap' => $id,
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::user()->name,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'filename' => $document->original_filename,
                'created_at' => $document->created_at,
            ]
        ], 201);
    }

    /**
     * Delete a supporting document.
     */
    public function deleteDocument($id, $documentId)
    {
        $rekap = Rekap::findOrFail($id);

        $document = \App\Models\RekapSupportingDocument::where('id', $documentId)
            ->where('id_rekap', $id)
            ->firstOrFail();

        // Delete file from storage
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Download a supporting document.
     */
    public function downloadDocument($id, $documentId)
    {
        $rekap = Rekap::findOrFail($id);

        $document = \App\Models\RekapSupportingDocument::where('id', $documentId)
            ->where('id_rekap', $id)
            ->firstOrFail();

        return \Illuminate\Support\Facades\Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename
        );
    }
}