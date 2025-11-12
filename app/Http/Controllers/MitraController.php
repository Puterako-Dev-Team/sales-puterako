<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mitra;

class MitraController extends Controller
{
    public function index(Request $request)
    {
        $query = Mitra::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nama_mitra', 'like', "%{$q}%")
                  ->orWhere('provinsi', 'like', "%{$q}%")
                  ->orWhere('kota', 'like', "%{$q}%")
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        $sort = $request->get('sort', 'id_mitra');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id_mitra','nama_mitra','provinsi','kota','alamat','created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id_mitra';
        if (!in_array(strtolower($direction), ['asc','desc'])) $direction = 'asc';

        $mitras = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        return view('mitra.list', compact('mitras'));
    }

    public function filter(Request $request)
    {
        $query = Mitra::query();

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('nama_mitra', 'like', "%{$q}%")
                  ->orWhere('provinsi', 'like', "%{$q}%")
                  ->orWhere('kota', 'like', "%{$q}%")
                  ->orWhere('alamat', 'like', "%{$q}%");
            });
        }

        $sort = $request->get('sort', 'id_mitra');
        $direction = $request->get('direction', 'asc');
        $allowed = ['id_mitra','nama_mitra','provinsi','kota','alamat','created_at'];
        if (!in_array($sort, $allowed)) $sort = 'id_mitra';
        if (!in_array(strtolower($direction), ['asc','desc'])) $direction = 'asc';

        $mitras = $query->orderBy($sort, $direction)->paginate(10)->appends($request->query());

        $table = view('mitra.table-content', ['mitras' => $mitras])->render();
        $pagination = view('penawaran.pagination', ['paginator' => $mitras])->render();

        return response()->json([
            'table' => $table,
            'pagination' => $pagination,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_mitra' => 'required|string|max:255',
            'provinsi'        => 'nullable|string|max:150',
            'kota'            => 'required|string|max:150',
            'alamat'          => 'nullable|string',
        ]);

        Mitra::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('mitra.list')->with('success', 'Mitra ditambahkan');
    }

    public function edit($id)
    {
        $mitra = Mitra::findOrFail($id);
        return response()->json($mitra);
    }

    public function update(Request $request, $id)
    {
        $mitra = Mitra::findOrFail($id);
        $data = $request->validate([
            'nama_mitra' => 'required|string|max:255',
            'provinsi'        => 'nullable|string|max:150',
            'kota'            => 'required|string|max:150',
            'alamat'          => 'nullable|string',
        ]);
        $mitra->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success','Mitra diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $mitra = Mitra::findOrFail($id);
        $mitra->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success','Mitra dihapus');
    }
}