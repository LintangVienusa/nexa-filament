<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function Checkin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
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
             $filePath = null;

            if ($check_in_evidence) {
                if ($check_in_evidence != '') {
                    $check_in_evidence = "data:image/png;base64," . $check_in_evidence;
                    $data = substr($check_in_evidence, strpos($check_in_evidence, ',') + 1);
                    $data = base64_decode($data);

                    if ($data === false) {
                        return response()->json(['error' => 'Base64 decode failed'], 400);
                    }

                    $fileName = 'check_in_' . time() . '.jpg';
                    $folder = public_path('storage/check_in_evidence');

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    $tempImage = imagecreatefromstring($data);
                    if ($tempImage === false) {
                        return response()->json(['error' => 'Failed to create image from data'], 400);
                    }

                    $width = imagesx($tempImage);
                    $height = imagesy($tempImage);

                    $maxWidth = 800;
                    $maxHeight = 800;
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                    imagedestroy($tempImage);
                    imagedestroy($compressedImage);

                    $filePath = 'check_in_evidence/' . $fileName;
                } else {
                    return response()->json(['error' => 'Invalid base64 format'], 400);
                }
            }
            $attendance = Attendance::create([
                'employee_id' => $employee->employee_id,
                'attendance_date' => $date,
                'check_in_time' => $date_time,
                'check_in_evidence' => $filePath,
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
        date_default_timezone_set('Asia/Jakarta');
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
        if (!$employee || !$employee->employee_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'User '.$email.' tidak terhubung dengan employee'
            ], 404);
        }
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
        
        if($attendance->check_out_evidence !=''){
            return response()->json([
                'message' => 'Sudah melakukan check-out hari ini',
            'data' => $attendance,
            ], 404);
        }

        $atid = $attendance->id;
        $activeTimesheet = Timesheet::where('attendance_id', $atid)
            ->where('status', 0)
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

                return $item;
            });

       $activeTimesheetCount = $activeTimesheet->filter(fn($item) => $item->job_duration !== null)->count();

        if ($activeTimesheetCount>0) {
            return response()->json([
                'message' => 'Masih ada pekerjaan yang sedang berlangsung, selesaikan atau pending terlebih dahulu',
                'data'    =>$activeTimesheet,
            ], 400);
        }

        $cek_in =$attendance->check_in_time;
        $time_in = \Carbon\Carbon::parse($cek_in);
        $timeOut = \Carbon\Carbon::parse($date . ' ' . $check_out_time);
        $date_time_out = $date." ".$check_out_time;
        $totalHours = $time_in->diffInMinutes($timeOut);

         $filePath = null;

        if ($check_out_evidence) {
            if ($check_out_evidence != '') {
                $check_out_evidence = "data:image/png;base64," . $check_out_evidence;
                $data = substr($check_out_evidence, strpos($check_out_evidence, ',') + 1);
                $data = base64_decode($data);

                if ($data === false) {
                    return response()->json(['error' => 'Base64 decode failed'], 400);
                }

                $fileName = 'check_out_' . time() . '.jpg';
                $folder = public_path('storage/check_out_evidence');

                if (!file_exists($folder)) {
                    mkdir($folder, 0777, true);
                }

                $tempImage = imagecreatefromstring($data);
                if ($tempImage === false) {
                    return response()->json(['error' => 'Failed to create image from data'], 400);
                }

                $width = imagesx($tempImage);
                $height = imagesy($tempImage);

                $maxWidth = 800;
                $maxHeight = 800;
                $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $compressedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($compressedImage, $tempImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                imagejpeg($compressedImage, $folder . '/' . $fileName, 75);

                imagedestroy($tempImage);
                imagedestroy($compressedImage);

                $filePath = 'check_out_evidence/' . $fileName;
                
            } else {
                return response()->json(['error' => 'Invalid base64 format'], 400);
            }
        }
        
        $attendance->check_out_evidence = $filePath;
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
        date_default_timezone_set('Asia/Jakarta');
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        $email = $request->email;
        $user = User::where('email', $email)->first();
        $employee = Employee::where('email', $email)->first();
        if (!$employee || !$employee->employee_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'User '.$email.' tidak terhubung dengan employee'
            ], 404);
        }
        $employee_id = $employee->employee_id;
        $today = Carbon::today()->format('Y-m-d');

       

        $attendance = Attendance::where('employee_id', $employee_id)
            ->whereDate('attendance_date', $today)
            ->first();

             if (! $attendance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attendance not found',
                ], 404);
            }
        $workingHours = null;

        if ($attendance->check_out_time) {
            $checkIn = Carbon::parse($attendance->check_in_time, 'Asia/Jakarta');
            $checkOut = Carbon::parse($attendance->check_out_time, 'Asia/Jakarta');

            $diffInSeconds = $checkIn->diffInSeconds($checkOut);

            $hours = floor($diffInSeconds / 3600);
            $minutes = floor(($diffInSeconds % 3600) / 60);

            if ($hours > 0 && $minutes > 0) {
                $workingHours = "{$hours} jam {$minutes} menit";
            } elseif ($hours > 0) {
                $workingHours = "{$hours} jam";
            } else {
                $workingHours = "{$minutes} menit";
            }
        } else {
            // Jika belum checkout, hitung sementara dari jam sekarang
            $checkIn = Carbon::parse($attendance->check_in_time, 'Asia/Jakarta');
            $now = Carbon::now('Asia/Jakarta');
            $diffInSeconds = $checkIn->diffInSeconds($now);

            $hours = floor($diffInSeconds / 3600);
            $minutes = floor(($diffInSeconds % 3600) / 60);

            $workingHours = "{$hours} jam {$minutes} menit (sementara)";
        }

        if (!$attendance) {
            return response()->json([
                'message' => 'Belum melakukan check-in hari ini',
            'data' => $attendance,
            ], 404);
        }else{
            return response()->json([
                'message' => 'Sudah melakukan check-in hari ini',
            'data' => [
                
                        'id' => $attendance->id,
                        'employee_id' => $attendance->employee_id,
                        'attendance_date' => $attendance->attendance_date
                            ? $attendance->attendance_date->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                            : null,
                        'check_in_time' => $attendance->check_in_time
                            ? $attendance->check_in_time->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                            : null,
                        'check_out_time' => $attendance->check_out_time
                            ? $attendance->check_out_time->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                            : null,
                        'working_hours' => $workingHours,
                        'check_in_evidence' => $attendance->check_in_evidence,
                        'check_out_evidence' => $attendance->check_out_evidence,
                        'check_in_latitude' => $attendance->check_in_latitude,
                        'check_in_longitude' => $attendance->check_in_longitude,
                        'check_out_latitude' => $attendance->check_out_latitude,
                        'check_out_longitude' => $attendance->check_out_longitude

                ],
            ], 404);
        }

    }
}
