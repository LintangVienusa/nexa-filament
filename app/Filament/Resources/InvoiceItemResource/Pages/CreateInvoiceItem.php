<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\InvoiceItem;
use Spatie\Activitylog\Models\Activity;


class CreateInvoiceItem extends CreateRecord
{
    protected static string $resource = InvoiceItemResource::class;
    protected InvoiceItem $createdRecord;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): InvoiceItem
    {
        $poNumber = $data['po_number'] ?? 'PO.' . now()->format('Y.m') . '.' . rand(10000,99999);
        foreach ($data['items'] as $component) {
            // $poNumber = 'PO-'.date('Ymd-His').'-'.rand(100,999);
            $this->createdRecord = InvoiceItem::create([
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
        return $this->createdRecord;
        // return new InvoiceItem();
    }
    protected function afterCreate(): void
    {
        $record = $this->createdRecord; // record yang baru dibuat

            activity('Invoice-action')
                ->causedBy(auth()->user())
                
                ->withProperties([
                    'ip' =>  request()->ip(),
                    'menu' => 'Invoice Items',
                    'email' => auth()->user()?->email,
                    'record_id' => $record->id,
                    'url' => request()->fullUrl(),
                    'name' => $record->name ?? null,
                ])
                ->log('Membuat record Invoice baru');
                Activity::latest()->first()->update([
                    'email' => auth()->user()?->email,
                    'record_id' => $record->id,
                ]);
    }

    

    public function mount(): void
    {
        parent::mount();
        $user = auth()->user();

        $activity = activity('InvoiceItems-access')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  request()->ip(),
                'menu' => 'Invoice Items',
                'email' => $user?->email,
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ])
            ->log('Membuka halaman Create InvoiceItem');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Invoice Items',
            ]);
    }
    
}
