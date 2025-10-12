<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::get('/attendance/checkout', [AttendanceController::class, 'checkout']);
    Route::post('/timesheets', [TimesheetController::class, 'store']);
});
