<?php

namespace App\Http\Controllers;

use App\Models\Tipe;
use Illuminate\Http\Request;

class TipeController extends Controller
{
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

    /**
     * Create new tipe
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
}
