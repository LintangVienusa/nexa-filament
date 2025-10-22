<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Timesheet;
use App\Models\Attendance;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Actions\ActionFailedException;


class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     if (!empty($data['check_out_evidence'])) {
    //     // Ambil record yang sedang di-edit
    //     $this->record->update([
    //         'check_out_evidence' => $data['check_out_evidence'],
    //     ]);

    //     // Hapus dari $data agar Filament tidak mencoba update lagi
    //     unset($data['check_out_evidence']);
    //      }

    //     return $data;
    // }

//     protected functs


    // protected function beforeSave(): void
    // {
    //     $data = $this->form->getState();

    //     $employeeId = $data['employee_id'];
    //     $attendanceDate = $data['attendance_date'];

    //     $attendance = Attendance::where('employee_id', $employeeId)
    //         ->whereDate('attendance_date', $attendanceDate)
    //         ->first();

    //     if (!$attendance) {
    //         return;
    //     }

    //     $existingTimesheet = Timesheet::where('attendance_id', $attendance->id)
    //         ->where('status', 0) // On Progress
    //         ->count();

    //     if ($existingTimesheet > 0) {
    //         // Tampilkan notifikasi
    //         Notification::make()
    //             ->warning()
    //             ->title('Ada pekerjaan yang belum selesai')
    //             ->body("Terdapat {$existingTimesheet} job/timesheet yang masih On Progress atau Pending.")
    //             ->send();
    //     //    throw new \Exception("Penyimpanan dibatalkan karena ada pekerjaan yang belum selesai.");
        
    //         // Batalkan penyimpanan
    //         // throw new \Exception('Penyimpanan dibatalkan karena ada pekerjaan yang belum selesai.');
    //         $data['check_out_evidence'] = null;
    //         $data['check_out_time'] = null;
    //         $data['check_out_latitude'] = null;
    //         $data['check_out_longitude'] = null;
            
    //     }
    //     return $data;
    
    // }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $employeeId = $data['employee_id'];
        $attendance_date = $data['attendance_date'];

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $attendance_date)
            ->first();

            $attendance_id = $attendance?->id;

        $existingTimesheet = Timesheet::where('attendance_id', $attendance_id)
            ->where('status', 0)
            ->count();
            
        if ($this->data['check_out_evidence'] ?? false) {
            $cek_out = $cek_out = $data['check_out_evidence'] ?? null;
           $data['check_out_evidence'] = Attendance::compressBase64Image($data['check_out_evidence'], 70);
             $data['check_out_evidence'] = preg_replace('#^data:image/\w+;base64,#i', '', $cek_out);
            
               $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $data['check_out_evidence']);
                    
                    $imageData = base64_decode($base64);
                    if ($imageData === false) {
                        return response()->json(['error' => 'Base64 decode failed'], 400);
                    }

                    $image = imagecreatefromstring($imageData);
                    if ($image === false) {
                        return response()->json(['error' => 'Invalid image data'], 400);
                    }

                    $folder = storage_path('app/public/check_out_evidence');
                    if (!file_exists($folder)) mkdir($folder, 0777, true);

                   
                    $fileName = 'check_out_' . time() . '.jpg';
                    $filePath = $folder . '/' . $fileName;

                    imagejpeg($image, $filePath, 70);
                    imagedestroy($image);

                    $data['check_out_evidence']= 'check_out_evidence/' . $fileName;




            $this->record->update([
                'check_out_evidence' => $this->data['check_out_evidence'],
            ]);

            
          
            
        }

        if ($existingTimesheet > 0) {
            Notification::make()
                ->warning()
                ->title('Ada pekerjaan yang belum selesai')
                ->body("Terdapat {$existingTimesheet} job/timesheet yang masih On Progress atau Pending.")
                ->send();

                    $data['check_in_evidence'] = $attendance->check_in_evidence;
                    $data['check_out_evidence'] = null;
                    $data['check_out_time'] = null;
                    $data['check_out_latitude'] = null;
                    $data['check_out_longitude'] = null;
                //    throw new ActionFailedException("Tidak bisa menyimpan karena masih ada job yang On Progress.");
    
        }

        

        return $data; // jika aman, kembalikan data untuk save


    }

    // protected function afterSave(): void
    // {
    //     if ($this->data['check_out_evidence'] ?? false) {
    //         $cek_out = $this->$data['check_out_evidence']?? null;
    //          $data['check_out_evidence'] = preg_replace('#^data:image/\w+;base64,#i', '', $this->$data['check_out_evidence']);
            
    //         $this->record->update([
    //             'check_out_evidence' => $this->data['check_out_evidence'],
    //         ]);
    //     }
    // }



    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
