<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceItem extends EditRecord
{
    protected static string $resource = InvoiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ambil semua items terkait invoice
        $invoiceId = $this->record->invoice_id; // ID Invoice
        $items = InvoiceItem::where('invoice_id', $invoiceId)->get();

        // Map ke format Repeater
        $data['items'] = $items->map(function ($item) {
            return [
                'service_id' => $item->service_id,
                'description' => $item->description,
                'qty' => $item->qty,
                'price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ];
        })->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $invoiceId = $this->record->invoice_id;

        // Hapus semua item lama
        InvoiceItem::where('invoice_id', $invoiceId)->delete();

        // Simpan ulang semua item dari repeater
        foreach ($data['items'] as $component) {
            InvoiceItem::create([
                'invoice_id' => $invoiceId,
                'service_id' => $component['service_id'],
                'description' => $component['description'],
                'qty' => $component['qty'],
                'unit_price' => $component['price'],
                'subtotal' => $component['subtotal'],
            ]);
        }

        // Hapus key 'items' agar tidak ikut disimpan di Invoice model
        unset($data['items']);

        return $data;
    }
}
