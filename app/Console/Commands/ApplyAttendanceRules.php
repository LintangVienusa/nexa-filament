<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;
use App\Services\AttendanceRuleService;

class ApplyAttendanceRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:apply-rules {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    
    protected $description = 'Terapkan aturan check-in dan input task ke data attendance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ?? now()->toDateString();

        $this->info("🔄 Memproses aturan attendance untuk tanggal {$date}...");

        $attendances = Attendance::whereDate('attendance_date', $date)->get();

        if ($attendances->isEmpty()) {
            $this->warn("Tidak ada data attendance untuk tanggal {$date}.");
            return Command::SUCCESS;
        }

        foreach ($attendances as $attendance) {
            AttendanceRuleService::applyRules($attendance);
            $this->line("✅ {$attendance->employee_id} -> {$attendance->status}");
        }

        $this->info("✅ Selesai memproses {$attendances->count()} data attendance.");
        return Command::SUCCESS;
    }
}
