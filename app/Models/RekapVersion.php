<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekapVersion extends Model
{
    protected $fillable = [
        'rekap_id',
        'version',
        'notes',
        'status',
    ];

    /**
     * Get the rekap that owns this version.
     */
    public function rekap(): BelongsTo
    {
        return $this->belongsTo(Rekap::class);
    }

    /**
     * Get items for this version.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RekapItem::class, 'version_id');
    }

    /**
     * Get surveys for this version.
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(RekapSurvey::class, 'version_id');
    }
}
