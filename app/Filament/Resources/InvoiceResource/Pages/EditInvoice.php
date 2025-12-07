<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\InvoiceItem;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        parent::mount($record);

        $invoiceItem = InvoiceItem::where('invoice_id', $this->record->id)->first();

        if ($invoiceItem) {
            
            $this->redirect('/admin/invoice-items/' . $invoiceItem->id . '/edit');
        } else {
            
            $this->redirect('/admin/invoice-items?invoice_id=' . $this->record->id);
        }
    }
}
