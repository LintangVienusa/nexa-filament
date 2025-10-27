<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoCheckout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-checkout';
    protected $description = 'Auto checkout teknisi jam 20:00, selain teknisi jam 23:00 dan buat semua task Pending ';

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $date = $now->toDateString();

        $technicians = Employee::whereHas('Organization', function ($query) {
            $query->where('unit_name', 'Technician');
        })->get();

        foreach ($technicians as $tech) {
            $attendance = Attendance::where('employee_id', $tech->employee_id)
                            ->whereDate('attendance_date', $date)
                            ->whereNull('checkout_time')
                            ->first();

            if ($attendance && !$attendance->checkout_time) {
                $attendance->checkout_time = $date.' 20:00:00';
                $attendance->updated_by = 'auto checkout';
                $attendance->save();
            }

            $timesheet = Timesheet::where('employee_id', $tech->employee_id)
                ->whereDate('created_at', $now->toDateString())
                ->where('status', '==', '0') 
                ->get();
            foreach ($timesheet as $ts) {
                $ts->update([
                    'status' => '1',
                    'updated_at' => $date.' 20:00:00',
                ]);
            }
        }

        $this->info('Auto checkout teknisi dan task Pending berhasil.');

         $technicians = Employee::whereHas('Organization', function ($query) {
            $query->where('unit_name','!=', 'Technician');
        })->get();

        foreach ($technicians as $tech) {
            $attendance = Attendance::where('employee_id', $tech->employee_id)
                            ->whereDate('attendance_date', $date)
                            ->whereNull('checkout_time')
                            ->first();

            if ($attendance && !$attendance->checkout_time) {
                $attendance->checkout_time = $date.' 23:00:00';
                $attendance->updated_by = 'auto checkout';
                $attendance->updated_at = 'auto checkout';
                $attendance->save();
            }

            $timesheet = Timesheet::where('employee_id', $tech->employee_id)
                ->whereDate('created_at', $now->toDateString())
                ->where('status', '==', '0') 
                ->get();
            foreach ($timesheet as $ts) {
                $ts->update([
                    'status' => '1',
                    'updated_at' => $date.' 23:00:00',
                ]);
            }
        }

        $this->info('Auto checkout teknisi dan task Pending berhasil.');
    }
}
