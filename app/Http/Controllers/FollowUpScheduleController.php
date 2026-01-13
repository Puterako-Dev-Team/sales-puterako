<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penawaran;
use App\Models\FollowUpSchedule;
use App\Models\FollowUp;
use Carbon\Carbon;

class FollowUpScheduleController extends Controller
{
    public function create(Request $request, $penawaranId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'interval_days' => 'required|integer|min:1|max:90',
            'max_reminders' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
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
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Follow-up schedule created successfully!',
            'schedule' => $schedule,
            'progress' => $schedule->getProgressInfo(),
        ]);
    }

    /**
     * Start cycle baru (setelah cycle selesai)
    */
    public function startNewCycle(Request $request, $penawaranId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'interval_days' => 'required|integer|min:1|max:90',
            'max_reminders' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
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
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'New follow-up cycle started!',
            'schedule' => $schedule->fresh(),
            'progress' => $schedule->getProgressInfo(),
        ]);
    }

    /**
     * Update konfigurasi (interval/max reminders) tanpa reset counter
    */
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
    /**
     * Pause auto reminder
     */
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

    /**
     * Resume auto reminder
     */
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

    /**
     * Get schedule info & history
     */
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
