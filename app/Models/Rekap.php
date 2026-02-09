<?php
// app/Models/Rekap.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rekap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'penawaran_id',
        'nama',
        'no_penawaran',
        'nama_perusahaan',
        'status',
        'deleted_at',
        'imported_by',
        'imported_at',
        'imported_into_penawaran_id'
    ];

    protected $dates = ['deleted_at', 'imported_at'];

    protected $attributes = [
        'status' => 'pending'
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'penawaran_id', 'id_penawaran');
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
    public function importedIntoPenawaran()
    {
        return $this->belongsTo(Penawaran::class, 'imported_into_penawaran_id', 'id_penawaran');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(RekapItem::class, 'rekap_id', 'id');
    }

    public function survey()
    {
        return $this->hasOne(RekapSurvey::class, 'rekap_id', 'id');
    }

    public function surveys()
    {
        return $this->hasMany(RekapSurvey::class, 'rekap_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(RekapVersion::class, 'rekap_id', 'id');
    }

    /**
     * Get the latest version number.
     */
    public function getLatestVersionNumber(): int
    {
        return $this->versions()->max('version') ?? -1;
    }

    /**
     * Get the latest version.
     */
    public function latestVersion()
    {
        return $this->versions()->orderByDesc('version')->first();
    }
}