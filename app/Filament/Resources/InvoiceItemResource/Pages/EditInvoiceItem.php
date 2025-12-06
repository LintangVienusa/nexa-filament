<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;

class EditInvoiceItem extends EditRecord
{
    protected static string $resource = InvoiceItemResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Cek status invoice
        if ($this->record->invoice?->status === '2') { // 2 = Paid
            Notification::make()
                ->title('Cannot edit')
                ->body('This invoice has already been paid. Editing is not allowed.')
                ->warning()
                ->send();

            // Redirect user ke halaman index agar tidak tetap di form edit
            $this->redirect($this->getResource()::getUrl('index'));

            $activity = activity('InvoiceItems-action')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  request()->ip(),
                'menu' => 'Invoice Items',
                'email' => auth()->user()->email,
                'record_id' => $record->id,
                'record_name' => $record->name ?? null,
            ])
            ->log('Membuka halaman Edit InvoiceItem');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Invoice Items',
                'record_id' => $record->id,
            ]);
        }


        
    }

    protected function canEdit(): bool
    {
        return $this->record->invoice->status !== '2';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $invoiceId = $this->record->invoice_id;
        $items = InvoiceItem::where('invoice_id', $invoiceId)->get();

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
       $record = $this->record;
        $data['customer_id'] = $data['customer_id'] ?? ($this->record?->customer_id ?? null);

        

        if (!$this->record) {
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'invoice_date'   => $data['invoice_date'] ?? now(),
                'customer_id'    => $data['customer_id'],
                'status'         => $data['status'] ?? 0,
                'create_by'      => auth()->user()->email,
            ]);

            $invoiceId = $invoice->id;
        } else {
            $invoiceId = $this->record->invoice_id;
        }

        if ($this->record) {
            InvoiceItem::where('invoice_id', $invoiceId)->delete();
            Invoice::where('id', $invoiceId)->delete();
        }

        if (!empty($data['items'])) {
            foreach ($data['items'] as $component) {
                InvoiceItem::create([
                    'po_number' => $data['po_number'],
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
        }

        $activity = activity('Invoice-action')
            ->causedBy(auth()->user())
            ->withProperties([
                'ip' =>  request()->ip(),
                'menu' => 'Invoice',
                'email' => auth()->user()->email,
                'record_id' => $record->id,
                'po_number' => $record->po_number,
                'record_name' => $record->name ?? null,  
            ])
            ->log('Mengedit record Invoice Items');

            Activity::latest()->first()->update([
                'email' => auth()->user()?->email,
                'menu' => 'Invoice',
                'record_id' => $record->po_number,
            ]);
        unset($data['items']);

        $data['id'] ??= $invoiceId;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // protected functions

   
}
