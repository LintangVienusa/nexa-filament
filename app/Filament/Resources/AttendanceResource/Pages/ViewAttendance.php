<?php
namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\ViewField;


class ViewAttendance extends ViewRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFormSchema(): array
    {
        return [
            ViewField::make('camera_capture')
                ->view('filament.partials.camera-capture')
                ->extraAttributes([
                    'data-check-in' => fn($record) => $record?->check_in_evidence,
                    'data-check-out' => fn($record) => $record?->check_out_evidence,
                ])
        ];
    }
}

