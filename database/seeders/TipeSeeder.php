<?php

namespace Database\Seeders;

use App\Models\Tipe;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipes = [
            'IR9042G',
            'RG-NBS3100-24GT4SFP-P',
            'C9300-24UX-A',
            'WS-C3850-24P-E',
            'ASR1002-HX',
            'ISR4351-3AX4SH',
            'N9K-C93180YC-EX',
            'Firepower 2120',
        ];

        foreach ($tipes as $nama) {
            Tipe::firstOrCreate(
                ['nama' => $nama],
                ['nama' => $nama]
            );
        }
    }
}
