<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\InvoiceItem;


class CreateInvoiceItem extends CreateRecord
{
    protected static string $resource = InvoiceItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): InvoiceItem
    {
        $poNumber = $data['po_number'] ?? 'PO.' . now()->format('Y.m') . '.' . rand(10000,99999);
        foreach ($data['items'] as $component) {
            // $poNumber = 'PO-'.date('Ymd-His').'-'.rand(100,999);
            InvoiceItem::create([
                'po_number' =>  $poNumber,
                'po_description' => $data['po_description'],
                'customer_id' => $data['customer_id'],
                'service_id' => $component['service_id'],
                'description' => $component['description'],
                'invoice_date' => $data['invoice_date'],
                'qty' => $component['qty'],
                'unit_price' => $component['price'],
                'subtotal' => $component['subtotal']  ?? ($component['qty'] * $component['price']),
            ]);

             
        }
        return new InvoiceItem();
    }
    
}
