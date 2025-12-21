<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TimesheetController;
use App\Http\Controllers\Api\BastProjectController;

use App\Http\Controllers\RotatePhotoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/rotate-photo', [RotatePhotoController::class, 'rotate']);

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
    Route::post('/bast/listsite', [BastProjectController::class, 'listsite']);
    Route::post('/bast/create', [BastProjectController::class, 'create']);
    Route::post('/bast/updatepole', [BastProjectController::class, 'updatepole']);
    Route::post('/bast/listpole', [BastProjectController::class, 'listpole']);
    Route::post('/bast/detailpole', [BastProjectController::class, 'detailpole']);
    Route::post('/bast/listodp', [BastProjectController::class, 'listodp']);
    Route::post('/bast/updateodp', [BastProjectController::class, 'updateodp']);
    Route::post('/bast/detailodp', [BastProjectController::class, 'detailodp']);
    Route::post('/bast/listodc', [BastProjectController::class, 'listodc']);
    Route::post('/bast/updateodc', [BastProjectController::class, 'updateodc']);
    Route::post('/bast/detailodc', [BastProjectController::class, 'detailodc']);
    Route::post('/bast/listfeeder', [BastProjectController::class, 'listfeeder']);
    Route::post('/bast/updatefeeder', [BastProjectController::class, 'updatefeeder']);
    Route::post('/bast/detailfeeder', [BastProjectController::class, 'detailfeeder']);
    Route::post('/bast/listrbs', [BastProjectController::class, 'listrbs']);
    Route::post('/bast/updaterbs', [BastProjectController::class, 'updaterbs']);
    Route::post('/bast/detailrbs', [BastProjectController::class, 'detailrbs']);
    Route::post('/bast/updatehomeconnect', [BastProjectController::class, 'updatehomeconnect']);
    Route::post('/bast/listodphc', [BastProjectController::class, 'listodphc']);
    Route::post('/bast/listodp_porthc', [BastProjectController::class, 'listodp_porthc']);
    Route::post('/bast/detailhomeconnect', [BastProjectController::class, 'detailhomeconnect']);
    Route::post('/bast/listcable', [BastProjectController::class, 'listcable']); 
    Route::post('/bast/updatecable', [BastProjectController::class, 'updatecable']); 
    Route::post('/bast/detailcable', [BastProjectController::class, 'detailcable']); 
    Route::post('/bast/dailyprog', [BastProjectController::class, 'dailyprog']); 

    
});
