<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


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

        $employee = Employee::where('email', $user->email)
            ->first();

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data employee tidak ditemukan untuk email ini.',
            ], 404);
        }

        $employeeId = $employee->employee_id;

        $attendance = Attendance::where('id', $validated['attendance_id'])
            ->where('employee_id', $employeeId)
            ->first();

        if (! $attendance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Attendance tidak cocok dengan employee yang login.',
            ], 403);
        }


        $timesheet = Timesheet::create([
            'attendance_id'   => $validated['attendance_id'],
            'job_description' => $validated['job_description'],
            'job_duration'    => $validated['job_duration'] ?? 0,
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

    public function listjob(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        
        $todayTimesheets = Timesheet::where('created_by', $user->email)
        ->whereDate('created_at', now()->toDateString())->where('status', 0) 
        ->get()
        ->map(function ($item) {
            if ($item->status == 0 && $item->created_at) {
                $createdAt = Carbon::parse($item->created_at);
                $now = Carbon::now();

                $diffInMinutes = (int) $createdAt->diffInMinutes($now);

                if ($diffInMinutes < 60) {
                    $item->job_duration = $diffInMinutes . ' menit';
                } else {
                    $hours = floor($diffInMinutes / 60);
                    $minutes = $diffInMinutes % 60;

                    if ($minutes > 0) {
                        $item->job_duration = $hours . ' jam ' . $minutes . ' menit';
                    } else {
                        $item->job_duration = $hours . ' jam';
                    }
                }
            } else {
                $item->job_duration = null;
            }

            $item->status = match($item->status) {
                0 => 'On Progress',
                1 => 'Pending',
                2 => 'Done',
                3 => 'Cancel',
                default => 'Unknown',
            };

            return $item;
        });

        $pendingTimesheets = Timesheet::where('created_by', $user->email)
            ->where('status', 1) 
            ->get()
            ->map(function ($item) { 
                 $item->status = match($item->status) {
                    0 => 'On Progress',
                    1 => 'Pending',
                    2 => 'Done',
                    3 => 'Cancel',
                    default => 'Unknown',
                };

                return $item;
        });
        
        $completeTimesheets = Timesheet::where('created_by', $user->email)
            ->where('status', 2) 
            ->get()
            ->map(function ($item) { 
                 $item->status = match($item->status) {
                    0 => 'On Progress',
                    1 => 'Pending',
                    2 => 'Done',
                    3 => 'Cancel',
                    default => 'Unknown',
                };

                return $item;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'list timesheet',
            'data' => [
                'today' =>[
                    'pending' => $pendingTimesheets,
                    'In Progress' => $todayTimesheets,
                    'Complete' => $completeTimesheets,
                ]
                
            ],
            
        ], 201);
    }

    public function updateTimesheetStatus(Request $request)
    {
         $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        $validated = $request->validate([
            'status' => 'required|integer:1,2,3',
            'notes'  => 'nullable|string',
        ]);

        
        $id = $request->timesheet_id;

        $timesheet = Timesheet::where('created_by', $user->email)
            ->where('id', $id);
            
        $timesheet = $timesheet->first();

        if (!$timesheet) {
            return response()->json([
                'message' => 'Timesheet tidak ditemukan',
            ], 404);
        }

        if ($timesheet->status != 0) {
            if ($timesheet->status == 2) {
                return response()->json([
                    'message' => 'Job sudah selesai',
                ], 400);
            }

            
        }

         $createdAt = Carbon::parse($timesheet->created_at);
            $now = Carbon::now();
            $diffInMinutes = (int) $createdAt->diffInMinutes($now);

        $timesheet->status = $request->status;
        $timesheet->notes  = $request->notes ?? $timesheet->notes;
        $timesheet->job_duration  = $diffInMinutes ?? 0;
        $timesheet->updated_by  = $request->email ?? null;
        $timesheet->save();

        $formattedDuration = $this->formatDuration($diffInMinutes);

        return response()->json([
            'message'   => 'Status timesheet berhasil diperbarui',
            'timesheet' => [
                'id'             => $timesheet->id,
                'status'         => $timesheet->status,
                'notes'          => $timesheet->notes,
                'job_duration'   => $formattedDuration, 
                'created_at'     => $timesheet->created_at,
                'updated_at'     => $timesheet->updated_at,
            ],
        ]);

        
    }

    private function formatDuration(int $minutes): string
        {
            if ($minutes < 60) {
                return $minutes . ' menit';
            }

            $hours = floor($minutes / 60);
            $remainMinutes = $minutes % 60;

            if ($remainMinutes > 0) {
                return $hours . ' jam ' . $remainMinutes . ' menit';
            }

            return $hours . ' jam';
        }
}
