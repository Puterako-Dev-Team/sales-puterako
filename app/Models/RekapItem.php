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
        'nama_item',
        'detail'
    ];

    protected $casts = [
        'detail' => 'array', // Otomatis decode/encode JSON
    ];

    public function rekap()
    {
        return $this->belongsTo(Rekap::class, 'rekap_id', 'id');
    }

    public function kategori()
    {
        return $this->belongsTo(RekapKategori::class, 'rekap_kategori_id', 'id');
    }
}