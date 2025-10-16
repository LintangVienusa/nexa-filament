<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user_information', [AuthController::class, 'user_information']);

    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
    Route::post('/attendance', [AttendanceController::class, 'checkabsen']);
    Route::post('/timesheets', [TimesheetController::class, 'store']);
});
