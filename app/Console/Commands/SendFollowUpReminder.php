<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Penawaran;
use App\Models\FollowUp;
use App\Models\FollowUpSchedule;
use Carbon\Carbon;

class SendFollowUpReminder extends Command
{
    protected $signature = 'followup:remind {--dry-run : Show what would be done without actually doing it}';
    protected $description = 'Send follow up reminders for success penawarans with flexible scheduling';

    const DEFAULT_INTERVAL_DAYS = 7;
    const DEFAULT_MAX_REMINDERS = 5;

    public function handle()
    {
        $this->info('🔍 Checking for penawaran that need follow up reminders...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No data will be modified');
        }

        $this->newLine();
        
        // 1. Process scheduled reminders (penawaran yang sudah punya schedule)
        $this->info('📌 Processing scheduled follow-ups...');
        $scheduledCount = $this->processScheduledReminders($dryRun);

        $this->newLine();
        
        // 2. Process legacy/auto reminders (penawaran yang belum punya schedule)
        $this->info('📌 Processing auto follow-ups (penawaran without schedule)...');
        $legacyCount = $this->processAutoReminders($dryRun);

        $this->newLine();
        
        $totalCount = $scheduledCount + $legacyCount;
        
        if ($dryRun) {
            $this->info("🧪 DRY RUN COMPLETE");
            $this->info("   📊 Scheduled reminders: {$scheduledCount}");
            $this->info("   📊 Auto reminders: {$legacyCount}");
            $this->info("   📊 Total: {$totalCount}");
            $this->info("💡 Run without --dry-run to actually send reminders");
        } else {
            $this->info("✅ SUCCESS - {$totalCount} total reminders sent!");
            $this->info("   📊 Scheduled: {$scheduledCount}");
            $this->info("   📊 Auto: {$legacyCount}");
        }

        return Command::SUCCESS;
    }

    /**
     * Process reminders yang sudah punya schedule
     */
    private function processScheduledReminders(bool $dryRun): int
    {
        $schedules = FollowUpSchedule::dueForReminder()
            ->with('penawaran')
            ->get();

        $this->line("   Found {$schedules->count()} scheduled reminders due");

        $count = 0;
        $completedCycles = 0;

        foreach ($schedules as $schedule) {
            $penawaran = $schedule->penawaran;
            
            // Skip jika penawaran bukan draft
            if ($penawaran->status !== 'success') {
                $this->line("   ⏭️ {$penawaran->no_penawaran} - status: {$penawaran->status}");
                continue;
            }

            $newCount = $schedule->current_reminder_count + 1;
            $isLastReminder = $newCount >= $schedule->max_reminders_per_cycle;

            $reminderData = [
                'penawaran_id' => $penawaran->id_penawaran,
                'follow_up_schedule_id' => $schedule->id,
                'nama' => "🔔 Reminder [Cycle {$schedule->cycle_number}] #{$newCount}/{$schedule->max_reminders_per_cycle}",
                'deskripsi' => $this->buildScheduledReminderDescription($schedule, $newCount, $isLastReminder),
                'hasil_progress' => "Segera hubungi klien untuk update status penawaran.",
                'jenis' => 'reminder',
                'status' => 'pending',
                'is_system_generated' => true,
                'cycle_number' => $schedule->cycle_number,
                'reminder_sequence' => $newCount,
            ];

            if (!$dryRun) {
                $followup = FollowUp::create($reminderData);
                $schedule->incrementReminder();

                if ($penawaran->user){
                    $penawaran->user->notify(new \App\Notifications\FollowUpNotification($penawaran, $followup));
                }
                
                if ($isLastReminder) {
                    $completedCycles++;
                }
            }

            $count++;
            
            $status = $dryRun ? 'would be sent' : 'sent';
            $this->line("   ✅ [Scheduled] {$penawaran->no_penawaran} - Reminder #{$newCount}/{$schedule->max_reminders_per_cycle}");
            
            if ($isLastReminder) {
                $this->warn("      🏁 Cycle completed!");
            }
        }

        if ($completedCycles > 0 && !$dryRun) {
            $this->warn("   🏁 {$completedCycles} cycles completed - SPV can start new cycles");
        }

        return $count;
    }

    /**
     * Process auto reminders untuk penawaran tanpa schedule
     * Auto-create schedule saat reminder pertama
     */
    private function processAutoReminders(bool $dryRun): int
    {
        // Ambil penawaran success yang:
        // 1. Belum punya schedule
        // 2. Sudah >= 7 hari sejak dibuat
        $penawarans = Penawaran::where('status', 'success')
            ->where('created_at', '<=', Carbon::now()->subDays(self::DEFAULT_INTERVAL_DAYS))
            ->whereDoesntHave('followUpSchedule')
            ->get();

        $this->line("   Found {$penawarans->count()} penawaran without schedule");

        $count = 0;
        $autoCreatedSchedules = 0;

        foreach ($penawarans as $penawaran) {
            // Hitung total system reminder yang sudah dikirim
            $totalReminders = FollowUp::where('penawaran_id', $penawaran->id_penawaran)
                ->where('is_system_generated', true)
                ->count();

            // Skip jika sudah mencapai max default
            if ($totalReminders >= self::DEFAULT_MAX_REMINDERS) {
                $this->line("   ⏭️ {$penawaran->no_penawaran} - max reminders reached ({$totalReminders})");
                continue;
            }

            // Cek reminder terakhir
            $lastReminder = FollowUp::where('penawaran_id', $penawaran->id_penawaran)
                ->where('is_system_generated', true)
                ->orderBy('created_at', 'desc')
                ->first();

            // Skip jika baru kirim dalam interval hari
            if ($lastReminder && $lastReminder->created_at >= Carbon::now()->subDays(self::DEFAULT_INTERVAL_DAYS)) {
                $this->line("   ⏭️ {$penawaran->no_penawaran} - recent reminder exists");
                continue;
            }

            $newReminderCount = $totalReminders + 1;
            $isLastReminder = $newReminderCount >= self::DEFAULT_MAX_REMINDERS;

            if (!$dryRun) {
                // Auto-create schedule untuk tracking
                $schedule = FollowUpSchedule::create([
                    'penawaran_id' => $penawaran->id_penawaran,
                    'cycle_number' => 1,
                    'current_reminder_count' => $newReminderCount,
                    'max_reminders_per_cycle' => self::DEFAULT_MAX_REMINDERS,
                    'interval_days' => self::DEFAULT_INTERVAL_DAYS,
                    'cycle_start_date' => $penawaran->created_at,
                    'next_reminder_date' => $isLastReminder ? null : Carbon::now()->addDays(self::DEFAULT_INTERVAL_DAYS),
                    'is_active' => !$isLastReminder,
                    'status' => $isLastReminder ? 'completed' : 'running',
                    'last_reminder_sent_at' => Carbon::now(),
                    'notes' => 'Auto-created by system',
                ]);

                $autoCreatedSchedules++;

                // Buat reminder
                $followup = FollowUp::create([
                    'penawaran_id' => $penawaran->id_penawaran,
                    'follow_up_schedule_id' => $schedule->id,
                    'nama' => "🔔 Reminder #{$newReminderCount}/" . self::DEFAULT_MAX_REMINDERS,
                    'deskripsi' => $this->buildAutoReminderDescription($penawaran, $newReminderCount, $isLastReminder),
                    'hasil_progress' => "Segera hubungi klien untuk update status penawaran.",
                    'jenis' => 'reminder',
                    'status' => 'pending',
                    'is_system_generated' => true,
                    'cycle_number' => 1,
                    'reminder_sequence' => $newReminderCount,
                ]);

                if ($penawaran->user) {
                    $penawaran->user->notify(new \App\Notifications\FollowUpNotification($penawaran, $followup));
                }
            }               

            $count++;
            
            $status = $dryRun ? 'would be sent' : 'sent';
            $this->line("   ✅ [Auto] {$penawaran->no_penawaran} - Reminder #{$newReminderCount}/" . self::DEFAULT_MAX_REMINDERS);
            
            if ($isLastReminder) {
                $this->warn("      🏁 Max reminders reached!");
            }
        }

        if ($autoCreatedSchedules > 0 && !$dryRun) {
            $this->info("   📋 {$autoCreatedSchedules} schedules auto-created");
        }

        return $count;
    }

    private function buildScheduledReminderDescription(
        FollowUpSchedule $schedule,
        int $newCount,
        bool $isLastReminder
    ): string {
        $penawaran = $schedule->penawaran;
        $daysSinceCreated = (int) $penawaran->created_at->diffInDays(Carbon::now());
        $daysSinceCycleStart = $schedule->cycle_start_date 
            ? (int) $schedule->cycle_start_date->diffInDays(Carbon::now())
            : 0;

        $desc = "Sistem mengingatkan untuk melakukan follow up penawaran ini.\n\n";
        $desc .= "📊 Info Cycle:\n";
        $desc .= "- Cycle: {$schedule->cycle_number}\n";
        $desc .= "- Reminder: {$newCount} dari {$schedule->max_reminders_per_cycle}\n";
        $desc .= "- Interval: Setiap {$schedule->interval_days} hari\n";
        $desc .= "- Hari sejak penawaran dibuat: {$daysSinceCreated} hari\n";
        $desc .= "- Hari sejak cycle dimulai: {$daysSinceCycleStart} hari\n\n";

        if ($isLastReminder) {
            $desc .= "⚠️ Ini adalah reminder otomatis terakhir untuk cycle ini.\n";
            $desc .= "💡 SPV dapat memulai cycle baru dengan setting custom.";
        } else {
            $nextDate = Carbon::now()->addDays($schedule->interval_days)->format('d M Y');
            $desc .= "📅 Reminder berikutnya: {$nextDate}";
        }

        if ($schedule->notes) {
            $desc .= "\n\n📝 Catatan: {$schedule->notes}";
        }

        return $desc;
    }

    private function buildAutoReminderDescription(
        Penawaran $penawaran,
        int $reminderCount,
        bool $isLastReminder
    ): string {
        $daysSinceCreated = (int) $penawaran->created_at->diffInDays(Carbon::now());
        
        $desc = "Sistem mengingatkan untuk melakukan follow up penawaran ini.\n\n";
        $desc .= "📊 Info:\n";
        $desc .= "- Reminder: {$reminderCount} dari " . self::DEFAULT_MAX_REMINDERS . "\n";
        $desc .= "- Interval: Setiap " . self::DEFAULT_INTERVAL_DAYS . " hari\n";
        $desc .= "- Hari sejak penawaran dibuat: {$daysSinceCreated} hari\n\n";

        if ($isLastReminder) {
            $desc .= "⚠️ Ini adalah reminder otomatis terakhir.\n";
            $desc .= "💡 SPV dapat memulai cycle baru dengan setting custom via dashboard.";
        } else {
            $nextDate = Carbon::now()->addDays(self::DEFAULT_INTERVAL_DAYS)->format('d M Y');
            $desc .= "📅 Reminder berikutnya: {$nextDate}\n";
            $desc .= "💡 Schedule otomatis sudah dibuat untuk penawaran ini.";
        }

        return $desc;
    }
}