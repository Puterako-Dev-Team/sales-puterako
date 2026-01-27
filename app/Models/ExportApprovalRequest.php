<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportApprovalRequest extends Model
{
    protected $fillable = [
        'penawaran_id',
        'version_id',
        'requested_by',
        'status',
        'approved_by_supervisor',
        'approved_at_supervisor',
        'approved_by_manager',
        'approved_at_manager',
        'approved_by_direktur',
        'approved_at_direktur',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at_supervisor' => 'datetime',
        'approved_at_manager' => 'datetime',
        'approved_at_direktur' => 'datetime',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function version()
    {
        return $this->belongsTo(PenawaranVersion::class, 'version_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBySupervisor()
    {
        return $this->belongsTo(User::class, 'approved_by_supervisor');
    }

    public function approvedByManager()
    {
        return $this->belongsTo(User::class, 'approved_by_manager');
    }

    public function approvedByDirektor()
    {
        return $this->belongsTo(User::class, 'approved_by_direktur');
    }

    /**
     * Check if all approvals are complete
     */
    public function isFullyApproved()
    {
        return $this->status === 'fully_approved' && 
               $this->approved_by_supervisor && 
               $this->approved_by_manager && 
               $this->approved_by_direktur;
    }
}

