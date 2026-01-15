<?php

namespace App\Http\Controllers;

use App\Models\Tipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TipeController extends Controller
{
    public function __construct()
    {
        if (!Auth::check() || Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    /**
     * Display list of tipe
     */
    public function index(Request $request)
    {
        $query = Tipe::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama', 'like', "%{$q}%");
        }

        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id', 'nama', 'created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id';
        if (!in_array(strtolower($direction), ['asc', 'desc'])) $direction = 'asc';

        $tipes = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        return view('tipe.index', compact('tipes'));
    }

    /**
     * Filter tipe (for AJAX)
     */
    public function filter(Request $request)
    {
        $query = Tipe::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama', 'like', "%{$q}%");
        }

        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id', 'nama', 'created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id';
        if (!in_array(strtolower($direction), ['asc', 'desc'])) $direction = 'asc';

        $tipes = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        $table = view('tipe.table-content', ['tipes' => $tipes])->render();
        $pagination = view('penawaran.pagination', ['paginator' => $tipes])->render();

        return response()->json([
            'table' => $table,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Store new tipe
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|unique:tipes,nama'
        ]);

        $tipe = Tipe::create($validated);

        return response()->json([
            'success' => true,
            'data' => $tipe,
            'message' => 'Tipe berhasil ditambahkan'
        ]);
    }

    /**
     * Get tipe data for edit
     */
    public function edit($id)
    {
        $tipe = Tipe::findOrFail($id);
        return response()->json($tipe);
    }

    /**
     * Update tipe
     */
    public function update(Request $request, $id)
    {
        $tipe = Tipe::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:tipes,nama,' . $id
        ]);

        $tipe->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipe berhasil diperbarui',
            'data' => $tipe
        ]);
    }

    /**
     * Delete tipe
     */
    public function destroy($id)
    {
        $tipe = Tipe::findOrFail($id);
        $tipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipe berhasil dihapus'
        ]);
    }

    /**
     * Get all tipes (for search/autocomplete)
     */
    public function getAll()
    {
        $tipes = Tipe::orderBy('nama')->get();
        return response()->json($tipes);
    }

    /**
     * Search tipes by query
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        $tipes = Tipe::where('nama', 'LIKE', "%{$query}%")
            ->orderBy('nama')
            ->get(['id', 'nama']);
        
        return response()->json($tipes);
    }
}
