<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use pxlrbt\FilamentActivityLog\Pages\ListActivities;
use App\Filament\Resources\InvoiceItemResource;

class ListInvoiceActivities extends ListActivities
{
    protected static string $resource = InvoiceItemResource::class;
}
