<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttandanceController extends Controller
{
    public function Checkin(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        $todayCheckin = Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if (!$employee) {
            return response()->json([
                'message' => 'User tidak terhubung dengan employee'
            ], 400);
        }

        $todayCheckin = Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        if ($todayCheckin) {
                return response()->json([
                    'message' => 'Anda sudah check-in hari ini',
                ], 400);
            }

        $attendance = Attendance::create([
            'employee_id' => $employee->employee_id,
            'checkin_time' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Check-in berhasil',
            'data' => $attendance
        ]);
    }
}
