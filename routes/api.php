<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum', 'restrict.session')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
    Route::post('/attendance', [AttendanceController::class, 'checkabsen']);
    Route::post('/timesheets', [TimesheetController::class, 'store']);
    Route::post('/timesheets/listjob', [TimesheetController::class, 'listjob']);
    Route::post('/timesheets/update', [TimesheetController::class, 'updateTimesheetStatus']);
});
