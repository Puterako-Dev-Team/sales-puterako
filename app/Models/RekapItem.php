<?php
// app/Models/RekapItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rekap_id',
        'rekap_kategori_id',
        'tipes_id',
        'nama_area',
        'jumlah',
        'satuan_id'
    ];

    protected $casts = [];
    
    protected $with = ['tipe', 'kategori', 'satuan'];

    public function rekap()
    {
        return $this->belongsTo(Rekap::class, 'rekap_id', 'id');
    }

    public function kategori()
    {
        return $this->belongsTo(RekapKategori::class, 'rekap_kategori_id', 'id');
    }

    public function tipe()
    {
        return $this->belongsTo(Tipe::class, 'tipes_id', 'id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id', 'id');
    }
}
