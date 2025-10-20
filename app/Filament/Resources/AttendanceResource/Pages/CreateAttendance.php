<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // if (isset($data['check_in_evidence']) &&  str_starts_with($data['check_in_evidence'], 'data:image')) {
        //     // $base64 = $data['check_in_evidence'];

        //     // // hapus prefix data:image/...;base64,
        //     // $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

        //     // // decode base64
        //     // $imgData = base64_decode($base64);

        //     // buat nama unik
        //     // $fileName = 'photo_' . time() . '_' . uniqid() . '.jpg';

        //     // // simpan ke public/photos
        //     // file_put_contents(public_path('photos/' . $fileName), $imgData);

        //     // ganti nilai yang akan masuk ke DB
        //     $data['check_in_evidence'] = $data['check_in_evidence'];
        // }

        $todayAttendance = Attendance::where('employee_id', auth()->user()->employee->employee_id)
            ->whereDate('attendance_date', now())
            ->first();

        if ($todayAttendance) {
            // if (isset($data['check_out_evidence']) && str_starts_with($data['check_out_evidence'], 'data:image')) {
            //     $eviden = $data['check_out_evidence'];
            // }

            // $data['check_out_evidence'] = $eviden;
            $data['check_in_evidence'] = $todayAttendance->check_in_evidence;
            $data['check_in_time'] = $todayAttendance->check_in_time;
            $data['check_in_latitude'] = $todayAttendance->check_in_latitude;
            $data['check_in_longitude'] = $todayAttendance->check_in_longitude;
        } else {
            if (isset($data['check_in_evidence']) && str_starts_with($data['check_in_evidence'], 'data:image')) {
                $data['check_in_evidence'] = Attendance::compressBase64Image($data['check_in_evidence'], 70);
                 $data['check_in_evidence'] = preg_replace('#^data:image/\w+;base64,#i', '', $data['check_in_evidence']);
               
            }
            $data['check_out_evidence'] = null;
            $data['check_out_time'] = null;
            $data['check_out_latitude'] = null;
            $data['check_out_longitude'] = null;
        }

        

        return $data;

    }

   

    public function mount(): void
    {
        parent::mount();

        $today = Carbon::today()->toDateString();
        $employeeId = Auth::user()->employee?->employee_id;

        $existing = Attendance::where('employee_id', $employeeId)
            ->where('attendance_date', $today)
            ->first();

        if ($existing) {
            // Jika sudah check in hari ini, arahkan ke form edit
            $this->redirect(AttendanceResource::getUrl('edit', ['record' => $existing->id]));
        }
    }
   
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
