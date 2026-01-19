<?php

namespace App\Http\Controllers;

use App\Models\ExportApprovalRequest;
use App\Models\Penawaran;
use App\Models\PenawaranVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportApprovalController extends Controller
{
    /**
     * Show approval requests list for approver roles.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $allowedRoles = ['supervisor', 'manager', 'direktur'];

        if (!in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Hanya supervisor, manager, atau direktur yang dapat mengakses halaman ini');
        }

        $baseQuery = ExportApprovalRequest::with([
            'penawaran',
            'version',
            'requestedBy'
        ]);

        // Apply filters
        if ($request->filled('tanggal_dari')) {
            $baseQuery->whereDate('requested_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('no_penawaran')) {
            $baseQuery->whereHas('penawaran', function($q) use ($request) {
                $q->where('no_penawaran', 'like', '%' . $request->no_penawaran . '%');
            });
        }

        if ($request->filled('nama_perusahaan')) {
            $baseQuery->whereHas('penawaran', function($q) use ($request) {
                $q->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
            });
        }

        if ($request->filled('pic_admin')) {
            $baseQuery->whereHas('requestedBy', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pic_admin . '%');
            });
        }

        // Handle AJAX requests for filtering
        if ($request->ajax()) {
            // Pending per role
            $pendingQuery = clone $baseQuery;
            if ($user->role === 'supervisor') {
                $pendingQuery->whereNull('approved_by_supervisor');
            } elseif ($user->role === 'manager') {
                $pendingQuery->whereNotNull('approved_by_supervisor')
                    ->whereNull('approved_by_manager');
            } elseif ($user->role === 'direktur') {
                $pendingQuery->whereNotNull('approved_by_manager')
                    ->whereNull('approved_by_direktur');
            }

            // Apply sorting
            $sortColumn = $request->get('sort', 'requested_at');
            $sortDirection = $request->get('direction', 'desc');
            
            $allowedSorts = ['requested_at', 'no_penawaran', 'nama_perusahaan', 'status'];
            if (!in_array($sortColumn, $allowedSorts)) {
                $sortColumn = 'requested_at';
            }
            
            if ($sortColumn === 'no_penawaran') {
                $pendingQuery->join('penawarans', 'export_approval_requests.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->orderBy('penawarans.no_penawaran', $sortDirection);
            } elseif ($sortColumn === 'nama_perusahaan') {
                $pendingQuery->join('penawarans', 'export_approval_requests.penawaran_id', '=', 'penawarans.id_penawaran')
                    ->orderBy('penawarans.nama_perusahaan', $sortDirection);
            } else {
                $pendingQuery->orderBy("export_approval_requests.$sortColumn", $sortDirection);
            }

            $requests = $pendingQuery->paginate(10)->appends($request->query());
            
            $table = view('penawaran.approval-table', [
                'requests' => $requests,
                'userRole' => $user->role,
            ])->render();
            
            $pagination = view('components.paginator', ['paginator' => $requests])->render();
            
            // Generate filter info
            $activeFilters = [];
            if ($request->tanggal_dari) $activeFilters[] = 'Tanggal';
            if ($request->no_penawaran) $activeFilters[] = 'No Penawaran';
            if ($request->nama_perusahaan) $activeFilters[] = 'Perusahaan';
            if ($request->pic_admin) $activeFilters[] = 'PIC';
            
            $info = '';
            if (count($activeFilters) > 0) {
                $info = view('penawaran.approval-filter-info', [
                    'count' => $requests->count(),
                    'total' => ExportApprovalRequest::count(),
                    'filters' => implode(', ', $activeFilters),
                    'currentPage' => $requests->currentPage(),
                    'lastPage' => $requests->lastPage(),
                    'from' => $requests->firstItem(),
                    'to' => $requests->lastItem()
                ])->render();
            }
            
            return response()->json([
                'table' => $table,
                'pagination' => $pagination,
                'info' => $info
            ]);
        }

        // Pending per role
        $pendingQuery = clone $baseQuery;
        if ($user->role === 'supervisor') {
            $pendingQuery->whereNull('approved_by_supervisor');
        } elseif ($user->role === 'manager') {
            $pendingQuery->whereNotNull('approved_by_supervisor')
                ->whereNull('approved_by_manager');
        } elseif ($user->role === 'direktur') {
            $pendingQuery->whereNotNull('approved_by_manager')
                ->whereNull('approved_by_direktur');
        }

        // Apply sorting
        $sortColumn = $request->get('sort', 'requested_at');
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSorts = ['requested_at', 'no_penawaran', 'nama_perusahaan', 'status'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'requested_at';
        }
        
        if ($sortColumn === 'no_penawaran') {
            $pendingQuery->join('penawarans', 'export_approval_requests.penawaran_id', '=', 'penawarans.id_penawaran')
                ->orderBy('penawarans.no_penawaran', $sortDirection);
        } elseif ($sortColumn === 'nama_perusahaan') {
            $pendingQuery->join('penawarans', 'export_approval_requests.penawaran_id', '=', 'penawarans.id_penawaran')
                ->orderBy('penawarans.nama_perusahaan', $sortDirection);
        } else {
            $pendingQuery->orderBy("export_approval_requests.$sortColumn", $sortDirection);
        }

        $requests = $pendingQuery->paginate(10)->appends($request->query());

        // Get PIC Admin options for filter dropdown
        $picAdmins = ExportApprovalRequest::with('requestedBy')
            ->whereHas('requestedBy')
            ->selectRaw('DISTINCT users.name')
            ->join('users', 'export_approval_requests.requested_by', '=', 'users.id')
            ->orderBy('users.name')
            ->pluck('users.name');

        return view('penawaran.approval-list', [
            'requests' => $requests,
            'userRole' => $user->role,
            'picAdmins' => $picAdmins,
        ]);
    }

    /**
     * Submit verification request for export PDF
     */
    public function submitVerificationRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'penawaran_id' => 'required|integer|exists:penawarans,id_penawaran',
                'version_id' => 'required|integer|exists:penawaran_versions,id',
            ]);

            $user = Auth::user();

            if ($user->role !== 'staff') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya staff yang dapat request verifikasi export PDF'
                ], 403);
            }

            $penawaran = Penawaran::find($validated['penawaran_id']);
            $version = PenawaranVersion::find($validated['version_id']);

            if (!$penawaran || !$version) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penawaran atau versi tidak ditemukan'
                ], 404);
            }

            if (empty($version->jasa_ringkasan) || empty($version->notes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ringkasan Jasa dan Notes harus diisi'
                ], 422);
            }

            $existingRequest = ExportApprovalRequest::where('version_id', $version->id)
                ->whereIn('status', ['pending', 'supervisor_approved', 'manager_approved'])
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'notify' => [
                        'type' => 'error',
                        'title' => 'Gagal',
                        'message' => 'Sudah ada permintaan verifikasi untuk versi ini'
                    ]
                ], 409);
            }


            $approvalRequest = ExportApprovalRequest::create([
                'penawaran_id' => $penawaran->id_penawaran,
                'version_id' => $version->id,
                'requested_by' => $user->id,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            $approvers = User::whereIn('role', ['supervisor', 'manager', 'direktur'])->get();

            // Contoh notifikasi (opsional)
            // Notification::send($approvers, new ExportApprovalRequestNotification($approvalRequest));

            return response()->json([
                'success' => true,
                'message' => 'Permintaan verifikasi export PDF telah dikirim',
                'data' => $approvalRequest
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. Silakan periksa kembali.'
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Export approval error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan. Silakan coba lagi.'
            ], 500);
        }
    }


    /**
     * Approve verification request (Supervisor step 1)
     */
    public function approveBySupervisor(Request $request, $requestId)
    {
        $user = Auth::user();

        // Only supervisors can approve in step 1
        if ($user->role !== 'supervisor') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya supervisor yang dapat approve pada tahap pertama'
            ], 403);
        }

        $approvalRequest = ExportApprovalRequest::find($requestId);
        if (!$approvalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan verifikasi tidak ditemukan'
            ], 404);
        }

        // Check if already approved by supervisor
        if ($approvalRequest->approved_by_supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah disetujui oleh supervisor'
            ], 409);
        }

        // Update supervisor approval
        $approvalRequest->update([
            'approved_by_supervisor' => $user->id,
            'approved_at_supervisor' => now(),
            'status' => 'supervisor_approved',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Persetujuan supervisor berhasil. Menunggu persetujuan manager...'
        ]);
    }

    /**
     * Approve verification request (Manager step 2)
     */
    public function approveByManager(Request $request, $requestId)
    {
        $user = Auth::user();

        // Only managers can approve in step 2
        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya manager yang dapat approve pada tahap kedua'
            ], 403);
        }

        $approvalRequest = ExportApprovalRequest::find($requestId);
        if (!$approvalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan verifikasi tidak ditemukan'
            ], 404);
        }

        // Check if supervisor approved first
        if (!$approvalRequest->approved_by_supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'Belum disetujui oleh supervisor'
            ], 409);
        }

        // Check if already approved by manager
        if ($approvalRequest->approved_by_manager) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah disetujui oleh manager'
            ], 409);
        }

        // Update manager approval
        $approvalRequest->update([
            'approved_by_manager' => $user->id,
            'approved_at_manager' => now(),
            'status' => 'manager_approved',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Persetujuan manager berhasil. Menunggu persetujuan direktur...'
        ]);
    }

    /**
     * Approve verification request (Direktur step 3 - final approval)
     */
    public function approveByDirektor(Request $request, $requestId)
    {
        $user = Auth::user();

        // Only direkturs can approve in step 3
        if ($user->role !== 'direktur') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya direktur yang dapat approve pada tahap ketiga'
            ], 403);
        }

        $approvalRequest = ExportApprovalRequest::find($requestId);
        if (!$approvalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan verifikasi tidak ditemukan'
            ], 404);
        }

        // Check if supervisor and manager approved first
        if (!$approvalRequest->approved_by_supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'Belum disetujui oleh supervisor'
            ], 409);
        }

        if (!$approvalRequest->approved_by_manager) {
            return response()->json([
                'success' => false,
                'message' => 'Belum disetujui oleh manager'
            ], 409);
        }

        // Check if already approved by direktur
        if ($approvalRequest->approved_by_direktur) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah disetujui oleh direktur'
            ], 409);
        }

        // Update direktur approval - FINAL APPROVAL
        $approvalRequest->update([
            'approved_by_direktur' => $user->id,
            'approved_at_direktur' => now(),
            'status' => 'fully_approved',
        ]);

        // Update penawaran_version to allow export
        $version = PenawaranVersion::find($approvalRequest->version_id);
        $version->update([
            'export_approval_status' => 'approved',
        ]);

        return response()->json([
            'success' => true,
            'message' => '✅ Persetujuan lengkap! Staff sekarang dapat export PDF'
        ]);
    }
}
