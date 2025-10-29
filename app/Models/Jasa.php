<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jasa extends Model
{
    protected $primaryKey = 'id_jasa';
    protected $fillable = [
        'id_penawaran',
        'total_awal',
        'profit_percent',
        'profit_value',
        'pph_percent',
        'pph_value',
        'bpjsk_percent',
        'bpjsk_value',
        'grand_total',
        'ringkasan',
        'version_id',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran');
    }

    public function details()
    {
        return $this->hasMany(JasaDetail::class, 'id_jasa');
    }
    public function version()
    {
        return $this->belongsTo(PenawaranVersion::class, 'version_id');
    }
}
