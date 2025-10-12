<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function Checkin(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        $email = $request->email;
        $check_in_evidence = $request->check_in_evidence;
        $date = $request->date;
        $check_in_time = $request->check_in_time;
        $check_in_latitude = $request->check_in_latitude;
        $check_in_longitude = $request->check_in_longitude;

        // $user = User::where('email', $email)->first();
        $employee = Employee::where('email', $email)->first();

        $info = "OK";

        if (!$employee) {

            $info = "NOK";
            return response()->json([
                'message' => 'User tidak terhubung dengan employee'
            ], 400);
        }

        $employee_id = $employee->employee_id;

        $today = \Carbon\Carbon::today();

        $todayCheckin = Attendance::where('employee_id', $employee_id)
            ->where('attendance_date', $date)
            ->first();
        if ($todayCheckin) {
            $attendance = Attendance::where('employee_id', $employee_id)
                ->where('attendance_date', $date)
                ->first();

            $info = "NOK";
            return response()->json([
                'message' => 'Anda sudah check-in hari ini',
                'data' => $attendance
            ], 400);

        }

        $date_time = $date . " " . $check_in_time;
        if($info ==="OK"){
            $attendance = Attendance::create([
                'employee_id' => $employee->employee_id,
                'attendance_date' => $date,
                'check_in_time' => $date_time,
                'check_in_evidence' => $check_in_evidence,
                'check_in_latitude' => $check_in_latitude,
                'check_in_longitude' => $check_in_longitude,
                'created_by' => $email,
            ]);
        }

        return response()->json([
            'message' => 'Check-in berhasil',
            'data' => $attendance
        ]);
    }

    public function checkout(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        $request->validate([
            'email' => 'required',
        ]);

        $email = $request->email;
        $date = $request->date;
        $check_out_evidence = $request->check_out_evidence;
        $check_out_time = $request->check_out_time;
        $check_out_latitude = $request->check_out_latitude;
        $check_out_longitude = $request->check_out_longitude;

        $user = User::where('email', $email)->first();
        $employee = Employee::where('email', $email)->first();
        $today = Carbon::today();

        $employee_id = $employee->employee_id;
        $attendance = Attendance::where('employee_id', $employee_id)
            ->whereDate('attendance_date', $date)
            ->first();
        if (!$attendance) {
            return response()->json([
                'message' => 'Belum melakukan check-in hari ini',
            'data' => $attendance,
            ], 404);
        }
        if($attendance->check_out_time !=''){
            return response()->json([
                'message' => 'Sudah melakukan check-out hari ini',
            'data' => $attendance,
            ], 404);
        }

        $cek_in =$attendance->check_in_time;
        $time_in = \Carbon\Carbon::parse($cek_in);
        $timeOut = \Carbon\Carbon::parse($date . ' ' . $check_out_time);
        $date_time_out = $date." ".$check_out_time;
        $totalHours = $time_in->diffInHours($timeOut);

        $attendance->check_out_evidence = $check_out_evidence;
        $attendance->check_out_time = $date_time_out;
        $attendance->working_hours = $totalHours;
        $attendance->check_out_latitude = $check_out_latitude;
        $attendance->check_out_longitude = $check_out_longitude;
        $attendance->updated_by = $email;
        $attendance->save();

        return response()->json([
            'message' => 'Check-out berhasil',
            'data' => $attendance
        ]);
    }

     public function checkabsen(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        $email = $request->email;
        $user = User::where('email', $email)->first();
        $employee = Employee::where('email', $email)->first();
        $employee_id = $employee->employee_id;
        $today = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employee_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Belum melakukan check-in hari ini',
            'data' => $attendance,
            ], 404);
        }else{
            return response()->json([
                'message' => 'Sudah melakukan check-in hari ini',
            'data' => $attendance,
            ], 404);
        }

    }
}
