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
        $date = Carbon::now('Asia/Jakarta')->toDateString(); 
        $currentTime = Carbon::now('Asia/Jakarta')->format('H:i:s'); 

        
        $employees = Employee::all();
        foreach ($employees as $emp) {
            $attendance = Attendance::where('employee_id', $emp->employee_id)
                ->whereDate('attendance_date', $date)
                ->first();

            if (! $attendance) continue;

                $hasOvertime = Overtime::where('employee_id', $emp->employee_id)
                    ->where('attendance_id', $attendance->id)
                    ->whereTime('start_time', '>=', '17:00:00')
                    ->whereTime('start_time', '<=', '18:00:00')
                    ->exists();

                if ($hasOvertime) { 
                    $attendance->where('employee_id', $emp->employee_id)->whereDate('attendance_date', $date)->update([
                        'check_out_time' => $date . ' 17:00:00',
                        'updated_by' => 'Auto Checkout',
                    ]);


                    $this->info("Auto checkout jam 17:00 + set lembur jam 18:00 untuk employee {$emp->employee_id}");
                    continue;
                }

                $isTechnician = $emp->Organization?->unit_name === 'Technician';
                $checkoutTime = $isTechnician ? '20:00:00' : '23:00:00';

                if ($currentTime >= $checkoutTime && empty($attendance->check_out_time)) {

                    if ($isTechnician && $currentTime >= '20:00:00') {
                        $attendance->where('employee_id', $emp->employee_id)->whereDate('attendance_date', $date)->update([
                            'check_out_time' => $date . ' 20:00:00',
                            'updated_by' => 'Auto Checkout',
                        ]);

                        Timesheet::where('attendance_id', $attendance->id)
                            ->whereDate('created_at', $date)
                            ->update([
                                'status' => 1,
                                'updated_at' => $date . ' ' . $checkoutTime,
                            ]);
                            
                            $this->line("✅ {$emp->employee_id} -> update teknisi");
                    } elseif (!$isTechnician && $currentTime >= '23:00:00') {
                        $attendance->where('employee_id', $emp->employee_id)->whereDate('attendance_date', $date)->update([
                            'check_out_time' => $date . ' 23:00:00',
                            'updated_by' => 'Auto Checkout',
                        ]);

                        Timesheet::where('attendance_id', $attendance->id)
                            ->whereDate('created_at', $date)
                            ->update([
                                'status' => 1,
                                'updated_at' => $date . ' ' . $checkoutTime,
                            ]);
                            
                            $this->line("✅ {$emp->employee_id} -> update selain teknisi");
                    }
                }

                if ($isTechnician && $attendance->check_out_time > $date . ' 20:00:00') {

                    $attendance->check_out_time = $date . ' 20:00:00';
                    $attendance->updated_by = 'Auto Checkout';
                    $attendance->save();

                    Timesheet::where('attendance_id', $attendance->id)
                        ->whereDate('created_at', $date)
                        
                        ->update([
                            'status' => 1,
                            'updated_at' => $date . ' 20:00:00',
                        ]);

                    $this->line("✔ {$emp->employee_id} -> Fix checkout teknisi >20:00 diturunkan");
                }elseif (!$isTechnician && $attendance->check_out_time > $date .' 23:30:00') {
                        $attendance->where('employee_id', $emp->employee_id)->whereDate('attendance_date', $date)->update([
                            'check_out_time' => $date . ' 23:00:00',
                            'updated_by' => 'Auto Checkout',
                        ]);

                        Timesheet::where('attendance_id', $attendance->id)
                            ->whereDate('created_at', $date)
                            
                            ->update([
                                'status' => 1,
                                'updated_at' => $date . ' ' . $checkoutTime,
                            ]);
                            
                            $this->line("✅ {$emp->employee_id} -> Fix checkout bukan teknisi >23:00 diturunkan");
                    }

               
        }

        
        $this->info("Auto checkout dan lembur selesai dijalankan {$date} {$currentTime}");
    }
}
