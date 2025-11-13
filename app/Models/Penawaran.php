<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penawaran extends Model
{
    use SoftDeletes;
    
    protected $primaryKey = 'id_penawaran';
    
    protected $fillable = [
        'perihal',
        'nama_perusahaan',
        'pic_perusahaan',
        'user_id',
        'no_penawaran',
        'lokasi',
        'tiket',
        'is_best_price',
        'best_price',
        'total',
        'ppn_persen',
        'ppn_nominal',
        'grand_total',
        'note',
        'status',
        'deleted_at',
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(PenawaranDetail::class, 'id_penawaran');
    }
    public function jasaDetails()
    {
        return $this->hasMany(JasaDetail::class, 'id_penawaran');
    }

    public function versions()
    {
        return $this->hasMany(PenawaranVersion::class, 'penawaran_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'penawaran_id', 'id_penawaran')
                    ->orderBy('created_at', 'desc');
    }

    public function systemFollowUps()
    {
        return $this->hasMany(FollowUp::class, 'penawaran_id', 'id_penawaran')
                    ->where('is_system_generated', true)
                    ->orderBy('created_at', 'desc');
    }
}
