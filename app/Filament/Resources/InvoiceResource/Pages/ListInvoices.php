<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Spatie\Activitylog\Models\Activity;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Catat aktivitas user saat membuka halaman
        $activity = activity('Invoice-access')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  request()->ip(),
                'menu' => 'Invoice',
                'email' => auth()->user()->email,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ])
            ->log('Mengakses halaman ListInvoices');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Invoice',
            ]);

        return [];
    }
}
