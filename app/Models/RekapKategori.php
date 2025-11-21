<?php
// app/Models/RekapKategori.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapKategori extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function items()
    {
        return $this->hasMany(RekapItem::class, 'rekap_kategori_id', 'id');
    }
}