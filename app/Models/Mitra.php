<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    protected $table = 'mitras';
    protected $primaryKey = 'id_mitra';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['nama_mitra', 'provinsi', 'kota','alamat'];
}