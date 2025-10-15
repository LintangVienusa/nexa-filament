<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Spatie\Activitylog\Models\Activity;

class ListInvoiceItems extends ListRecords
{
    protected static string $resource = InvoiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Catat aktivitas user saat membuka halaman
        $activity = activity('filament-action')
            ->causedBy(auth()->user())
            ->withProperties([
                    'ip' =>  request()->ip(),
                'email' => auth()->user()->email,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ])
            ->log('Mengakses halaman ListInvoices');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
            ]);

        return [];
    }
}
