<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Penawaran;
use App\Models\FollowUp;
use Carbon\Carbon;

class SendFollowUpReminder extends Command
{
    protected $signature = 'followup:remind {--dry-run : Show what would be done without actually doing it}';
    protected $description = 'Send follow up reminders for draft penawarans every 7 days';

    public function handle()
    {
        $this->info('🔍 Checking for penawaran that need follow up reminders...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No data will be inserted');
        }

        // Ambil penawaran dengan status draft yang sudah lebih dari 7 hari
        $penawarans = Penawaran::where('status', 'draft')
            ->where('created_at', '<=', Carbon::now()->subDays(6))
            ->get();

        $this->info("📋 Found {$penawarans->count()} draft penawarans older than 7 days");

        $reminderCount = 0;

        foreach ($penawarans as $penawaran) {
            // Cek apakah sudah ada system reminder dalam 7 hari terakhir
            $hasRecentReminder = FollowUp::where('penawaran_id', $penawaran->id_penawaran)
                ->where('is_system_generated', true)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->exists();

            if (!$hasRecentReminder) {
                $daysSinceCreated = (int) $penawaran->created_at->diffInDays(Carbon::now());                $weeksPassed = ceil($daysSinceCreated / 7);

                $reminderData = [
                    'penawaran_id' => $penawaran->id_penawaran,
                    'nama' => "🔔 Reminder Follow Up (Minggu ke-{$weeksPassed})",
                    'deskripsi' => "Sistem mengingatkan untuk melakukan follow up penawaran ini. Penawaran sudah {$daysSinceCreated} hari tanpa aktivitas follow up.",
                    'hasil_progress' => "Segera hubungi klien untuk update status penawaran. Penawaran ini sudah 7 hari tanpa follow up!",
                    'jenis' => 'reminder',
                    'status' => 'pending',
                    'is_system_generated' => true,
                ];

                if (!$dryRun) {
                    FollowUp::create($reminderData);
                }

                $reminderCount++;
                $this->line("✅ Reminder " . ($dryRun ? 'would be sent' : 'sent') . " for: {$penawaran->no_penawaran}");
                $this->line("   📅 Created: {$penawaran->created_at->format('Y-m-d')} ({$daysSinceCreated} days ago)");
            } else {
                $this->line("⏭️ Skipping {$penawaran->no_penawaran} - reminder already sent within 7 days");
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("🧪 DRY RUN COMPLETE - {$reminderCount} reminders would be sent");
            $this->info("💡 Run without --dry-run to actually send reminders");
        } else {
            $this->info("📊 SUMMARY: {$reminderCount} reminders sent successfully!");
        }

        return Command::SUCCESS;
    }
}