<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TimesheetController;
use App\Http\Controllers\Api\BastProjectController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    #attendance
    Route::post('/attendance/checkin', [AttendanceController::class, 'checkin']);
    Route::post('/attendance/checkout', [AttendanceController::class, 'checkout']);
    Route::post('/attendance', [AttendanceController::class, 'checkabsen']);


    #timesheets
    Route::post('/timesheets', [TimesheetController::class, 'store']);
    Route::post('/timesheets/listjob', [TimesheetController::class, 'listjob']);
    Route::post('/timesheets/update', [TimesheetController::class, 'updateTimesheetStatus']);

    #BAST
    Route::post('/bast/list', [BastProjectController::class, 'index']);
    Route::post('/bast/create', [BastProjectController::class, 'create']);
    Route::post('/bast/updatepole', [BastProjectController::class, 'updatepole']);
});
