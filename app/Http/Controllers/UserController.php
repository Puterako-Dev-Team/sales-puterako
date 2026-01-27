<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Method untuk check administrator access
    private function checkAdminAccess()
    {
        if (!Auth::check() || Auth::user()->role !== 'administrator') {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdminAccess();
        
        $query = User::query();
        
        // Show trashed users if requested
        if ($request->get('show_deleted') === '1') {
            $query->onlyTrashed();
        } elseif ($request->get('show_deleted') === 'all') {
            $query->withTrashed();
        }
        // Default: show only active users (no withTrashed needed)
        
        // Filter berdasarkan parameter
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('departemen', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('kantor')) {
            $query->where('kantor', $request->kantor);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate(15);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('users.table-content', compact('users'))->render(),
                'pagination' => $users->links('pagination::bootstrap-4')->render(),
                'info' => "Menampilkan " . $users->firstItem() . " sampai " . $users->lastItem() . " dari " . $users->total() . " data"
            ]);
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->checkAdminAccess();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:administrator,direktur,manager,supervisor,staff',
            'departemen' => 'required|string|max:100',
            'kantor' => 'required|string|max:100',
            'nohp' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'departemen' => $request->departemen,
            'kantor' => $request->kantor,
            'nohp' => $request->nohp,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'User berhasil ditambahkan'
                ]
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function show(User $user)
    {
        $this->checkAdminAccess();
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->checkAdminAccess();
        
        if (request()->ajax()) {
            return response()->json($user);
        }
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:administrator,direktur,manager,supervisor,staff',
            'departemen' => 'required|string|max:100',
            'kantor' => 'required|string|max:100',
            'nohp' => 'nullable|string|max:20',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'departemen' => $request->departemen,
            'kantor' => $request->kantor,
            'nohp' => $request->nohp,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        if ($request->ajax()) {
            return response()->json([
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'User berhasil diupdate'
                ]
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate');
    }

    public function destroy(User $user)
    {
        $this->checkAdminAccess();
        
        // Prevent deleting self
        if ($user->id === Auth::id()) {
            return response()->json([
                'notify' => [
                    'type' => 'error',
                    'title' => 'Gagal',
                    'message' => 'Tidak dapat menghapus akun sendiri'
                ]
            ], 400);
        }

        $user->delete(); // This will soft delete

        return response()->json([
            'notify' => [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'User berhasil dihapus (soft delete)'
            ]
        ]);
    }

    public function restore(User $user)
    {
        $this->checkAdminAccess();
        
        $user->restore();

        return response()->json([
            'notify' => [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'User berhasil dipulihkan'
            ]
        ]);
    }

    public function forceDelete(User $user)
    {
        $this->checkAdminAccess();
        
        // Prevent force deleting self
        if ($user->id === Auth::id()) {
            return response()->json([
                'notify' => [
                    'type' => 'error',
                    'title' => 'Gagal',
                    'message' => 'Tidak dapat menghapus permanen akun sendiri'
                ]
            ], 400);
        }

        $user->forceDelete(); // This will permanently delete

        return response()->json([
            'notify' => [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'User berhasil dihapus permanen'
            ]
        ]);
    }

    public function permissions()
    {
        $this->checkAdminAccess();
        
        $users = User::orderBy('role')->orderBy('name')->get();
        return view('users.permissions', compact('users'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        $this->checkAdminAccess();
        
        $request->validate([
            'role' => 'required|in:administrator,direktur,manager,supervisor,staff',
        ]);

        $user->update(['role' => $request->role]);

        return response()->json([
            'notify' => [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'Hak akses berhasil diupdate'
            ]
        ]);
    }

    public function filter(Request $request)
    {
        $this->checkAdminAccess();
        return $this->index($request);
    }

    public function profile()
    {
        return view('users.profile', [
            'user' => Auth::user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'departemen' => 'required|string|max:100',
            'kantor' => 'required|string|max:100',
            'nohp' => 'nullable|string|max:20',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'departemen' => $request->departemen,
            'kantor' => $request->kantor,
            'nohp' => $request->nohp,
        ];

        $user->update($updateData);

        if ($request->ajax()) {
            return response()->json([
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Data diri berhasil diupdate'
                ]
            ]);
        }

        return redirect()->route('profile')->with('success', 'Data diri berhasil diupdate');
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            if ($request->ajax()) {
                return response()->json([
                    'notify' => [
                        'type' => 'error',
                        'title' => 'Gagal',
                        'message' => 'Password saat ini tidak benar'
                    ]
                ], 400);
            }
            return back()->withErrors(['current_password' => 'Password saat ini tidak benar']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'notify' => [
                    'type' => 'success',
                    'title' => 'Berhasil',
                    'message' => 'Password berhasil diubah'
                ]
            ]);
        }

        return redirect()->route('profile')->with('success', 'Password berhasil diubah');
    }
}