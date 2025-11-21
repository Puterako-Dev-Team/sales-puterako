<?php
// app/Models/Rekap.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekap extends Model
{
    use HasFactory;

    protected $fillable = [
        'penawaran_id',
        'user_id',
        'nama',
        'no_penawaran',
        'nama_perusahaan'
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(RekapItem::class, 'rekap_id', 'id');
    }
}