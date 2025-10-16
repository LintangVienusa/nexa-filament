<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Spatie\Activitylog\Models\Activity;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Catat aktivitas user saat membuka halaman
        $activity = activity('Leaves-access')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  request()->ip(),
                'menu' => 'Leaves Items',
                'email' => auth()->user()->email,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ])
            ->log('Mengakses halaman ListInvoicesItems');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Leaves Items',
            ]);

        return [];
    }
}
