<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenawaranSupportingDocument extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'penawaran_supporting_documents';

    protected $fillable = [
        'id_penawaran',
        'file_path',
        'original_filename',
        'file_type',
        'file_size',
        'uploaded_by',
        'notes',
    ];

    public function penawaran()
    {
        return $this->belongsTo(Penawaran::class, 'id_penawaran', 'id_penawaran');
    }
}
