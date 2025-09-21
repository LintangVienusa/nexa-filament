<?php

namespace App\Filament\Resources\InvoiceItemResource\Pages;

use App\Filament\Resources\InvoiceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\InvoiceItem;
use Filament\Notifications\Notification;

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
       // Pastikan customer_id dari form
        $data['customer_id'] = $data['customer_id'] ?? ($this->record?->customer_id ?? null);

        // Jika ini create, buat invoice dulu untuk dapat ID
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
            // Edit: pakai invoice_id yang ada
            $invoiceId = $this->record->invoice_id;
        }

        // Hapus semua item lama jika edit
        if ($this->record) {
            InvoiceItem::where('invoice_id', $invoiceId)->delete();
        }

        // Simpan semua item dari repeater
        if (!empty($data['items'])) {
            foreach ($data['items'] as $component) {
                InvoiceItem::create([
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

        // Hapus key 'items' agar tidak ikut disimpan di Invoice model
        unset($data['items']);

        // Pastikan invoice_id selalu ada
        $data['id'] ??= $invoiceId;

        return $data;
    }

   
}
