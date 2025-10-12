<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class TimesheetController extends Controller
{
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'attendance_id'   => 'required|integer|exists:mysql_employees.Attendances,id',
            'job_description' => 'required|string|max:255',
            'job_duration'    => 'required|numeric|min:0',
        ]);


        $timesheet = Timesheet::create([
            'attendance_id'   => $validated['attendance_id'],
            'job_description' => $validated['job_description'],
            'job_duration'    => $validated['job_duration'],
            'created_by'      => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Timesheet created successfully',
            'data' => [
                'id' => $timesheet->id,
                'attendance_id' => $timesheet->attendance_id,
                'job_description' => $timesheet->job_description,
                'job_duration' => $timesheet->job_duration,
                'created_by' => $timesheet->created_by,
            ],
        ], 201);
    }
}
