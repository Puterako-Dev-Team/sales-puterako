<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penawaran;
use App\Models\FollowUpSchedule;
use App\Models\FollowUp;
use Illuminate\Support\Facades\Auth; 
use Carbon\Carbon;

class FollowUpScheduleController extends Controller
{
    /**
     * Halaman list follow-up schedules
     */

    public function __construct()
    {
        $this->checkAuthorizedAccess();
    }

    private function checkAuthorizedAccess()
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['supervisor', 'administrator'])) {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }
    public function index(Request $request)
    {
        $query = FollowUpSchedule::with(['penawaran']);

        // Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('penawaran', function($q) use ($search) {
                $q->where('no_penawaran', 'like', "%{$search}%")
                  ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        $query->orderBy($sortColumn, $sortDirection);

        $schedules = $query->paginate(15);

        if ($request->ajax()) {
            return view('followup.table-content', compact('schedules'));
        }

        return view('followup.index', compact('schedules'));
    }

    public function create(Request $request, $penawaranId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'interval_days' => 'required|integer|min:1|max:90',
            'max_reminders' => 'required|integer|min:1|max:20',
        ]);

        $penawaran = Penawaran::findOrFail($penawaranId);

        // Cek apakah sudah ada schedule
        if ($penawaran->followUpSchedule) {
            return response()->json([
                'error' => 'Follow-up schedule already exists. Use update or start new cycle.'
            ], 422);
        }

        $schedule = FollowUpSchedule::create([
            'penawaran_id' => $penawaranId,
            'cycle_number' => 1,
            'current_reminder_count' => 0,
            'max_reminders_per_cycle' => $validated['max_reminders'],
            'interval_days' => $validated['interval_days'],
            'cycle_start_date' => $validated['start_date'],
            'next_reminder_date' => $validated['start_date'],
            'is_active' => true,
            'status' => 'running',
        ]);

        return response()->json([
            'message' => 'Follow-up schedule created successfully!',
            'schedule' => $schedule,
            'progress' => $schedule->getProgressInfo(),
        ]);
    }

    public function startNewCycle(Request $request, $penawaranId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'interval_days' => 'required|integer|min:1|max:90',
            'max_reminders' => 'required|integer|min:1|max:20',
        ]);

        $penawaran = Penawaran::findOrFail($penawaranId);
        $schedule = $penawaran->followUpSchedule;

        if (!$schedule) {
            return response()->json([
                'error' => 'No existing schedule found. Use create endpoint instead.'
            ], 404);
        }

        $schedule->startNewCycle([
            'start_date' => Carbon::parse($validated['start_date']),
            'interval_days' => $validated['interval_days'],
            'max_reminders' => $validated['max_reminders'],
        ]);

        return response()->json([
            'message' => 'New follow-up cycle started!',
            'schedule' => $schedule->fresh(),
            'progress' => $schedule->getProgressInfo(),
        ]);
    }

    public function updateConfig(Request $request, $penawaranId)
    {
        $validated = $request->validate([
            'interval_days' => 'nullable|integer|min:1|max:90',
            'max_reminders' => 'nullable|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        $penawaran = Penawaran::findOrFail($penawaranId);
        $schedule = $penawaran->followUpSchedule;

        if (!$schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        $schedule->updateConfig($validated);

        return response()->json([
            'message' => 'Schedule configuration updated!',
            'schedule' => $schedule->fresh(),
            'progress' => $schedule->getProgressInfo(),
        ]);
    }

    public function pause($penawaranId)
    {
        $penawaran = Penawaran::findOrFail($penawaranId);
        $schedule = $penawaran->followUpSchedule;

        if (!$schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        $schedule->pause();

        return response()->json([
            'message' => 'Follow-up schedule paused',
            'schedule' => $schedule->fresh(),
        ]);
    }

    public function resume($penawaranId)
    {
        $penawaran = Penawaran::findOrFail($penawaranId);
        $schedule = $penawaran->followUpSchedule;

        if (!$schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        $schedule->resume();

        return response()->json([
            'message' => 'Follow-up schedule resumed',
            'schedule' => $schedule->fresh(),
        ]);
    }

    public function show($penawaranId)
    {
        $penawaran = Penawaran::with(['followUpSchedule', 'systemFollowUps'])->findOrFail($penawaranId);
        $schedule = $penawaran->followUpSchedule;

        if (!$schedule) {
            return response()->json([
                'message' => 'No follow-up schedule configured',
                'has_schedule' => false,
            ]);
        }

        $followUpsByCycle = $penawaran->systemFollowUps
            ->groupBy('cycle_number')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nama' => $item->nama,
                        'sequence' => $item->reminder_sequence,
                        'created_at' => $item->created_at->format('d M Y H:i'),
                    ];
                });
            });

        return response()->json([
            'has_schedule' => true,
            'schedule' => $schedule,
            'progress' => $schedule->getProgressInfo(),
            'history_by_cycle' => $followUpsByCycle,
        ]);
    }
}