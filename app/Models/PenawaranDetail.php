<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranDetail extends Model
{
    protected $primaryKey = 'id_penawaran_detail';
    protected $fillable = [
        'id_penawaran',
        'area',
        'nama_section',
        'no',
        'tipe',
        'tipe_id',
        'tipe_name',
        'deskripsi',
        'qty',
        'satuan',
        'harga_satuan',
        'harga_total',
        'hpp',
        'profit',
        'is_mitra',
        'is_judul',
        'color_code',
        'added_cost',
        'delivery_time',
        'comments',
        'version_id',
        'order',
    ];

    protected $casts = [
        'comments' => 'array',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran');
    }

    public function tipe()
    {
        return $this->belongsTo(Tipe::class, 'tipe_id');
    }

    public function version()
    {
        return $this->belongsTo(PenawaranVersion::class, 'version_id');
    }
}
