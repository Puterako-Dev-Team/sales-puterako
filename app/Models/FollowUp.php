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
        'nama',
        'deskripsi',
        'hasil_progress',
        'jenis',
        'status',
        'is_system_generated'
    ];

    protected $casts = [
        'is_system_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function scopeSystemGenerated($query)
    {
        return $query->where('is_system_generated', true);
    }
}