<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranVersion extends Model
{

    protected $fillable = [
        'penawaran_id',
        'version',
        'notes',
        'status',
        'grand_total',
        'penawaran_total_awal',
        'jasa_total_awal',
        'jasa_profit_percent',
        'jasa_profit_value',
        'jasa_pph_percent',
        'jasa_pph_value',
        'jasa_bpjsk_percent',
        'jasa_bpjsk_value',
        'jasa_grand_total',
        'jasa_use_bpjs',
        'jasa_use_pembulatan',
        'jasa_pembulatan_final',
        'jasa_ringkasan',
        'is_best_price',
        'best_price',
        'is_diskon',
        'diskon',
        'ppn_persen',
        'ppn_nominal',

    ];
    public function details()
    {
        return $this->hasMany(PenawaranDetail::class, 'version_id');
    }

    public function jasa()
    {
        return $this->hasOne(Jasa::class, 'version_id');
    }
    public function jasaDetails()
    {
        return $this->hasMany(JasaDetail::class, 'version_id');
    }
}
