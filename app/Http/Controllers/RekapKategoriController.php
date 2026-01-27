<?php

namespace App\Http\Controllers;

use App\Models\RekapKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapKategoriController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'administrator') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        $kategoris = RekapKategori::orderBy('nama')->paginate(15);
        return view('rekap-kategori.index', compact('kategoris'));
    }

    public function filter(Request $request)
    {
        if (Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized. Administrator access required.');
        }
        
        $query = RekapKategori::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('nama', 'like', "%{$q}%");
        }

        $sort = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');
        $allowed = ['nama', 'created_at'];
        if (!in_array($sort, $allowed)) $sort = 'nama';
        if (!in_array(strtolower($direction), ['asc', 'desc'])) $direction = 'asc';

        $kategoris = $query->orderBy($sort, $direction)->paginate(15)->appends($request->query());

        $table = view('rekap-kategori.table-content', ['kategoris' => $kategoris])->render();
        $pagination = view('components.paginator', ['paginator' => $kategoris->withPath(route('rekap-kategori.filter'))])->render();

        return response()->json([
            'table' => $table,
            'pagination' => $pagination,
        ]);
    }

    public function create()
    {
        if (Auth::user()->role !== 'administrator') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        return view('rekap-kategori.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:rekap_kategoris,nama'
        ], [
            'nama.required' => 'Nama kategori harus diisi',
            'nama.unique' => 'Nama kategori sudah ada'
        ]);

        $kategori = RekapKategori::create($validated);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $kategori
        ]);
    }

    public function edit(RekapKategori $rekapKategori)
    {
        if (Auth::user()->role !== 'administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($rekapKategori);
    }

    public function update(Request $request, RekapKategori $rekapKategori)
    {
        if (Auth::user()->role !== 'administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:rekap_kategoris,nama,' . $rekapKategori->id
        ], [
            'nama.required' => 'Nama kategori harus diisi',
            'nama.unique' => 'Nama kategori sudah ada'
        ]);

        $rekapKategori->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'data' => $rekapKategori
        ]);
    }

    public function destroy(RekapKategori $rekapKategori)
    {
        if (Auth::user()->role !== 'administrator') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($rekapKategori->items()->exists()) {
            return response()->json(['message' => 'Kategori tidak bisa dihapus karena sudah digunakan'], 422);
        }

        $rekapKategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
