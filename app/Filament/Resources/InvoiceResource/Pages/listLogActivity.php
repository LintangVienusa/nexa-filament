<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use App\Models\LogActivity;
use App\Models\InvoiceItem;

class listLogActivity extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Log Activity Invoice';

    public $invoiceId;

    public function mount(): void
    {
        $this->invoiceId = request()->route('record');
    }

    public function table(Table $table): Table
    {
        $poNumber = InvoiceItem::where('invoice_id', $this->invoiceId)
            ->pluck('po_number')
            ->unique()
            ->first();
            // dd($poNumber);
            
        return $table
            ->query(
                LogActivity::query() ->select(
                        \DB::raw('MAX(id) as id'),
                        'description',
                        'created_at',
                        'email','properties'
                    )
                    ->whereIn('log_name', ['Invoice-action'])
                    ->when($this->invoiceId, function ($q) use ($poNumber) {
                        $q->where(function ($query) use ($poNumber) {
                            $query->Where('properties','REGEXP', $poNumber);
                            
                        });
                    })->groupBy('description', 'created_at','email','properties')
                    ->orderBy('id', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Deskripsi')->limit(60),
                Tables\Columns\TextColumn::make('properties.email')->label('User'),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
