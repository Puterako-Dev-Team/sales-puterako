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
