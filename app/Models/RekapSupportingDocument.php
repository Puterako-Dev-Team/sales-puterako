<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapSupportingDocument extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'rekap_supporting_documents';

    protected $fillable = [
        'id_rekap',
        'file_path',
        'original_filename',
        'file_type',
        'file_size',
        'uploaded_by',
        'notes',
    ];

    public function rekap()
    {
        return $this->belongsTo(Rekap::class, 'id_rekap', 'id');
    }
}
