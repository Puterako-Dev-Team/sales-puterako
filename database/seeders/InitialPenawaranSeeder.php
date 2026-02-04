<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Penawaran;
use App\Models\PenawaranVersion;
use Carbon\Carbon;

class InitialPenawaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Initialize penawaran for production staff
     */
    public function run()
    {
        $this->command->info('Starting Initial Penawaran Seeder...');
        
        // Data untuk setiap staff
        $staffData = [
            [
                'user_id' => 1,
                'name' => 'Ria',
                'count' => 14
            ],
            [
                'user_id' => 2,
                'name' => 'Diyah',
                'count' => 13
            ],
            [
                'user_id' => 3,
                'name' => 'Dinda',
                'count' => 21
            ],
        ];

        $now = Carbon::now();
        $month = $now->format('m'); // 02
        $year = $now->format('Y');  // 2026
        $monthRoman = $this->convertToRoman((int)$month); // II

        foreach ($staffData as $staff) {
            $userId = $staff['user_id'];
            $name = $staff['name'];
            $count = $staff['count'];

            $this->command->info("\nCreating {$count} penawaran for {$name} (User ID: {$userId})...");

            for ($i = 1; $i <= $count; $i++) {
                // Format sequence dengan padding 3 digit
                $paddedSequence = str_pad($i, 3, '0', STR_PAD_LEFT);
                
                // Format: PIB/SS-SBY/JK/{user_id}-{sequence}/{month}/{year}
                $noPenawaran = "PIB/SS-SBY/JK/{$userId}-{$paddedSequence}/{$monthRoman}/{$year}";

                // Cek apakah sudah ada
                $exists = Penawaran::where('no_penawaran', $noPenawaran)->exists();

                if (!$exists) {
                    // Create penawaran
                    $penawaran = Penawaran::create([
                        'user_id' => $userId,
                        'no_penawaran' => $noPenawaran,
                        'perihal' => "Penawaran Setup #{$i} - {$name}",
                        'nama_perusahaan' => "PT Sample Company {$i}",
                        'lokasi' => 'Surabaya',
                        'pic_perusahaan' => 'Sample PIC',
                        'status' => 'draft',
                        'created_at' => $now->copy()->subDays($count - $i), // Spread dates
                        'updated_at' => $now->copy()->subDays($count - $i),
                    ]);

                    // Create default version 0 for each penawaran
                    PenawaranVersion::create([
                        'penawaran_id' => $penawaran->id_penawaran,
                        'version' => 0,
                        'status' => 'draft',
                        'ppn_persen' => 11,
                    ]);

                    if ($i % 5 == 0) {
                        $this->command->info("  - Created {$i}/{$count}");
                    }
                } else {
                    $this->command->warn("  - Skipped {$noPenawaran} (already exists)");
                }
            }

            $this->command->info("✓ Completed for {$name}: {$count} penawaran");
        }

        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('Summary - Next penawaran numbers:');
        $this->command->info(str_repeat('=', 60));
        $this->command->info("Ria (ID 1)   : PIB/SS-SBY/JK/1-016/{$monthRoman}/{$year}");
        $this->command->info("Diyah (ID 2) : PIB/SS-SBY/JK/2-014/{$monthRoman}/{$year}");
        $this->command->info("Dinda (ID 3) : PIB/SS-SBY/JK/3-022/{$monthRoman}/{$year}");
        $this->command->info(str_repeat('=', 60));
    }

    /**
     * Convert number to Roman numeral
     */
    private function convertToRoman($num)
    {
        $map = [
            12 => 'XII', 11 => 'XI', 10 => 'X',
            9 => 'IX', 8 => 'VIII', 7 => 'VII',
            6 => 'VI', 5 => 'V', 4 => 'IV',
            3 => 'III', 2 => 'II', 1 => 'I',
        ];

        return $map[$num] ?? 'I';
    }
}
