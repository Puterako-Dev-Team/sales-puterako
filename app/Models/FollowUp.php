<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class FollowUp extends Model
{
    use HasFactory;

    protected $table = 'follow_ups';

    protected $fillable = [
        'penawaran_id',
        'follow_up_schedule_id',
        'nama',
        'deskripsi',
        'hasil_progress',
        'jenis',
        'status',
        'is_system_generated',
        'cycle_number',
        'reminder_sequence',
    ];

    protected $casts = [
        'is_system_generated' => 'boolean',
        'cycle_number' => 'integer',
        'reminder_sequence' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }
    public function schedule()
    {
        return $this->belongsTo(FollowUpSchedule::class, 'follow_up_schedule_id');
    }

    public function scopeSystemGenerated($query)
    {
        return $query->where('is_system_generated', true);
    }
    public function scopeManual($query)
    {
        return $query->where('is_system_generated', false);
    }

    public function scopeForCycle($query, int $cycleNumber)
    {
        return $query->where('cycle_number', $cycleNumber);
    }
}