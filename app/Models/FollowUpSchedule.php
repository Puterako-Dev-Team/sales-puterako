<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class FollowUpSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'penawaran_id',
        'cycle_number',
        'current_reminder_count',
        'max_reminders_per_cycle',
        'interval_days',
        'cycle_start_date',
        'next_reminder_date',
        'is_active',
        'status',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'cycle_number' => 'integer',
        'current_reminder_count' => 'integer',
        'max_reminders_per_cycle' => 'integer',
        'interval_days' => 'integer',
        'is_active' => 'boolean',
        'cycle_start_date' => 'date',
        'next_reminder_date' => 'date',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'follow_up_schedule_id');
    }

    public function currentCycleFollowUps()
    {
        return $this->followUps()
                    ->where('cycle_number', $this->cycle_number)
                    ->orderBy('created_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('status', 'running');
    }

    public function scopeDueForReminder($query)
    {
        return $query->active()
                     ->whereNotNull('next_reminder_date')
                     ->where('next_reminder_date', '<=', Carbon::now())
                     ->whereColumn('current_reminder_count', '<', 'max_reminders_per_cycle');
    }

    public function canSendReminder(): bool
    {
        return $this->is_active
            && $this->status === 'running'
            && $this->current_reminder_count < $this->max_reminders_per_cycle
            && $this->next_reminder_date
            && $this->next_reminder_date <= Carbon::now();
    }

    public function incrementReminder()
    {
        $this->increment('current_reminder_count');
        $this->update([
            'last_reminder_sent_at' => Carbon::now(),
        ]);

        // Cek apakah cycle sudah selesai
        if ($this->current_reminder_count >= $this->max_reminders_per_cycle) {
            $this->completeCycle();
        } else {
            $this->scheduleNextReminder();
        }
    }

    public function scheduleNextReminder()
    {
        $this->update([
            'next_reminder_date' => Carbon::now()->addDays($this->interval_days),
        ]);
    }

    public function completeCycle()
    {
        $this->update([
            'status' => 'completed',
            'is_active' => false,
            'next_reminder_date' => null,
        ]);
    }

    public function startNewCycle(array $config = [])
    {
        $this->update([
            'cycle_number' => $this->cycle_number + 1,
            'current_reminder_count' => 0,
            'max_reminders_per_cycle' => $config['max_reminders'] ?? 5,
            'interval_days' => $config['interval_days'] ?? 7,
            'cycle_start_date' => $config['start_date'] ?? Carbon::now(),
            'next_reminder_date' => $config['start_date'] ?? Carbon::now(),
            'is_active' => true,
            'status' => 'running',
            'notes' => $config['notes'] ?? null,
        ]);
    }

    public function pause()
    {
        $this->update([
            'status' => 'paused',
            'is_active' => false,
        ]);
    }

    public function resume()
    {
        if ($this->current_reminder_count < $this->max_reminders_per_cycle) {
            $this->update([
                'status' => 'running',
                'is_active' => true,
                'next_reminder_date' => Carbon::now()->addDays($this->interval_days),
            ]);
        }
    }

    public function updateConfig(array $config)
    {
        $updates = [];
        
        if (isset($config['interval_days'])) {
            $updates['interval_days'] = $config['interval_days'];
            $updates['next_reminder_date'] = Carbon::now()->addDays($config['interval_days']);
        }
        
        if (isset($config['max_reminders'])) {
            $updates['max_reminders_per_cycle'] = $config['max_reminders'];
        }

        if (isset($config['notes'])) {
            $updates['notes'] = $config['notes'];
        }

        $this->update($updates);
    }

    public function getProgressInfo(): array
    {
        return [
            'cycle_number' => $this->cycle_number,
            'current' => $this->current_reminder_count,
            'max' => $this->max_reminders_per_cycle,
            'remaining' => $this->max_reminders_per_cycle - $this->current_reminder_count,
            'percentage' => round(($this->current_reminder_count / $this->max_reminders_per_cycle) * 100, 1),
            'interval_days' => $this->interval_days,
            'next_date' => $this->next_reminder_date?->format('d M Y'),
            'status' => $this->status,
            'is_active' => $this->is_active,
            'can_send' => $this->canSendReminder(),
        ];
    }
}