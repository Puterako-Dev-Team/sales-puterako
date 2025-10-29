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
        'jasa_profit_percent',
        'jasa_profit_value',
        'jasa_pph_percent',
        'jasa_pph_value',
        'jasa_bpjsk_percent',
        'jasa_bpjsk_value',
        'jasa_grand_total',
        'jasa_ringkasan'

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
