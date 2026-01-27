<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_libur',
        'nama_libur',
        'libur_nasional',
    ];

    protected $casts = [
        'tanggal_libur' => 'date',
        'libur_nasional' => 'boolean',
    ];

    public static function isHoliday($date)
    {
        $holiday = self::whereDate('tanggal_libur', $date)->first();
        
        if (!$holiday) {
            return false;
        }

        return $holiday->libur_nasional;
    }

    public static function getUpcoming($limit = 10)
    {
        return self::where('tanggal_libur', '>=', now())
            ->orderBy('tanggal_libur')
            ->limit($limit)
            ->get();
    }
}
