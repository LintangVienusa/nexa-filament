<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\Overtime;

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
        $date = now()->toDateString();

        // // ====== 1. Auto Checkout untuk Technician ======
        // $technicians = Employee::whereHas('Organization', function ($query) {
        //     $query->where('unit_name', 'Technician');
        // })->get();

        // foreach ($technicians as $tech) {
        //     $attendance = Attendance::where('employee_id', $tech->employee_id)
        //         ->whereDate('attendance_date', $date)
        //         ->whereNull('check_out_time')
        //         ->first();

        //     if ($attendance) {
        //         $attendance->update([
        //             'check_out_time' => $date . ' 20:00:00',
        //             'updated_by' => 'Auto Checkout',
        //         ]);

        //          Timesheet::where('attendance_id', $attendance->id)
        //                 ->whereDate('created_at', $date)
        //                 ->where('status', 0)
        //                 ->update([
        //                     'status' => 1,
        //                     'updated_at' => $date . ' 20:00:00',
        //                 ]);
        //     }

           
        // }

        // $this->info('Auto checkout Technician selesai.');

        //  // ====== 2. Auto Checkout untuk Non-Technician ======
        // $nonTechnicians = Employee::whereHas('Organization', function ($query) {
        //     $query->where('unit_name', '!=', 'Technician');
        // })->get();

        // foreach ($nonTechnicians as $emp) {
        //     $attendance = Attendance::where('employee_id', $emp->employee_id)
        //         ->whereDate('attendance_date', $date)
        //         ->whereNull('check_out_time')
        //         ->first();

        //     if ($attendance) {
        //         $attendance->update([
        //             'check_out_time' => $date . ' 23:00:00',
        //             'updated_by' => 'Auto Checkout',
        //         ]);

        //         Timesheet::where('attendance_id', $attendance->id)
        //                 ->whereDate('created_at', $date)
        //                 ->where('status', 0)
        //                 ->update([
        //                     'status' => 1,
        //                     'updated_at' => $date . ' 23:00:00',
        //                 ]);
        //     }

            
        // }
        
        $employees = Employee::all();
        foreach ($employees as $emp) {
            $attendance = Attendance::where('employee_id', $emp->employee_id)
                ->whereDate('attendance_date', $date)
                ->whereNull('check_out_time')
                ->first();

        if (! $attendance) continue;

            //  1. Jika ada lembur antara 17:00–18:00
            $hasOvertime = Overtime::where('employee_id', $emp->employee_id)
                ->where('attendance_id', $attendance->id)
                ->whereTime('start_time', '>=', '17:00:00')
                ->whereTime('start_time', '<=', '18:00:00')
                ->exists();

            if ($hasOvertime) {
                // auto checkout jam 17:00
                $attendance->update([
                    'check_out_time' => $date . ' 17:00:00',
                    'updated_by' => 'Auto Checkout',
                ]);

                // lembur mulai 18:00
                // Overtime::where('employee_id', $emp->employee_id)
                //     ->where('attendance_id', $attendance->id)
                //     ->update([
                //         'start_time' => $date . ' 18:00:00',
                //     ]);

                $this->info("Auto checkout jam 17:00 + set lembur jam 18:00 untuk employee {$emp->employee_id}");
                continue;
            }

            //  2. Jika tidak ada lembur, lanjut ke aturan biasa
            $isTechnician = $emp->Organization?->unit_name === 'Technician';
            $checkoutTime = $isTechnician ? '20:00:00' : '23:00:00';

            if (empty($attendance->check_out_time) && empty($hasOvertime->id)) {
                $attendance->update([
                    'check_out_time' => $date . ' ' . $checkoutTime,
                    'updated_by' => 'Auto Checkout',
                ]);

                Timesheet::where('attendance_id', $attendance->id)
                    ->whereDate('created_at', $date)
                    ->where('status', 0)
                    ->update([
                        'status' => 1,
                        'updated_at' => $date . ' ' . $checkoutTime,
                    ]);
            }
        }

        $this->info('Auto checkout dan lembur selesai dijalankan.');
        // $this->info('Auto checkout Non-Technician selesai.');
    }
}
