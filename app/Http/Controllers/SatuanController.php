<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Satuan;

class SatuanController extends Controller
{
    /**
     * Check if user is admin, abort if not
     */
    private function checkAdminAccess()
    {
        if (!Auth::check() || Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    /**
     * Display list of satuan
     */
    public function index(Request $request)
    {
        $this->checkAdminAccess();
        
        $query = Satuan::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama', 'like', "%{$q}%");
        }

        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id', 'nama', 'created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id';
        if (!in_array(strtolower($direction), ['asc', 'desc'])) $direction = 'asc';

        $satuans = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        return view('satuan.index', compact('satuans'));
    }

    /**
     * Filter satuan (for AJAX)
     */
    public function filter(Request $request)
    {
        $this->checkAdminAccess();
        
        $query = Satuan::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama', 'like', "%{$q}%");
        }

        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id', 'nama', 'created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id';
        if (!in_array(strtolower($direction), ['asc', 'desc'])) $direction = 'asc';

        $satuans = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        $table = view('satuan.table-content', ['satuans' => $satuans])->render();
        $pagination = view('components.paginator', ['paginator' => $satuans])->render();
        return response()->json([
            'table' => $table,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Store new satuan
     */
    public function store(Request $request)
    {
        $this->checkAdminAccess();
        
        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:satuans,nama'
        ]);

        $satuan = Satuan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil ditambahkan',
            'data' => $satuan
        ]);
    }

    /**
     * Get satuan data for edit
     */
    public function edit($id)
    {
        $this->checkAdminAccess();
        
        $satuan = Satuan::findOrFail($id);
        return response()->json($satuan);
    }

    /**
     * Update satuan
     */
    public function update(Request $request, $id)
    {
        $this->checkAdminAccess();
        
        $satuan = Satuan::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:satuans,nama,' . $id
        ]);

        $satuan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil diperbarui',
            'data' => $satuan
        ]);
    }

    /**
     * Delete satuan
     */
    public function destroy($id)
    {
        $this->checkAdminAccess();
        
        $satuan = Satuan::findOrFail($id);
        $satuan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dihapus'
        ]);
    }

    /**
     * Get all satuan (for search/autocomplete)
     */
    public function getAll()
    {
        $this->checkAdminAccess();
        
        $satuans = Satuan::orderBy('nama')->get();
        return response()->json($satuans);
    }

    /**
     * Search satuan by query
     */
    public function search(Request $request)
    {
        $this->checkAdminAccess();
        
        $query = $request->input('q', '');

        $satuans = Satuan::where('nama', 'LIKE', "%{$query}%")
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return response()->json($satuans);
    }

    /**
     * Get all satuans for API (used by survey spreadsheet) - simple method without admin check
     */
    public function getSatuansApi(Request $request)
    {
        try {
            \Log::debug('📦 getSatuansApi called');
            
            // Simple query without any complex logic
            $satuans = Satuan::select('id', 'nama')
                        ->orderBy('nama', 'asc')
                        ->get();
            
            \Log::debug('📦 Retrieved satuans count: ' . $satuans->count());
            
            if ($satuans->isEmpty()) {
                \Log::warning('⚠️ No satuans found in database');
            }
            
            return response()->json([
                'success' => true,
                'data' => $satuans->toArray()
            ]);
        } catch (\Throwable $e) {
            \Log::error('❌ getSatuansApi error: ' . $e->getMessage());
            \Log::error('❌ File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching satuans: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}